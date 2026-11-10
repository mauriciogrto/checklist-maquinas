<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Empresa;
use App\Models\LocacaoCliente;
use App\Models\Maquina;
use App\Models\Orcamento;
use App\Models\RastreadorDispositivo;
use App\Models\RastreadorPosicao;
use App\Models\Registro;
use App\Models\RegistroResposta;
use App\Models\SistemaCobranca;
use App\Models\SistemaProposta;
use App\Services\FormularioService;
use App\Services\RelatorioPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RelatorioController extends Controller
{
    private const TIPOS = [
        'geral' => 'Geral operacional',
        'maquinas' => 'Máquinas e contratos',
        'clientes' => 'Clientes',
        'checklists' => 'Checklists',
        'manutencoes' => 'Manutenções',
        'alertas' => 'Alertas',
        'horimetro' => 'Horímetro',
        'localizacoes' => 'Localizações e rastreamento',
        'financeiro' => 'Financeiro',
    ];

    public function index()
    {
        return view('admin.relatorios.index', [
            'tiposRelatorio' => self::TIPOS,
            'empresas' => $this->empresasDisponiveis(),
            'clientes' => $this->clientesDisponiveis(),
            'maquinas' => $this->maquinasDisponiveis(),
            'empresaAtual' => $this->empresaAtual(),
        ]);
    }

    public function export(Request $request, RelatorioPdfService $pdf)
    {
        $filtros = $this->validarFiltros($request);
        $data = $this->montarRelatorio($filtros);

        return response($pdf->render($data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($filtros).'"',
        ]);
    }

    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(self::TIPOS))],
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:locacao_clientes,id'],
            'maquina_id' => ['nullable', 'integer', 'exists:maquinas,id'],
            'tipo_manutencao' => ['nullable', 'in:'.implode(',', array_keys(FormularioService::TIPOS_MANUTENCAO))],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date'],
        ]);
    }

    private function montarRelatorio(array $filtros): array
    {
        $tipo = $filtros['tipo'] ?? 'geral';
        $contexto = $this->contexto($filtros);

        $data = [
            'tipo_label' => self::TIPOS[$tipo] ?? 'Relatório geral',
            'filtros' => $filtros,
            'filtros_labels' => $tipo === 'manutencoes' ? $this->labelsFiltrosManutencao($filtros) : $this->labelsFiltros($filtros),
            'resumo' => $tipo === 'manutencoes' ? null : $this->resumo($filtros, $contexto),
            'indicadores' => $tipo === 'manutencoes' ? null : $this->indicadores($filtros, $contexto),
            'secoes' => [],
            'observacoes' => $tipo === 'manutencoes'
                ? 'Relatório gerado apenas com dados operacionais da manutenção.'
                : 'Relatório gerado para análise gerencial. Os totais respeitam os filtros aplicados no momento da exportação.',
        ];

        $secoes = match ($tipo) {
            'maquinas' => [$this->secaoMaquinas($filtros, $contexto)],
            'clientes' => [$this->secaoClientes($filtros, $contexto)],
            'checklists' => [$this->secaoRegistros($filtros, $contexto)],
            'manutencoes' => [$this->secaoManutencoes($filtros, $contexto)],
            'alertas' => [$this->secaoAlertas($filtros, $contexto)],
            'horimetro' => [$this->secaoHorimetro($filtros, $contexto)],
            'localizacoes' => [$this->secaoLocalizacoes($filtros, $contexto)],
            'financeiro' => [$this->secaoOrcamentos($filtros, $contexto), $this->secaoPropostas($filtros, $contexto), $this->secaoCobrancas($filtros, $contexto)],
            default => [
                $this->secaoMaquinas($filtros, $contexto),
                $this->secaoAlertas($filtros, $contexto),
                $this->secaoRegistros($filtros, $contexto),
                $this->secaoManutencoes($filtros, $contexto),
                $this->secaoLocalizacoes($filtros, $contexto),
                $this->secaoOrcamentos($filtros, $contexto),
            ],
        };

        $data['secoes'] = array_filter($secoes);

        return $data;
    }

    private function resumo(array $filtros, array $contexto): array
    {
        $maquinas = $this->maquinasQuery($filtros, $contexto);
        $registros = $this->registrosQuery($filtros, $contexto);
        $alertas = $this->alertasQuery($filtros, $contexto);
        $rastreador = $this->rastreadorQuery($filtros, $contexto);

        return [
            'Máquinas' => $maquinas->count(),
            'Locadas' => (clone $maquinas)->where('locacao_status', 'locada')->count(),
            'Checklists' => $registros->count(),
            'Alertas abertos' => (clone $alertas)->whereIn('status', ['pendente', 'em_analise'])->count(),
            'Online' => (clone $rastreador)->where('ultimo_sinal_em', '>=', now()->subHours(2))->count(),
            'Sem comunicação' => (clone $rastreador)->where(function ($query) {
                $query->whereNull('ultimo_sinal_em')->orWhere('ultimo_sinal_em', '<', now()->subHours(2));
            })->count(),
        ];
    }

    private function indicadores(array $filtros, array $contexto): array
    {
        $orcamentos = $this->orcamentosQuery($filtros, $contexto);
        $cobrancas = $this->cobrancasQuery($filtros, $contexto);
        $posicoes = $this->posicoesQuery($filtros, $contexto);

        return [
            'Orçamentos aprovados' => (clone $orcamentos)->where('status', 'aprovado')->count(),
            'Valor em cobranças abertas' => 'R$ '.number_format((float) (clone $cobrancas)->where('status', 'aberta')->sum('valor'), 2, ',', '.'),
            'Cobranças vencidas' => (clone $cobrancas)->where('status', 'vencida')->count(),
            'Posições recebidas' => $posicoes->count(),
            'Máquinas com ignição ligada' => (clone $this->rastreadorQuery($filtros, $contexto))->where('ignicao', true)->count(),
        ];
    }

    private function secaoMaquinas(array $filtros, array $contexto): array
    {
        $rows = $this->maquinasQuery($filtros, $contexto)
            ->with(['empresa', 'locacaoCliente', 'rastreador'])
            ->orderBy('nome')
            ->limit(30)
            ->get()
            ->map(fn (Maquina $maquina) => [
                $maquina->nome,
                $maquina->locacaoStatusLabel(),
                $maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente ?? '-',
                $maquina->contrato_fim?->format('d/m/Y') ?? '-',
                $maquina->rastreador?->ultimo_sinal_em?->format('d/m/Y H:i') ?? '-',
            ])
            ->all();

        return [
            'titulo' => 'Máquinas e contratos',
            'descricao' => 'Visão das máquinas filtradas, cliente atual, fim previsto de contrato e último sinal de rastreamento.',
            'colunas' => ['Máquina', 'Situação', 'Cliente', 'Fim contrato', 'Último sinal'],
            'linhas' => $rows,
        ];
    }

    private function secaoClientes(array $filtros, array $contexto): array
    {
        $rows = $this->clientesQuery($filtros, $contexto)
            ->withCount(['maquinas', 'orcamentos'])
            ->orderBy('nome')
            ->limit(30)
            ->get()
            ->map(fn (LocacaoCliente $cliente) => [
                $cliente->nome,
                $cliente->status ?: '-',
                $cliente->maquinas_count,
                $cliente->orcamentos_count,
            ])
            ->all();

        return [
            'titulo' => 'Clientes',
            'descricao' => 'Resumo dos clientes cadastrados e seus vínculos operacionais.',
            'colunas' => ['Cliente', 'Status', 'Máquinas', 'Orçamentos'],
            'linhas' => $rows,
        ];
    }

    private function secaoRegistros(array $filtros, array $contexto): array
    {
        $rows = $this->registrosQuery($filtros, $contexto, 'checklist')
            ->with(['maquina', 'operador'])
            ->latest('data_hora')
            ->limit(30)
            ->get()
            ->map(fn (Registro $registro) => [
                $registro->data_hora?->format('d/m/Y H:i') ?? '-',
                $registro->maquina?->codigo_interno ?: '-',
                $registro->maquina?->nome ?? '-',
                $registro->operador?->nome ?? 'Dono/admin',
                $this->tipoRegistroLabel($registro->tipo, $registro->periodicidade),
                $registro->status_geral ?: '-',
            ])
            ->all();

        return [
            'titulo' => 'Checklists',
            'descricao' => 'Últimos checklists enviados dentro dos filtros, incluindo responsável e status geral.',
            'colunas' => ['Data', 'Código', 'Máquina', 'Responsável', 'Tipo', 'Status'],
            'linhas' => $rows,
        ];
    }

    private function secaoManutencoes(array $filtros, array $contexto): array
    {
        $rows = $this->registrosQuery($filtros, $contexto, 'manutencao')
            ->with(['maquina', 'respostas.item'])
            ->latest('data_hora')
            ->limit(30)
            ->get()
            ->map(function (Registro $registro) {
                $respostas = $registro->respostas->keyBy(fn (RegistroResposta $resposta) => $this->chaveRespostaManutencao($resposta->item?->pergunta));

                return [
                    'data_hora' => $registro->data_hora?->format('d/m/Y H:i') ?? '-',
                    'maquina' => $registro->maquina?->nome ?? '-',
                    'tipo' => $this->tipoManutencaoLabel($registro->periodicidade),
                    'horimetro' => $this->respostaManutencao($respostas, 'horimetro'),
                    'descricao' => $this->respostaManutencao($respostas, 'descricao'),
                    'tecnico' => $this->respostaManutencao($respostas, 'profissional'),
                    'assinatura' => $this->respostaManutencao($respostas, 'assinatura'),
                    'itens' => $this->itensManutencaoResumo($registro),
                ];
            })
            ->all();

        return [
            'titulo' => 'Manutenções',
            'descricao' => 'Histórico de intervenções com data, hora e itens preenchidos na manutenção.',
            'colunas' => ['Data/hora', 'Máquina / tipo', 'Horímetro', 'Itens registrados'],
            'linhas' => $rows,
        ];
    }

    private function secaoAlertas(array $filtros, array $contexto): array
    {
        $rows = $this->alertasQuery($filtros, $contexto)
            ->with(['maquina', 'operador', 'item'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (Alerta $alerta) => [
                '#'.$alerta->id,
                $alerta->maquina?->codigo_interno ?: '-',
                $alerta->maquina?->nome ?? '-',
                $alerta->operador?->nome ?? '-',
                $this->statusLabel($alerta->status),
                $alerta->created_at?->format('d/m/Y H:i') ?? '-',
            ])
            ->all();

        return [
            'titulo' => 'Alertas',
            'descricao' => 'Alertas encontrados nos filtros, com status atual para análise de tratativa.',
            'colunas' => ['ID', 'Código', 'Máquina', 'Responsável', 'Status', 'Criado em'],
            'linhas' => $rows,
        ];
    }

    private function secaoHorimetro(array $filtros, array $contexto): array
    {
        $rows = $this->rastreadorQuery($filtros, $contexto)
            ->with('maquina')
            ->orderByDesc('ultimo_sinal_em')
            ->limit(30)
            ->get()
            ->map(fn (RastreadorDispositivo $rastreador) => [
                $rastreador->maquina?->nome ?? 'Não vinculada',
                $rastreador->ignicao ? 'Ligada' : 'Desligada',
                $this->minutosLabel($rastreador->minutosTrabalhadosHoje()),
                $rastreador->maquina?->horimetro_inicial_horas ? number_format((float) $rastreador->maquina->horimetro_inicial_horas, 2, ',', '.').' h' : '-',
                $rastreador->ultimo_sinal_em?->format('d/m/Y H:i') ?? '-',
            ])
            ->all();

        return [
            'titulo' => 'Horímetro',
            'descricao' => 'Estimativa de uso por ignição recebida dos rastreadores. Para auditoria detalhada, use o PDF individual da máquina.',
            'colunas' => ['Máquina', 'Ignição', 'Uso hoje', 'Horímetro inicial', 'Último sinal'],
            'linhas' => $rows,
        ];
    }

    private function secaoLocalizacoes(array $filtros, array $contexto): array
    {
        $rows = $this->posicoesQuery($filtros, $contexto)
            ->with('maquina')
            ->orderByDesc('recebido_em')
            ->limit(30)
            ->get()
            ->map(fn (RastreadorPosicao $posicao) => [
                $posicao->maquina?->nome ?? '-',
                $posicao->momentoLocalLabel() ?? '-',
                $posicao->localizacaoLabel(),
                ((float) $posicao->velocidade).' km/h',
                $posicao->ignicao ? 'Ligada' : 'Desligada',
            ])
            ->all();

        return [
            'titulo' => 'Localizações',
            'descricao' => 'Últimas posições recebidas por GPS conforme filtros aplicados.',
            'colunas' => ['Máquina', 'Data', 'Localização', 'Vel.', 'Ignição'],
            'linhas' => $rows,
        ];
    }

    private function secaoOrcamentos(array $filtros, array $contexto): array
    {
        $rows = $this->orcamentosQuery($filtros, $contexto)
            ->with(['cliente', 'itens'])
            ->latest('data_orcamento')
            ->limit(25)
            ->get()
            ->map(fn (Orcamento $orcamento) => [
                '#'.$orcamento->id,
                $orcamento->cliente?->nome ?? '-',
                $this->statusLabel($orcamento->status),
                $orcamento->data_orcamento?->format('d/m/Y') ?? '-',
                'R$ '.number_format($orcamento->valorTotal(), 2, ',', '.'),
            ])
            ->all();

        return [
            'titulo' => 'Orçamentos',
            'descricao' => 'Resumo comercial dos orçamentos no período selecionado.',
            'colunas' => ['Número', 'Cliente', 'Status', 'Data', 'Total'],
            'linhas' => $rows,
        ];
    }

    private function secaoPropostas(array $filtros, array $contexto): array
    {
        if (! $this->isSuperAdmin()) {
            return [
                'titulo' => 'Propostas do sistema',
                'descricao' => 'Área disponível apenas para administradores master do sistema.',
                'colunas' => ['Informação'],
                'linhas' => [],
            ];
        }

        $rows = SistemaProposta::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->when($filtros['empresa_id'] ?? null, fn ($query, $empresaId) => $query->where('empresa_id', $empresaId))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('created_at', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('created_at', '<=', $fim))
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (SistemaProposta $proposta) => [
                '#'.$proposta->id,
                $proposta->cliente_nome,
                $proposta->statusLabel(),
                'R$ '.number_format($proposta->totalMensal(), 2, ',', '.'),
                'R$ '.number_format($proposta->totalImplantacao(), 2, ',', '.'),
            ])
            ->all();

        return [
            'titulo' => 'Propostas do sistema',
            'descricao' => 'Histórico comercial de propostas do Gestão de Máquinas, comodato e rastreamento.',
            'colunas' => ['ID', 'Cliente', 'Status', 'Mensal', 'Implantação'],
            'linhas' => $rows,
        ];
    }

    private function secaoCobrancas(array $filtros, array $contexto): array
    {
        $rows = $this->cobrancasQuery($filtros, $contexto)
            ->orderBy('vencimento')
            ->limit(25)
            ->get()
            ->map(fn (SistemaCobranca $cobranca) => [
                $cobranca->cliente_nome,
                $cobranca->referencia_mes?->format('m/Y') ?? '-',
                $cobranca->vencimento?->format('d/m/Y') ?? '-',
                $cobranca->statusLabel(),
                'R$ '.number_format((float) $cobranca->valor, 2, ',', '.'),
            ])
            ->all();

        return [
            'titulo' => 'Cobranças',
            'descricao' => 'Controle mensal das cobranças cadastradas na aba Sistema.',
            'colunas' => ['Cliente', 'Referência', 'Vencimento', 'Status', 'Valor'],
            'linhas' => $rows,
        ];
    }

    private function maquinasQuery(array $filtros, array $contexto): Builder
    {
        return Maquina::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->when($filtros['empresa_id'] ?? null, fn ($query, $id) => $query->where('empresa_id', $id))
            ->when($filtros['cliente_id'] ?? null, fn ($query, $id) => $query->where('locacao_cliente_id', $id))
            ->when($filtros['maquina_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where(function ($inner) use ($status) {
                $inner->where('status', $status)->orWhere('locacao_status', $status);
            }));
    }

    private function clientesQuery(array $filtros, array $contexto): Builder
    {
        return LocacaoCliente::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->when($filtros['empresa_id'] ?? null, fn ($query, $id) => $query->where('empresa_id', $id))
            ->when($filtros['cliente_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    private function registrosQuery(array $filtros, array $contexto, ?string $tipoRegistro = null): Builder
    {
        return Registro::query()
            ->whereHas('maquina', fn ($query) => $query->whereIn('empresa_id', $contexto['empresa_ids']))
            ->when($tipoRegistro, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($tipoRegistro === 'manutencao' && ! empty($filtros['tipo_manutencao']), fn ($query) => $query->where('periodicidade', $filtros['tipo_manutencao']))
            ->when($filtros['maquina_id'] ?? null, fn ($query, $id) => $query->where('maquina_id', $id))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('data_hora', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('data_hora', '<=', $fim))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status_geral', $status));
    }

    private function alertasQuery(array $filtros, array $contexto): Builder
    {
        return Alerta::query()
            ->whereHas('maquina', fn ($query) => $query->whereIn('empresa_id', $contexto['empresa_ids']))
            ->when($filtros['maquina_id'] ?? null, fn ($query, $id) => $query->where('maquina_id', $id))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('created_at', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('created_at', '<=', $fim))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    private function rastreadorQuery(array $filtros, array $contexto): Builder
    {
        return RastreadorDispositivo::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->when($filtros['maquina_id'] ?? null, fn ($query, $id) => $query->where('maquina_id', $id))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where(function ($inner) use ($status) {
                if ($status === 'online') {
                    $inner->where('ultimo_sinal_em', '>=', now()->subHours(2));
                } elseif ($status === 'offline') {
                    $inner->whereNull('ultimo_sinal_em')->orWhere('ultimo_sinal_em', '<', now()->subHours(2));
                } else {
                    $inner->where('status', $status);
                }
            }));
    }

    private function posicoesQuery(array $filtros, array $contexto): Builder
    {
        return RastreadorPosicao::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->when($filtros['maquina_id'] ?? null, fn ($query, $id) => $query->where('maquina_id', $id))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('recebido_em', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('recebido_em', '<=', $fim));
    }

    private function orcamentosQuery(array $filtros, array $contexto): Builder
    {
        return Orcamento::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->when($filtros['cliente_id'] ?? null, fn ($query, $id) => $query->where('locacao_cliente_id', $id))
            ->when($filtros['maquina_id'] ?? null, fn ($query, $id) => $query->where(function ($inner) use ($id) {
                $inner->where('maquina_id', $id)->orWhereHas('itens', fn ($item) => $item->where('maquina_id', $id));
            }))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('data_orcamento', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('data_orcamento', '<=', $fim))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    private function cobrancasQuery(array $filtros, array $contexto): Builder
    {
        $clienteNome = null;
        $clientesPermitidos = LocacaoCliente::query()
            ->whereIn('empresa_id', $contexto['empresa_ids'])
            ->pluck('nome')
            ->all();

        if (! empty($filtros['cliente_id'])) {
            $clienteNome = LocacaoCliente::query()->whereKey($filtros['cliente_id'])->value('nome');
        }

        return SistemaCobranca::query()
            ->when((! $this->isSuperAdmin() || ! empty($filtros['empresa_id'])) && $clientesPermitidos !== [], fn ($query) => $query->whereIn('cliente_nome', $clientesPermitidos))
            ->when($clienteNome, fn ($query, $nome) => $query->where('cliente_nome', $nome))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('vencimento', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('vencimento', '<=', $fim))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    private function contexto(array $filtros): array
    {
        if ($this->isSuperAdmin()) {
            $ids = Empresa::query()
                ->when($filtros['empresa_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                ->pluck('id')
                ->all();
        } else {
            $ids = [$this->empresaAtualId()];
        }

        return ['empresa_ids' => array_values(array_filter($ids))];
    }

    private function labelsFiltros(array $filtros): array
    {
        return [
            'Empresa' => ! empty($filtros['empresa_id'])
                ? Empresa::query()->whereKey($filtros['empresa_id'])->value('nome')
                : ($this->isSuperAdmin() ? 'Todas permitidas' : ($this->empresaAtual()?->nome ?? 'Empresa não definida')),
            'Cliente' => ! empty($filtros['cliente_id']) ? LocacaoCliente::query()->whereKey($filtros['cliente_id'])->value('nome') : 'Todos',
            'Máquina' => ! empty($filtros['maquina_id']) ? Maquina::query()->whereKey($filtros['maquina_id'])->value('nome') : 'Todas',
            'Tipo de manutenção' => ! empty($filtros['tipo_manutencao'])
                ? $this->tipoManutencaoLabel($filtros['tipo_manutencao'])
                : 'Todos',
        ];
    }

    private function labelsFiltrosManutencao(array $filtros): array
    {
        return [
            'Tipo de manutenção' => ! empty($filtros['tipo_manutencao'])
                ? $this->tipoManutencaoLabel($filtros['tipo_manutencao'])
                : 'Todos',
        ];
    }

    private function empresasDisponiveis()
    {
        return $this->isSuperAdmin()
            ? Empresa::query()->orderBy('nome')->get()
            : Empresa::query()->whereKey($this->empresaAtualId())->get();
    }

    private function clientesDisponiveis()
    {
        return LocacaoCliente::query()
            ->whereIn('empresa_id', $this->contexto([])['empresa_ids'])
            ->orderBy('nome')
            ->get();
    }

    private function maquinasDisponiveis()
    {
        return Maquina::query()
            ->whereIn('empresa_id', $this->contexto([])['empresa_ids'])
            ->orderBy('nome')
            ->get();
    }

    private function tipoRegistroLabel(?string $tipo, ?string $periodicidade): string
    {
        $base = [
            'checklist' => 'Checklist',
            'manutencao' => 'Manutenção',
            'problema' => 'Relato de problema',
        ][$tipo ?: ''] ?? ucfirst((string) $tipo);

        return trim($base.' '.($periodicidade ? ucfirst($periodicidade) : ''));
    }

    private function tipoManutencaoLabel(?string $tipo): string
    {
        return FormularioService::TIPOS_MANUTENCAO[$tipo ?: ''] ?? '-';
    }

    private function chaveRespostaManutencao(?string $pergunta): string
    {
        $texto = str((string) $pergunta)->lower()->ascii();

        return match (true) {
            $texto->contains('horimetro') => 'horimetro',
            $texto->contains('descricao') || $texto->contains('intervencao') || $texto->contains('foi feito') => 'descricao',
            $texto->contains('profissional') || $texto->contains('tecnico') || $texto->contains('empresa') => 'profissional',
            $texto->contains('assinatura') || $texto->contains('responsavel') => 'assinatura',
            default => md5((string) $pergunta),
        };
    }

    private function respostaManutencao($respostas, string $chave): string
    {
        return filled($respostas->get($chave)?->resposta) ? $respostas->get($chave)->resposta : '-';
    }

    private function itensManutencaoResumo(Registro $registro): string
    {
        $itens = $registro->respostas
            ->filter(fn (RegistroResposta $resposta) => filled($resposta->resposta))
            ->reject(fn (RegistroResposta $resposta) => in_array($this->chaveRespostaManutencao($resposta->item?->pergunta), [
                'horimetro',
                'descricao',
                'profissional',
                'assinatura',
            ], true))
            ->map(function (RegistroResposta $resposta) {
                $pergunta = trim((string) $resposta->item?->pergunta);

                if ($pergunta === '') {
                    return trim((string) $resposta->resposta);
                }

                return $pergunta.': '.$resposta->resposta;
            })
            ->values();

        return $itens->isNotEmpty() ? $itens->implode(' | ') : '-';
    }

    private function statusLabel(?string $status): string
    {
        return $status ? ucfirst(str_replace('_', ' ', $status)) : '-';
    }

    private function minutosLabel(int $minutos): string
    {
        if ($minutos < 60) {
            return $minutos.' min';
        }

        return floor($minutos / 60).'h '.($minutos % 60).'min';
    }
}
