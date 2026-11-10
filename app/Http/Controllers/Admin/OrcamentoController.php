<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocacaoCliente;
use App\Models\LocacaoHistorico;
use App\Models\Maquina;
use App\Models\Orcamento;
use App\Services\OrcamentoContratoPdfService;
use App\Services\OrcamentoPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrcamentoController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['busca', 'locacao_cliente_id', 'status']);
        $statusFiltro = in_array($request->status, ['rascunho', 'enviado', 'aprovado', 'recusado'], true)
            ? $request->status
            : null;

        $orcamentos = $this->scopeEmpresa(Orcamento::query())
            ->with(['cliente', 'maquina', 'itens.maquina'])
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = trim((string) $request->busca);

                $query->where(function ($inner) use ($busca) {
                    $inner->where('numero', 'like', "%{$busca}%")
                        ->orWhere('titulo', 'like', "%{$busca}%")
                        ->orWhere('descricao', 'like', "%{$busca}%")
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->where('nome', 'like', "%{$busca}%"))
                        ->orWhereHas('itens', fn ($item) => $item->where('descricao', 'like', "%{$busca}%"));
                });
            })
            ->when($request->filled('locacao_cliente_id'), fn ($query) => $query->where('locacao_cliente_id', $request->locacao_cliente_id))
            ->when($statusFiltro, fn ($query) => $query->where('status', $statusFiltro))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $clientes = $this->scopeEmpresa(LocacaoCliente::query())->orderBy('nome')->get();

        return view('admin.orcamentos.index', compact('orcamentos', 'clientes', 'filtros'));
    }

    public function create()
    {
        return view('admin.orcamentos.form', $this->formData(new Orcamento([
            'data_orcamento' => now(),
            'validade' => now()->addDays(10),
            'data_faturamento' => now(),
            'status' => 'rascunho',
            'frete_tipo' => 'cif',
            'desconto' => 0,
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $contratoArquivo = $request->file('contrato_arquivo');
        $itens = $data['itens'] ?? [];
        unset($data['itens'], $data['contrato_arquivo']);

        $data = $this->normalizeRelations($data);
        $data['numero'] = $this->numeroOrcamento($data['numero'] ?? null);
        $data['titulo'] = $data['numero'];

        $orcamento = DB::transaction(function () use ($data, $itens, $contratoArquivo) {
            $orcamento = Orcamento::create($data + [
                'empresa_id' => $this->empresaAtualId(),
            ]);

            $this->sincronizarItens($orcamento, $itens);
            $this->sincronizarLocacaoAprovada($orcamento, $contratoArquivo);

            return $orcamento;
        });

        return redirect()->route('admin.orcamentos.edit', $orcamento)->with('status', 'Orçamento criado.');
    }

    public function show(Orcamento $orcamento)
    {
        $this->abortUnlessEmpresa($orcamento->empresa_id);

        return redirect()->route('admin.orcamentos.edit', $orcamento);
    }

    public function edit(Orcamento $orcamento)
    {
        $this->abortUnlessEmpresa($orcamento->empresa_id);

        return view('admin.orcamentos.form', $this->formData($orcamento));
    }

    public function update(Request $request, Orcamento $orcamento)
    {
        $this->abortUnlessEmpresa($orcamento->empresa_id);

        $orcamento->loadMissing('itens.maquina');
        $statusAnterior = $orcamento->status;
        $clienteAnteriorId = $orcamento->locacao_cliente_id;
        $maquinasAprovadasAnteriormente = $orcamento->itens
            ->pluck('maquina')
            ->filter()
            ->unique('id')
            ->values();

        $data = $this->normalizeRelations($this->validated($request));
        $contratoArquivo = $request->file('contrato_arquivo');
        $itens = $data['itens'] ?? [];
        unset($data['itens'], $data['contrato_arquivo']);

        $data['numero'] = $this->numeroOrcamento($data['numero'] ?? $orcamento->numero);
        $data['titulo'] = $data['numero'];

        DB::transaction(function () use ($orcamento, $data, $itens, $contratoArquivo, $statusAnterior, $clienteAnteriorId, $maquinasAprovadasAnteriormente) {
            $orcamento->update($data);
            $this->sincronizarItens($orcamento, $itens);
            $this->liberarLocacoesRemovidasDoOrcamento($orcamento, $statusAnterior, $clienteAnteriorId, $maquinasAprovadasAnteriormente);
            $this->sincronizarLocacaoAprovada($orcamento, $contratoArquivo);
        });

        return redirect()->route('admin.orcamentos.edit', $orcamento)->with('status', 'Orçamento atualizado.');
    }

    public function destroy(Orcamento $orcamento)
    {
        $this->abortUnlessEmpresa($orcamento->empresa_id);
        $orcamento->delete();

        return redirect()->route('admin.orcamentos.index')->with('status', 'Orçamento removido.');
    }

    public function pdf(Orcamento $orcamento, OrcamentoPdfService $pdf)
    {
        $this->abortUnlessEmpresa($orcamento->empresa_id);
        $orcamento->load(['cliente', 'maquina', 'itens.maquina', 'empresa']);

        return response($pdf->render($orcamento), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($orcamento).'"',
        ]);
    }

    public function contrato(Orcamento $orcamento, OrcamentoContratoPdfService $pdf)
    {
        $this->abortUnlessEmpresa($orcamento->empresa_id);
        abort_unless($orcamento->status === 'aprovado', 404);

        $orcamento->load(['cliente', 'maquina', 'itens.maquina', 'empresa']);

        return response($pdf->render($orcamento), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($orcamento).'"',
        ]);
    }

    private function formData(Orcamento $orcamento): array
    {
        if ($orcamento->exists) {
            $orcamento->loadMissing('itens.maquina');
        }

        return [
            'orcamento' => $orcamento,
            'clientes' => $this->scopeEmpresa(LocacaoCliente::query())->orderBy('nome')->get(),
            'maquinas' => $this->scopeEmpresa(Maquina::query())->orderBy('nome')->get(),
        ];
    }

    private function normalizeRelations(array $data): array
    {
        foreach (['locacao_cliente_id', 'maquina_id'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            }
        }

        if ($data['locacao_cliente_id']) {
            $cliente = $this->scopeEmpresa(LocacaoCliente::query())->findOrFail($data['locacao_cliente_id']);
            $data['contato_nome'] = $cliente->nome;
            $data['contato_email'] = $cliente->email;
            $data['contato_telefone'] = $cliente->telefone;
        } else {
            $data['contato_nome'] = null;
            $data['contato_email'] = null;
            $data['contato_telefone'] = null;
        }

        if ($data['maquina_id']) {
            $this->scopeEmpresa(Maquina::query())->findOrFail($data['maquina_id']);
        }

        $data['data_orcamento'] = ($data['data_orcamento'] ?? null) ?: now()->toDateString();
        $data['validade'] = ($data['validade'] ?? null) ?: Carbon::parse($data['data_orcamento'])->addDays(10)->toDateString();

        if (($data['status'] ?? null) === 'aprovado') {
            $data['data_faturamento'] = ($data['data_faturamento'] ?? null) ?: now()->toDateString();
        } else {
            $data['data_faturamento'] = null;
        }

        foreach (['valor_locacao', 'valor_frete', 'desconto_percentual', 'imposto_percentual'] as $campo) {
            $data[$campo] = $data[$campo] ?? 0;
        }

        if (($data['condicoes_pagamento'] ?? null) !== 'Boleto') {
            $data['prazo_faturamento'] = null;
        }

        $data['desconto'] = 0;
        $data['descricao'] = null;

        return $data;
    }

    private function sincronizarItens(Orcamento $orcamento, array $itens): void
    {
        $orcamento->itens()->delete();
        $valorItens = 0;
        $totalDias = 0;
        $inicioMaisAntigo = null;
        $fimMaisRecente = null;

        foreach ($itens as $item) {
            $maquinaId = $item['maquina_id'] ?? null;
            $descricao = trim((string) ($item['descricao'] ?? ''));
            $inicioItem = $this->normalizarDataItem($item['contrato_inicio'] ?? null);
            $fimItem = $this->normalizarDataItem($item['contrato_fim'] ?? null);
            $diasContrato = $this->diasContratoItem($inicioItem, $fimItem, $item['quantidade'] ?? 1);
            $valorUnitario = (float) ($item['valor_unitario'] ?? 0);

            if (! $maquinaId && $descricao === '' && ! $inicioItem && ! $fimItem && $valorUnitario <= 0) {
                continue;
            }

            $maquina = null;
            if ($maquinaId) {
                $maquina = $this->scopeEmpresa(Maquina::query())->findOrFail($maquinaId);
            }

            $diasContrato = $diasContrato > 0 ? $diasContrato : 1;
            $descricao = $descricao ?: ($maquina?->nome ?? 'Item do orçamento');

            $orcamento->itens()->create([
                'maquina_id' => $maquina?->id,
                'descricao' => $descricao,
                'contrato_inicio' => $inicioItem,
                'contrato_fim' => $fimItem,
                'quantidade' => $diasContrato,
                'valor_unitario' => $valorUnitario,
            ]);

            $valorItens += $diasContrato * $valorUnitario;
            $totalDias += $diasContrato;

            if ($inicioItem && (! $inicioMaisAntigo || $inicioItem < $inicioMaisAntigo)) {
                $inicioMaisAntigo = $inicioItem;
            }

            if ($fimItem && (! $fimMaisRecente || $fimItem > $fimMaisRecente)) {
                $fimMaisRecente = $fimItem;
            }
        }

        $updates = [
            'valor_locacao' => $valorItens,
            'tempo_contrato' => $totalDias > 0 ? (string) $totalDias : null,
            'desconto' => 0,
        ];

        $updates['contrato_inicio'] = $inicioMaisAntigo;
        $updates['contrato_fim'] = $fimMaisRecente;

        $orcamento->update($updates);
    }

    private function normalizarDataItem(mixed $valor): ?string
    {
        if (! $valor) {
            return null;
        }

        try {
            return Carbon::parse($valor)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function diasContratoItem(?string $inicio, ?string $fim, mixed $fallback): int
    {
        if ($inicio && $fim) {
            $dias = Carbon::parse($inicio)->diffInDays(Carbon::parse($fim), false) + 1;

            return max((int) $dias, 1);
        }

        $diasContrato = (int) $fallback;

        return $diasContrato > 0 ? $diasContrato : 1;
    }

    private function sincronizarLocacaoAprovada(Orcamento $orcamento, ?UploadedFile $contratoArquivo = null): void
    {
        if ($orcamento->status !== 'aprovado') {
            return;
        }

        $orcamento->load(['cliente', 'itens.maquina']);

        foreach ($orcamento->itens as $item) {
            $maquina = $item->maquina;

            if (! $maquina) {
                continue;
            }

            $anteriores = $this->dadosLocacao($maquina);
            $contratoInicio = $item->contrato_inicio ?: $orcamento->contrato_inicio;
            $contratoFim = $item->contrato_fim ?: $orcamento->contrato_fim;

            $maquina->update([
                'locacao_status' => 'locada',
                'locacao_cliente_id' => $orcamento->locacao_cliente_id,
                'locacao_cliente' => $orcamento->cliente?->nome,
                'contrato_inicio' => $contratoInicio,
                'contrato_fim' => $contratoFim,
                'devolucao_prevista' => $contratoFim,
                'devolucao_realizada' => null,
                'frete_tipo' => $orcamento->frete_tipo,
            ]);

            $maquina->refresh();
            $this->registrarHistoricoLocacaoAprovada($maquina, $orcamento, $anteriores);

            if ($contratoArquivo && $contratoArquivo->isValid()) {
                $path = $contratoArquivo->store('maquinas/'.$maquina->id.'/contratos', 'public');

                $maquina->arquivos()->create([
                    'categoria' => 'contrato',
                    'nome_original' => $contratoArquivo->getClientOriginalName(),
                    'caminho_arquivo' => $path,
                    'mime_type' => $contratoArquivo->getMimeType() ?: 'application/octet-stream',
                    'tamanho' => $contratoArquivo->getSize() ?: 0,
                ]);
            }
        }
    }

    private function liberarLocacoesRemovidasDoOrcamento(Orcamento $orcamento, ?string $statusAnterior, ?int $clienteAnteriorId, $maquinasAprovadasAnteriormente): void
    {
        if ($statusAnterior !== 'aprovado' || $maquinasAprovadasAnteriormente->isEmpty()) {
            return;
        }

        $orcamento->load('itens.maquina');
        $maquinasAtuaisAprovadas = $orcamento->status === 'aprovado'
            ? $orcamento->itens->pluck('maquina_id')->filter()->unique()->values()
            : collect();

        $maquinasParaLiberar = $maquinasAprovadasAnteriormente
            ->reject(fn (Maquina $maquina) => $maquinasAtuaisAprovadas->contains($maquina->id));

        foreach ($maquinasParaLiberar as $maquina) {
            $maquina->refresh();

            if ($maquina->locacao_status !== 'locada' || (int) $maquina->locacao_cliente_id !== (int) $clienteAnteriorId) {
                continue;
            }

            $anteriores = $this->dadosLocacao($maquina);

            $maquina->update([
                'locacao_status' => 'disponivel',
                'locacao_cliente_id' => null,
                'locacao_cliente' => null,
                'locacao_local' => null,
                'contrato_inicio' => null,
                'contrato_fim' => null,
                'devolucao_prevista' => null,
                'devolucao_realizada' => null,
                'frete_tipo' => null,
            ]);

            $maquina->refresh();

            LocacaoHistorico::create([
                'empresa_id' => $maquina->empresa_id,
                'maquina_id' => $maquina->id,
                'locacao_cliente_id' => $clienteAnteriorId,
                'admin_id' => auth('admin')->id(),
                'status_anterior' => $anteriores['locacao_status'] ?? null,
                'status_novo' => $maquina->locacao_status,
                'cliente_anterior' => $anteriores['locacao_cliente'] ?? null,
                'cliente_novo' => null,
                'local_anterior' => $anteriores['locacao_local'] ?? null,
                'local_novo' => null,
                'contrato_inicio' => $anteriores['contrato_inicio'] ?? null,
                'contrato_fim' => $anteriores['contrato_fim'] ?? null,
                'devolucao_prevista' => $anteriores['devolucao_prevista'] ?? null,
                'devolucao_realizada' => $anteriores['devolucao_realizada'] ?? null,
                'observacao' => 'Orçamento deixou de estar aprovado: '.($orcamento->numero ?: '#'.$orcamento->id),
            ]);
        }
    }

    private function dadosLocacao(Maquina $maquina): array
    {
        return [
            'locacao_status' => $maquina->locacao_status,
            'locacao_cliente_id' => $maquina->locacao_cliente_id,
            'locacao_cliente' => $maquina->locacao_cliente,
            'locacao_local' => $maquina->locacao_local,
            'contrato_inicio' => $maquina->contrato_inicio?->format('Y-m-d'),
            'contrato_fim' => $maquina->contrato_fim?->format('Y-m-d'),
            'devolucao_prevista' => $maquina->devolucao_prevista?->format('Y-m-d'),
            'devolucao_realizada' => $maquina->devolucao_realizada?->format('Y-m-d'),
        ];
    }

    private function registrarHistoricoLocacaoAprovada(Maquina $maquina, Orcamento $orcamento, array $anteriores): void
    {
        LocacaoHistorico::create([
            'empresa_id' => $maquina->empresa_id,
            'maquina_id' => $maquina->id,
            'locacao_cliente_id' => $maquina->locacao_cliente_id,
            'admin_id' => auth('admin')->id(),
            'status_anterior' => $anteriores['locacao_status'] ?? null,
            'status_novo' => $maquina->locacao_status,
            'cliente_anterior' => $anteriores['locacao_cliente'] ?? null,
            'cliente_novo' => $maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente,
            'local_anterior' => $anteriores['locacao_local'] ?? null,
            'local_novo' => $maquina->locacao_local,
            'contrato_inicio' => $maquina->contrato_inicio?->format('Y-m-d'),
            'contrato_fim' => $maquina->contrato_fim?->format('Y-m-d'),
            'devolucao_prevista' => $maquina->devolucao_prevista?->format('Y-m-d'),
            'devolucao_realizada' => $maquina->devolucao_realizada?->format('Y-m-d'),
            'observacao' => 'Orçamento aprovado: '.($orcamento->numero ?: '#'.$orcamento->id),
        ]);
    }

    private function numeroOrcamento(?string $numero): string
    {
        return $numero ?: 'ORC-'.now()->format('Ymd-His');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'locacao_cliente_id' => ['nullable', 'integer', 'exists:locacao_clientes,id'],
            'maquina_id' => ['nullable', 'integer', 'exists:maquinas,id'],
            'numero' => ['nullable', 'string', 'max:255'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'contato_email' => ['nullable', 'email', 'max:255'],
            'contato_telefone' => ['nullable', 'string', 'max:255'],
            'data_orcamento' => ['nullable', 'date'],
            'validade' => ['nullable', 'date'],
            'data_faturamento' => ['nullable', 'date'],
            'contrato_inicio' => ['nullable', 'date'],
            'contrato_fim' => ['nullable', 'date', 'after_or_equal:contrato_inicio'],
            'tempo_contrato' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'frete_tipo' => ['nullable', 'in:cif,fob,outro'],
            'valor_locacao' => ['nullable', 'numeric', 'min:0'],
            'valor_frete' => ['nullable', 'numeric', 'min:0'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'desconto_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'imposto_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'condicoes_pagamento' => ['nullable', 'string'],
            'prazo_faturamento' => ['nullable', 'string', 'max:60'],
            'observacoes' => ['nullable', 'string'],
            'status' => ['required', 'in:rascunho,enviado,aprovado,recusado'],
            'contrato_arquivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
            'itens' => ['nullable', 'array'],
            'itens.*.maquina_id' => ['nullable', 'integer', 'exists:maquinas,id'],
            'itens.*.descricao' => ['nullable', 'string', 'max:255'],
            'itens.*.contrato_inicio' => ['nullable', 'date'],
            'itens.*.contrato_fim' => ['nullable', 'date'],
            'itens.*.quantidade' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'itens.*.valor_unitario' => ['nullable', 'numeric', 'min:0'],
        ], [
            'contato_email.email' => 'Informe um e-mail válido.',
            'contrato_arquivo.mimes' => 'O contrato deve ser PDF ou imagem.',
            'contrato_arquivo.max' => 'O contrato pode ter no máximo 15 MB.',
        ]);
    }
}
