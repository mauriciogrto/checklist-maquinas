<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistReset;
use App\Models\Empresa;
use App\Models\Formulario;
use App\Models\LocacaoCliente;
use App\Models\LocacaoHistorico;
use App\Models\Maquina;
use App\Models\MaquinaArquivo;
use App\Models\RastreadorDispositivo;
use App\Models\SistemaCobranca;
use App\Services\FormularioService;
use App\Services\HorimetroPdfService;
use App\Services\HorimetroService;
use App\Services\QrCodeService;
use App\Support\ChecklistPeriodicidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaquinaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['maquina', 'situacao', 'cliente', 'art', 'rastreador', 'empresa']);
        $hoje = now()->toDateString();
        $limiteArt = now()->addDays(30)->toDateString();
        $limiteOnline = now()->subMinutes(RastreadorDispositivo::ONLINE_MINUTES);
        $superadmin = $this->isSuperAdmin();
        $empresasFiltro = $superadmin
            ? Empresa::query()->orderBy('nome')->get(['id', 'nome'])
            : collect();
        $empresaFiltroId = $superadmin && ! empty($filtros['empresa']) && ctype_digit((string) $filtros['empresa'])
            ? (int) $filtros['empresa']
            : null;
        $clientesLocacao = $this->clientesLocacao($empresaFiltroId);
        $rastreamentoHabilitado = $this->rastreamentoHabilitado();
        $isComercial = (bool) auth('admin')->user()?->isComercial();

        $query = ($superadmin ? Maquina::query() : $this->scopeEmpresa(Maquina::query()))
            ->with(['empresa', 'formularioChecklist', 'formularioManutencao', 'arquivos', 'locacaoCliente', 'rastreador']);

        if ($superadmin && $empresaFiltroId) {
            $query->where('empresa_id', $empresaFiltroId);
        }

        if (! empty($filtros['maquina'])) {
            $busca = trim((string) $filtros['maquina']);

            $query->where(function ($inner) use ($busca) {
                $inner->where('nome', 'like', "%{$busca}%")
                    ->orWhere('codigo_interno', 'like', "%{$busca}%")
                    ->orWhere('modelo', 'like', "%{$busca}%")
                    ->orWhere('setor', 'like', "%{$busca}%");
            });
        }

        if (! empty($filtros['situacao'])) {
            $query->where('locacao_status', $filtros['situacao']);
        }

        if (! empty($filtros['cliente'])) {
            $cliente = trim((string) $filtros['cliente']);

            $query->where(function ($inner) use ($cliente, $clientesLocacao) {
                if (ctype_digit($cliente)) {
                    $inner->where('locacao_cliente_id', (int) $cliente);

                    $clienteNome = $clientesLocacao->firstWhere('id', (int) $cliente)?->nome;

                    if ($clienteNome) {
                        $inner->orWhere('locacao_cliente', 'like', "%{$clienteNome}%");
                    }

                    return;
                }

                $inner->where('locacao_cliente', 'like', "%{$cliente}%");
            });
        }

        if (! empty($filtros['art'])) {
            match ($filtros['art']) {
                'vencida' => $query->whereDate('art_seguranca_vencimento', '<', $hoje),
                'vencendo' => $query->whereDate('art_seguranca_vencimento', '>=', $hoje)
                    ->whereDate('art_seguranca_vencimento', '<=', $limiteArt),
                'em_dia' => $query->whereDate('art_seguranca_vencimento', '>', $limiteArt),
                'sem_data' => $query->whereNull('art_seguranca_vencimento'),
                default => null,
            };
        }

        if ($rastreamentoHabilitado && ! empty($filtros['rastreador'])) {
            match ($filtros['rastreador']) {
                'com' => $query->whereHas('rastreador'),
                'sem' => $query->whereDoesntHave('rastreador'),
                'online' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador->where('ultimo_sinal_em', '>=', $limiteOnline)),
                'movimento' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador->where('ultimo_sinal_em', '>=', $limiteOnline)->where('ignicao', true)),
                'ligada' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador->where('ultimo_sinal_em', '>=', $limiteOnline)->whereNotNull('tensao_externa')->where('tensao_externa', '>', RastreadorDispositivo::EXTERNAL_POWER_OFF_MAX_VOLTAGE)),
                'sem_alimentacao' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador->where('ultimo_sinal_em', '>=', $limiteOnline)->whereNotNull('tensao_externa')->where('tensao_externa', '<=', RastreadorDispositivo::EXTERNAL_POWER_OFF_MAX_VOLTAGE)),
                'offline' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador
                    ->where(function ($inner) use ($limiteOnline) {
                        $inner->whereNull('ultimo_sinal_em')
                            ->orWhere('ultimo_sinal_em', '<', $limiteOnline);
                    })),
                default => null,
            };
        }

        $maquinas = $query->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.maquinas.index', compact('maquinas', 'clientesLocacao', 'empresasFiltro', 'filtros', 'rastreamentoHabilitado', 'superadmin', 'isComercial'));
    }

    public function create()
    {
        return view('admin.maquinas.form', [
            'maquina' => new Maquina(['status' => 'ativa']),
            'modelosChecklist' => $this->modelosChecklist(),
            'clientesLocacao' => $this->clientesLocacao(),
            'rastreamentoHabilitado' => $this->rastreamentoHabilitado(),
            'podeGerenciarRastreamento' => $this->podeGerenciarRastreamento(),
        ]);
    }

    public function store(Request $request, FormularioService $formularios)
    {
        $empresa = $this->empresaAtual();

        if ($empresa && Maquina::query()->where('empresa_id', $empresa->id)->count() >= $empresa->limite_maquinas) {
            return back()->withErrors([
                'nome' => 'Limite atingido: esta empresa já possui o máximo de máquinas contratado.',
            ])->withInput();
        }

        $data = $this->validated($request);
        $rastreadorData = $this->podeGerenciarRastreamento() ? $this->extrairRastreadorData($data) : [];
        $this->removerRastreadorData($data);
        $checklistModelos = $data['checklist_modelos'] ?? [];
        unset($data['arquivos'], $data['contrato_arquivo'], $data['checklist_modelos']);

        $maquina = DB::transaction(function () use ($data, $formularios, $checklistModelos, $rastreadorData) {
            $maquina = Maquina::create($data + [
                'empresa_id' => $this->empresaAtualId(),
                'token' => Str::uuid()->toString(),
                'locacao_status' => 'disponivel',
            ]);

            if ($this->podeGerenciarRastreamento() && $this->rastreamentoHabilitado()) {
                $this->sincronizarRastreador($maquina, $rastreadorData);
            }

            $this->sincronizarChecklists($maquina, $formularios, $checklistModelos, true);

            $manutencao = $formularios->criarFormularioManutencao($maquina);
            $diario = $maquina->formularioPorPeriodicidade('diario');

            $maquina->update(array_filter([
                'formulario_checklist_id' => $diario?->id,
                'formulario_manutencao_id' => $manutencao->id,
            ]));

            if ($maquina->locacao_status !== 'disponivel' || $maquina->locacao_cliente_id || $maquina->locacao_cliente) {
                $this->registrarHistoricoLocacao($maquina, null, 'Cadastro inicial da locacao.');
            }

            return $maquina;
        });

        $this->salvarArquivos($maquina, $request);

        return redirect()->route('admin.maquinas.index')->with('status', 'Máquina criada.');
    }

    public function show(Request $request, Maquina $maquina, QrCodeService $qrCode, HorimetroService $horimetro)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        $rastreamentoHabilitado = $this->rastreamentoHabilitado($maquina);

        $maquina->load(['formularios.itens', 'formularioManutencao.itens', 'arquivos', 'locacaoCliente', 'empresa', 'rastreador']);

        $periodicidades = ChecklistPeriodicidade::labelsPadrao();
        $agora = now();
        $ciclos = collect(ChecklistPeriodicidade::PADROES)
            ->mapWithKeys(fn (string $periodicidade) => [
                $periodicidade => ChecklistPeriodicidade::ciclo($periodicidade, $maquina->created_at, $agora),
            ]);

        $ultimosRegistros = $maquina->registros()
            ->with(['admin', 'operador'])
            ->where('tipo', 'checklist')
            ->whereIn('periodicidade', array_keys($periodicidades))
            ->latest('data_hora')
            ->get()
            ->groupBy('periodicidade')
            ->map(fn ($registros) => $registros->first());

        $resets = ChecklistReset::query()
            ->where('maquina_id', $maquina->id)
            ->latest('resetado_em')
            ->get()
            ->filter(function (ChecklistReset $reset) use ($ciclos, $maquina, $agora) {
                $ciclo = $ciclos->get($reset->periodicidade)
                    ?? (ChecklistPeriodicidade::isExtra($reset->periodicidade)
                        ? ChecklistPeriodicidade::ciclo($reset->periodicidade, $maquina->created_at, $agora)
                        : null);

                return $ciclo && $reset->resetado_em->betweenIncluded($ciclo['inicio'], $ciclo['fim']);
            })
            ->groupBy('periodicidade')
            ->map(fn ($resets) => $resets->first());

        $registrosCiclo = $maquina->registros()
            ->with(['admin', 'operador'])
            ->where('tipo', 'checklist')
            ->whereIn('periodicidade', ChecklistPeriodicidade::PADROES)
            ->latest('data_hora')
            ->get()
            ->filter(function ($registro) use ($ciclos) {
                $ciclo = $ciclos->get($registro->periodicidade);

                return $ciclo
                    && $registro->data_hora->betweenIncluded($ciclo['inicio'], $ciclo['fim']);
            })
            ->filter(function ($registro) use ($resets) {
                $reset = $resets->get($registro->periodicidade);

                return ! $reset || $registro->data_hora->greaterThan($reset->resetado_em);
            })
            ->groupBy('periodicidade')
            ->map(fn ($registros) => $registros->first());

        $formularios = $maquina->formularios
            ->where('tipo', 'checklist_maquina')
            ->whereIn('periodicidade', ChecklistPeriodicidade::PADROES)
            ->keyBy('periodicidade');

        $checklists = collect($periodicidades)->map(function (string $label, string $periodicidade) use ($formularios, $ultimosRegistros, $registrosCiclo, $ciclos, $resets, $agora) {
            $formulario = $formularios->get($periodicidade);
            $registroCiclo = $registrosCiclo->get($periodicidade);
            $ciclo = $ciclos->get($periodicidade);
            $reset = $resets->get($periodicidade);
            $tempoRestante = $this->tempoRestanteChecklist($agora, $ciclo['fim']);

            return [
                'periodicidade' => $periodicidade,
                'label' => $label,
                'formulario' => $formulario,
                'itens_count' => $formulario?->itens->count() ?? 0,
                'ultimo_registro' => $ultimosRegistros->get($periodicidade),
                'registro_ciclo' => $registroCiclo,
                'status_dia' => $registroCiclo ? 'concluido' : 'aberto',
                'status_label' => $registroCiclo
                    ? ($periodicidade === 'diario' ? 'Concluído hoje' : 'Concluído no ciclo atual')
                    : ($periodicidade === 'diario' ? 'Em aberto hoje' : 'Em aberto no ciclo atual'),
                'prazo_label' => $registroCiclo ? 'Próximo em '.$tempoRestante : 'Faltam '.$tempoRestante,
                'status_hint' => $registroCiclo
                    ? 'Realizado em '.$registroCiclo->data_hora->format('d/m/Y H:i')
                    : ($reset
                        ? 'Resetado manualmente em '.$reset->resetado_em->format('d/m/Y H:i').'.'
                        : 'Ciclo: '.$ciclo['inicio']->format('d/m/Y').' até '.$ciclo['fim']->format('d/m/Y H:i').'.'),
                'reset_ciclo' => $reset,
            ];
        })->values();

        $checklistsExtras = $maquina->formularios
            ->where('tipo', 'checklist_maquina')
            ->filter(fn (Formulario $formulario) => ChecklistPeriodicidade::isExtra($formulario->periodicidade))
            ->sortByDesc('created_at')
            ->values()
            ->map(function (Formulario $formulario) use ($maquina, $agora, $resets) {
                $periodicidade = $formulario->periodicidade ?: 'extra_diario';
                $ciclo = ChecklistPeriodicidade::ciclo($periodicidade, $formulario->created_at ?? $maquina->created_at, $agora);
                $reset = $resets->get($periodicidade);
                $tempoRestante = $this->tempoRestanteChecklist($agora, $ciclo['fim']);

                $ultimoRegistro = $maquina->registros()
                    ->with(['admin', 'operador'])
                    ->where('tipo', 'checklist')
                    ->where('periodicidade', $periodicidade)
                    ->where('formulario_id', $formulario->id)
                    ->latest('data_hora')
                    ->first();

                $registroCiclo = $maquina->registros()
                    ->with(['admin', 'operador'])
                    ->where('tipo', 'checklist')
                    ->where('periodicidade', $periodicidade)
                    ->where('formulario_id', $formulario->id)
                    ->whereBetween('data_hora', [$ciclo['inicio'], $ciclo['fim']])
                    ->when($reset, fn ($query) => $query->where('data_hora', '>', $reset->resetado_em))
                    ->latest('data_hora')
                    ->first();

                return [
                    'formulario' => $formulario,
                    'periodicidade' => $periodicidade,
                    'periodicidade_label' => ChecklistPeriodicidade::label($periodicidade),
                    'itens_count' => $formulario->itens->count(),
                    'ultimo_registro' => $ultimoRegistro,
                    'registro_ciclo' => $registroCiclo,
                    'status_dia' => $registroCiclo ? 'concluido' : 'aberto',
                    'status_label' => $registroCiclo ? 'Concluído no ciclo atual' : 'Em aberto',
                    'prazo_label' => $registroCiclo ? 'Próximo em '.$tempoRestante : 'Faltam '.$tempoRestante,
                ];
            });

        $registrosOperador = $maquina->registros()
            ->with(['operador', 'formulario'])
            ->where('tipo', 'checklist')
            ->where(function ($query) {
                $query->whereIn('periodicidade', ['diario', 'mensal'])
                    ->orWhere('periodicidade', 'like', 'extra%');
            })
            ->latest('data_hora')
            ->take(20)
            ->get();

        $registrosTecnicos = $maquina->registros()
            ->with(['admin', 'formulario'])
            ->where('tipo', 'checklist')
            ->whereIn('periodicidade', ['trimestral', 'anual'])
            ->latest('data_hora')
            ->take(20)
            ->get();

        $alertasMaquina = $maquina->alertas()
            ->with(['item', 'operador', 'registro'])
            ->whereIn('status', ['pendente', 'em_analise'])
            ->latest()
            ->take(12)
            ->get();

        $alertasFinalizadosMaquina = $maquina->alertas()
            ->with(['item', 'operador', 'registro'])
            ->whereNotIn('status', ['pendente', 'em_analise'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $locacaoHistoricos = $maquina->locacaoHistoricos()
            ->with(['cliente', 'admin'])
            ->take(12)
            ->get();

        $cobrancasMaquina = $maquina->cobrancasFinanceirasQuery()
            ->orderByRaw("case when status = 'vencida' then 0 when status = 'aberta' then 1 when status = 'paga' then 2 else 3 end")
            ->orderBy('vencimento')
            ->take(8)
            ->get();

        $rastreamentoFiltros = $request->only(['ignicao_log']);
        $ultimasPosicoes = $rastreamentoHabilitado && $maquina->rastreador
            ? $maquina->rastreador->posicoes()
                ->when(in_array($rastreamentoFiltros['ignicao_log'] ?? null, ['ligada', 'desligada'], true), function ($query) use ($rastreamentoFiltros) {
                    $query->where('ignicao', ($rastreamentoFiltros['ignicao_log'] ?? null) === 'ligada');
                })
                ->take(12)
                ->get()
            : collect();
        $horimetroFiltros = $request->only(['inicio', 'fim']);
        $horimetroResumo = $rastreamentoHabilitado
            ? $horimetro->resumo($maquina, $horimetroFiltros)
            : null;

        $qrCodeSvg = $qrCode->svgForMaquina($maquina);
        $modelosChecklist = $this->modelosChecklist($maquina->empresa_id);
        $periodicidadesExtra = ChecklistPeriodicidade::labelsExtra();
        $periodicidadesAdicionar = collect(ChecklistPeriodicidade::labelsPadrao())
            ->reject(fn (string $label, string $periodicidade) => $formularios->has($periodicidade))
            ->map(fn (string $label) => $label.' (padrão)')
            ->merge(collect($periodicidadesExtra)->map(fn (string $label) => ucfirst($label).' (extra)'))
            ->all();

        $podeGerenciarRastreamento = $this->podeGerenciarRastreamento();
        $isComercial = (bool) auth('admin')->user()?->isComercial();

        return view('admin.maquinas.show', compact('maquina', 'checklists', 'checklistsExtras', 'registrosOperador', 'registrosTecnicos', 'alertasMaquina', 'alertasFinalizadosMaquina', 'locacaoHistoricos', 'cobrancasMaquina', 'ultimasPosicoes', 'qrCodeSvg', 'modelosChecklist', 'periodicidadesExtra', 'periodicidadesAdicionar', 'rastreamentoHabilitado', 'rastreamentoFiltros', 'horimetroFiltros', 'horimetroResumo', 'podeGerenciarRastreamento', 'isComercial'));
    }

    public function storeCobranca(Request $request, Maquina $maquina)
    {
        $this->abortUnlessPodeGerenciarCobranca($maquina);

        $data = $this->validarCobrancaMaquina($request, $maquina);
        SistemaCobranca::create($data);

        return redirect(route('admin.maquinas.show', $maquina).'#gestao-locacao')->with('status', 'Cobrança registrada.');
    }

    public function updateCobranca(Request $request, Maquina $maquina, SistemaCobranca $cobranca)
    {
        $this->abortUnlessPodeGerenciarCobranca($maquina);
        $this->abortUnlessCobrancaPertenceMaquina($maquina, $cobranca);

        $data = $request->validate([
            'status' => ['required', 'in:aberta,paga,vencida,cancelada'],
            'pago_em' => ['nullable', 'date'],
        ]);

        if ($data['status'] === 'paga' && empty($data['pago_em'])) {
            $data['pago_em'] = now()->toDateString();
        }

        if ($data['status'] !== 'paga') {
            $data['pago_em'] = null;
        }

        $cobranca->update($data);

        return redirect(route('admin.maquinas.show', $maquina).'#gestao-locacao');
    }

    public function destroyCobranca(Maquina $maquina, SistemaCobranca $cobranca)
    {
        $this->abortUnlessPodeGerenciarCobranca($maquina);
        $this->abortUnlessCobrancaPertenceMaquina($maquina, $cobranca);

        $cobranca->delete();

        return redirect(route('admin.maquinas.show', $maquina).'#gestao-locacao')->with('status', 'Cobrança removida.');
    }

    public function horimetroPdf(Request $request, Maquina $maquina, HorimetroService $horimetro, HorimetroPdfService $pdf)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless($this->rastreamentoHabilitado($maquina), 404);

        $maquina->load(['rastreador', 'locacaoCliente', 'empresa']);
        $resumo = $horimetro->resumo($maquina, $request->only(['inicio', 'fim']));

        return response($pdf->render($maquina, $resumo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($maquina).'"',
        ]);
    }

    public function horimetroDados(Request $request, Maquina $maquina, HorimetroService $horimetro)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless($this->rastreamentoHabilitado($maquina), 404);

        $maquina->load(['rastreador']);
        $resumo = $horimetro->resumo($maquina, $request->only(['inicio', 'fim']));

        return response()->json([
            'atualizado_em' => now('America/Sao_Paulo')->format('d/m/Y H:i:s'),
            'total_label' => $resumo['total_label'] ?? '0 min',
            'horimetro_inicial_label' => $resumo['horimetro_inicial_label'] ?? '0 min',
            'total_rastreado_label' => $resumo['total_rastreado_label'] ?? '0 min',
            'total_geral_label' => $resumo['total_geral_label'] ?? '0 min',
            'dias_count' => $resumo['dias']->count() ?? 0,
            'periodo_label' => ($resumo['inicio']?->format('d/m/Y') ?? '-').' até '.($resumo['fim']?->format('d/m/Y') ?? '-'),
            'status_atual' => $maquina->rastreador?->operacaoLabel() ?? 'Sem rastreador',
            'ultimo_sinal' => $maquina->rastreador?->ultimoSinalLocalLabel() ?? 'Sem sinal',
            'horimetro_hoje' => $maquina->rastreador?->horasTrabalhadasHojeLabel() ?? 'Sem rastreador',
            'dias' => ($resumo['dias'] ?? collect())->map(function (array $dia) {
                $eventos = collect($dia['eventos']);

                return [
                    'dia_label' => $dia['dia']->format('d/m/Y'),
                    'label' => $dia['label'],
                    'minutos' => $dia['minutos'],
                    'sessoes_count' => $eventos->count(),
                    'eventos' => $eventos->take(8)->map(fn (array $evento) => [
                        'label' => $evento['inicio']->format('H:i').' até '.$evento['fim']->format('H:i').' · '.$evento['label'],
                    ])->values(),
                    'eventos_restantes' => max($eventos->count() - 8, 0),
                ];
            })->values(),
        ]);
    }

    public function atualizarHorimetroInicial(Request $request, Maquina $maquina)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless($this->rastreamentoHabilitado($maquina), 404);

        $data = $request->validate([
            'horimetro_inicial_horas' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $maquina->update([
            'horimetro_inicial_horas' => $data['horimetro_inicial_horas'] ?? 0,
        ]);

        return redirect(route('admin.maquinas.show', $maquina).'#horimetro')
            ->with('status', 'Horímetro inicial atualizado.');
    }

    public function edit(Maquina $maquina)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        $maquina->load(['arquivos', 'rastreador']);

        return view('admin.maquinas.form', [
            'maquina' => $maquina,
            'modelosChecklist' => $this->modelosChecklist($maquina->empresa_id),
            'clientesLocacao' => $this->clientesLocacao($maquina->empresa_id),
            'rastreamentoHabilitado' => $this->rastreamentoHabilitado($maquina),
            'podeGerenciarRastreamento' => $this->podeGerenciarRastreamento(),
        ]);
    }

    public function editLocacao(Maquina $maquina)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        $maquina->load(['arquivos', 'locacaoCliente']);

        return view('admin.maquinas.locacao', [
            'maquina' => $maquina,
            'clientesLocacao' => $this->clientesLocacao($maquina->empresa_id),
        ]);
    }

    public function update(Request $request, Maquina $maquina)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        $data = $this->validated($request, false);
        $rastreadorData = $this->podeGerenciarRastreamento() ? $this->extrairRastreadorData($data) : [];
        $this->removerRastreadorData($data);
        unset($data['arquivos'], $data['contrato_arquivo'], $data['checklist_modelos']);

        $maquina->update($data);
        $maquina->refresh();

        if ($this->podeGerenciarRastreamento() && $this->rastreamentoHabilitado($maquina)) {
            $this->sincronizarRastreador($maquina, $rastreadorData);
        }

        $this->salvarArquivos($maquina, $request);

        return redirect()->route('admin.maquinas.index')->with('status', 'Máquina atualizada.');
    }

    public function updateLocacao(Request $request, Maquina $maquina)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        $data = $this->normalizarLocacao($this->validatedLocacao($request), $maquina->empresa_id);
        $locacaoAnterior = $this->dadosLocacao($maquina);

        $maquina->update($data);
        $maquina->refresh();

        if ($this->locacaoMudou($locacaoAnterior, $this->dadosLocacao($maquina))) {
            $this->registrarHistoricoLocacao($maquina, $locacaoAnterior, 'Atualização de locação.');
        }

        $this->salvarContrato($maquina, $request);

        return redirect(route('admin.maquinas.show', $maquina).'#gestao-locacao')->with('status', 'Locação atualizada.');
    }

    public function destroy(Maquina $maquina)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        $maquina->rastreador()->delete();
        $maquina->delete();

        return redirect()->route('admin.maquinas.index')->with('status', 'Máquina removida.');
    }

    public function qrCode(Maquina $maquina, QrCodeService $qrCode)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        return response($qrCode->svgForMaquina($maquina), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.$maquina->codigo_interno.'.svg"',
        ]);
    }

    public function resetChecklist(Maquina $maquina, string $periodicidade)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless(ChecklistPeriodicidade::isPadrao($periodicidade), 404);

        ChecklistReset::create([
            'maquina_id' => $maquina->id,
            'periodicidade' => $periodicidade,
            'admin_id' => auth('admin')->id(),
            'resetado_em' => now(),
        ]);

        return back()->with('status', 'Checklist '.$periodicidade.' liberado para novo registro.');
    }

    public function atribuirChecklistModelo(Request $request, Maquina $maquina, FormularioService $formularios, string $periodicidade)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless(ChecklistPeriodicidade::isPadrao($periodicidade), 404);

        $data = $request->validate([
            'modelo_id' => ['required', 'integer', 'exists:formularios,id'],
        ], [
            'modelo_id.required' => 'Selecione um modelo de checklist para aplicar.',
        ]);

        $modelo = $this->queryDaEmpresa(Formulario::query(), $maquina->empresa_id)
            ->where('tipo', 'modelo_checklist')
            ->with('itens')
            ->findOrFail($data['modelo_id']);

        DB::transaction(function () use ($maquina, $formularios, $modelo, $periodicidade) {
            $this->arquivarChecklistAtual($maquina, $periodicidade);
            $novoFormulario = $formularios->copiarModeloParaMaquina($modelo, $maquina, $periodicidade);

            if ($periodicidade === 'diario') {
                $maquina->update(['formulario_checklist_id' => $novoFormulario->id]);
            }
        });

        return back()->with('status', 'Checklist '.$periodicidade.' atualizado a partir do modelo.');
    }

    public function adicionarChecklistExtra(Request $request, Maquina $maquina, FormularioService $formularios)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);

        $data = $request->validate([
            'modelo_id' => ['required', 'integer', 'exists:formularios,id'],
            'periodicidade_extra' => ['required', 'string', 'in:'.implode(',', array_merge(ChecklistPeriodicidade::PADROES, array_keys(ChecklistPeriodicidade::labelsExtra())))],
        ], [
            'modelo_id.required' => 'Selecione um modelo de checklist para adicionar.',
            'periodicidade_extra.required' => 'Selecione a periodicidade do checklist.',
        ]);

        $modelo = $this->queryDaEmpresa(Formulario::query(), $maquina->empresa_id)
            ->where('tipo', 'modelo_checklist')
            ->with('itens')
            ->findOrFail($data['modelo_id']);

        $periodicidade = $data['periodicidade_extra'];

        if (ChecklistPeriodicidade::isPadrao($periodicidade)) {
            abort_if($maquina->formularioPorPeriodicidade($periodicidade), 422, 'Este checklist padrão já está configurado nesta máquina.');

            $novoFormulario = $formularios->copiarModeloParaMaquina($modelo, $maquina, $periodicidade);

            if ($periodicidade === 'diario') {
                $maquina->update(['formulario_checklist_id' => $novoFormulario->id]);
            }

            return back()->with('status', 'Checklist '.$periodicidade.' configurado novamente.');
        }

        $formularios->copiarModeloParaMaquina($modelo, $maquina, $periodicidade);

        return back()->with('status', 'Checklist extra adicionado.');
    }

    public function abrirArquivo(Maquina $maquina, MaquinaArquivo $arquivo)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless($arquivo->maquina_id === $maquina->id, 404);
        abort_if(auth('admin')->user()?->isComercial() && $arquivo->categoria !== 'contrato', 403);
        abort_unless(Storage::disk('public')->exists($arquivo->caminho_arquivo), 404);

        return Storage::disk('public')->response($arquivo->caminho_arquivo, $arquivo->nome_original);
    }

    public function destruirArquivo(Maquina $maquina, MaquinaArquivo $arquivo)
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_unless($arquivo->maquina_id === $maquina->id, 404);

        Storage::disk('public')->delete($arquivo->caminho_arquivo);
        $arquivo->delete();

        return back()->with('status', 'Arquivo removido.');
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'setor' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'codigo_interno' => ['nullable', 'string', 'max:255'],
            'horimetro_inicial_horas' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', 'in:ativa,inativa'],
            'art_seguranca_vencimento' => ['nullable', 'date'],
            'rastreador_imei' => ['nullable', 'string', 'max:30'],
            'rastreador_modelo' => ['nullable', 'string', 'max:255'],
            'rastreador_operadora' => ['nullable', 'string', 'max:255'],
            'rastreador_chip_numero' => ['nullable', 'string', 'max:255'],
            'rastreador_observacao' => ['nullable', 'string'],
            'checklist_modelos' => ['nullable', 'array'],
            'checklist_modelos.*' => ['nullable', 'integer', 'exists:formularios,id'],
            'arquivos' => ['nullable', 'array', 'max:10'],
            'arquivos.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ], [
            'arquivos.array' => 'Envie os arquivos da máquina em formato válido.',
            'arquivos.max' => 'Envie no máximo :max arquivos por vez.',
            'arquivos.*.file' => 'Um dos anexos enviados não é um arquivo válido.',
            'arquivos.*.mimes' => 'Os anexos da máquina devem ser imagens JPG, PNG, WEBP ou PDF.',
            'arquivos.*.max' => 'Cada arquivo da máquina pode ter no máximo 10 MB.',
            'contrato_arquivo.mimes' => 'O contrato deve ser PDF ou imagem.',
            'contrato_arquivo.max' => 'O contrato pode ter no máximo 15 MB.',
        ]);
    }

    private function validatedLocacao(Request $request): array
    {
        return $request->validate([
            'locacao_status' => ['required', 'in:disponivel,locada,em_transporte,devolvida,manutencao'],
            'locacao_cliente_id' => ['nullable', 'integer', 'exists:locacao_clientes,id'],
            'locacao_cliente' => ['nullable', 'string', 'max:255'],
            'locacao_local' => ['nullable', 'string', 'max:255'],
            'contrato_inicio' => ['nullable', 'date'],
            'contrato_fim' => ['nullable', 'date', 'after_or_equal:contrato_inicio'],
            'devolucao_prevista' => ['nullable', 'date'],
            'devolucao_realizada' => ['nullable', 'date'],
            'frete_responsavel' => ['nullable', 'string', 'max:255'],
            'frete_tipo' => ['nullable', 'string', 'max:255'],
            'frete_observacao' => ['nullable', 'string'],
            'contrato_arquivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
        ], [
        ]);
    }

    private function extrairRastreadorData(array &$data): array
    {
        $campos = [
            'rastreador_imei' => 'imei',
            'rastreador_modelo' => 'modelo',
            'rastreador_operadora' => 'operadora',
            'rastreador_chip_numero' => 'chip_numero',
            'rastreador_observacao' => 'observacao',
        ];

        $rastreador = [];

        foreach ($campos as $campoForm => $campoBanco) {
            $rastreador[$campoBanco] = isset($data[$campoForm]) ? trim((string) $data[$campoForm]) : null;
            unset($data[$campoForm]);
        }

        return $rastreador;
    }

    private function removerRastreadorData(array &$data): void
    {
        foreach ([
            'rastreador_imei',
            'rastreador_modelo',
            'rastreador_operadora',
            'rastreador_chip_numero',
            'rastreador_observacao',
        ] as $campo) {
            unset($data[$campo]);
        }
    }

    private function sincronizarRastreador(Maquina $maquina, array $data): void
    {
        $data['imei'] = $this->normalizarImei($data['imei'] ?? null);
        $temDados = collect($data)->filter(fn ($valor) => filled($valor))->isNotEmpty();
        $imei = $data['imei'];
        $rastreador = $maquina->rastreador;

        if ($imei) {
            $existente = RastreadorDispositivo::withTrashed()->where('imei', $imei)->first();

            if ($existente && (! $rastreador || $existente->id !== $rastreador->id)) {
                if (! $existente->trashed() && $existente->maquina_id && (int) $existente->maquina_id !== (int) $maquina->id) {
                    $maquinaVinculada = Maquina::query()->find($existente->maquina_id);

                    throw ValidationException::withMessages([
                        'rastreador_imei' => 'Este IMEI já está vinculado à máquina '.($maquinaVinculada?->nome ?: '#'.$existente->maquina_id).'.',
                    ]);
                }

                if ($rastreador && $rastreador->exists) {
                    $rastreador->forceFill([
                        'empresa_id' => null,
                        'maquina_id' => null,
                        'status' => 'aguardando_vinculo',
                    ])->save();
                }

                $rastreador = $existente;
            }
        }

        $rastreador ??= new RastreadorDispositivo();

        if (! $temDados && ! $rastreador->exists) {
            return;
        }

        if (method_exists($rastreador, 'trashed') && $rastreador->trashed()) {
            $rastreador->restore();
        }

        $rastreador->fill([
            'empresa_id' => $maquina->empresa_id,
            'maquina_id' => $maquina->id,
            'imei' => $imei,
            'modelo' => $data['modelo'] ?: 'Queclink GV50CG',
            'provedor' => 'flespi',
            'operadora' => $data['operadora'] ?: null,
            'chip_numero' => $data['chip_numero'] ?: null,
            'observacao' => $data['observacao'] ?: null,
            'status' => $rastreador->ultimo_sinal_em ? 'ativo' : 'aguardando',
        ]);

        $rastreador->save();
        $maquina->setRelation('rastreador', $rastreador);
    }

    private function normalizarImei(?string $imei): ?string
    {
        $imei = preg_replace('/\D+/', '', (string) $imei);

        return $imei !== '' ? $imei : null;
    }

    private function rastreamentoHabilitado(?Maquina $maquina = null): bool
    {
        if ($maquina?->relationLoaded('empresa') || $maquina?->empresa) {
            return (bool) $maquina->empresa?->rastreamentoHabilitado();
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return (bool) $this->empresaAtual()?->rastreamentoHabilitado();
    }

    private function podeGerenciarRastreamento(): bool
    {
        return $this->isSuperAdmin();
    }

    private function abortUnlessPodeGerenciarCobranca(Maquina $maquina): void
    {
        $this->abortUnlessPodeAcessarMaquina($maquina);
        abort_if(auth('admin')->user()?->isComercial(), 403);
    }

    private function abortUnlessCobrancaPertenceMaquina(Maquina $maquina, SistemaCobranca $cobranca): void
    {
        abort_unless(
            (int) $cobranca->maquina_id === (int) $maquina->id
            || in_array($cobranca->cliente_nome, $maquina->cobrancasFinanceirasQuery()->pluck('cliente_nome')->all(), true),
            404
        );
    }

    private function validarCobrancaMaquina(Request $request, Maquina $maquina): array
    {
        $clientePadrao = $maquina->locacaoCliente?->nome
            ?: $maquina->locacao_cliente
            ?: $maquina->empresa?->nome
            ?: $maquina->nome;

        $data = $request->validate([
            'cliente_nome' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'referencia_mes' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'vencimento' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', 'in:aberta,paga,vencida,cancelada'],
            'forma_pagamento' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ], [
            'vencimento.required' => 'Informe a data de vencimento da cobrança.',
        ]);

        $data['maquina_id'] = $maquina->id;
        $data['cliente_nome'] = $data['cliente_nome'] ?: $clientePadrao;
        $data['referencia_mes'] = ! empty($data['referencia_mes']) ? $data['referencia_mes'].'-01' : null;
        $data['pago_em'] = ($data['status'] ?? null) === 'paga' ? now()->toDateString() : null;

        return $data;
    }

    private function abortUnlessPodeAcessarMaquina(Maquina $maquina): void
    {
        if ($this->isSuperAdmin() && $maquina->empresa_id) {
            return;
        }

        $this->abortUnlessEmpresa($maquina->empresa_id);
    }

    private function queryDaEmpresa($query, ?int $empresaId)
    {
        return $empresaId ? $query->where('empresa_id', $empresaId) : $this->scopeEmpresa($query);
    }

    private function salvarArquivos(Maquina $maquina, Request $request): void
    {
        foreach ($request->file('arquivos', []) as $arquivo) {
            if (! $arquivo->isValid()) {
                continue;
            }

            $path = $arquivo->store('maquinas/'.$maquina->id.'/arquivos', 'public');

            $maquina->arquivos()->create([
                'categoria' => 'arquivo',
                'nome_original' => $arquivo->getClientOriginalName(),
                'caminho_arquivo' => $path,
                'mime_type' => $arquivo->getMimeType() ?: 'application/octet-stream',
                'tamanho' => $arquivo->getSize() ?: 0,
            ]);
        }
    }

    private function salvarContrato(Maquina $maquina, Request $request): void
    {
        $arquivo = $request->file('contrato_arquivo');

        if (! $arquivo || ! $arquivo->isValid()) {
            return;
        }

        $path = $arquivo->store('maquinas/'.$maquina->id.'/contratos', 'public');

        $maquina->arquivos()->create([
            'categoria' => 'contrato',
            'nome_original' => $arquivo->getClientOriginalName(),
            'caminho_arquivo' => $path,
            'mime_type' => $arquivo->getMimeType() ?: 'application/octet-stream',
            'tamanho' => $arquivo->getSize() ?: 0,
        ]);
    }

    private function normalizarLocacao(array $data, ?int $empresaId = null): array
    {
        $clienteId = $data['locacao_cliente_id'] ?? null;
        $empresaId ??= $this->empresaAtualId();

        if ($clienteId) {
            $cliente = LocacaoCliente::query()
                ->where('empresa_id', $empresaId)
                ->findOrFail($clienteId);
            $data['locacao_cliente'] = $cliente->nome;
        } else {
            $data['locacao_cliente_id'] = null;
            $data['locacao_cliente'] = null;
        }

        return $data;
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

    private function locacaoMudou(array $antes, array $depois): bool
    {
        foreach ($antes as $campo => $valor) {
            if ((string) ($valor ?? '') !== (string) ($depois[$campo] ?? '')) {
                return true;
            }
        }

        return false;
    }

    private function registrarHistoricoLocacao(Maquina $maquina, ?array $anteriores, string $observacao): void
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
            'observacao' => $observacao,
        ]);
    }

    private function clientesLocacao(?int $empresaId = null)
    {
        $query = LocacaoCliente::query();
        $empresaId ? $query->where('empresa_id', $empresaId) : $this->scopeEmpresa($query);

        return $query
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();
    }

    private function modelosChecklist(?int $empresaId = null)
    {
        $empresaId ??= $this->empresaAtualId();

        if (! $empresaId) {
            return collect();
        }

        app(FormularioService::class)->garantirModelosPadrao($empresaId);

        return Formulario::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', 'modelo_checklist')
            ->withCount('itens')
            ->orderByRaw("CASE periodicidade WHEN 'diario' THEN 1 WHEN 'mensal' THEN 2 WHEN 'trimestral' THEN 3 WHEN 'anual' THEN 4 ELSE 5 END")
            ->orderBy('nome')
            ->get();
    }

    private function sincronizarChecklists(Maquina $maquina, FormularioService $formularios, array $modelos, bool $criando): void
    {
        foreach (ChecklistPeriodicidade::PADROES as $periodicidade) {
            $modelo = null;
            $modeloId = $modelos[$periodicidade] ?? null;

            if ($modeloId) {
                $modelo = $this->queryDaEmpresa(Formulario::query(), $maquina->empresa_id)
                    ->where('tipo', 'modelo_checklist')
                    ->with('itens')
                    ->findOrFail($modeloId);
            } elseif ($criando) {
                $modelo = $formularios->modeloChecklistPadrao($maquina->empresa_id, $periodicidade);
            }

            if (! $modelo) {
                continue;
            }

            $this->arquivarChecklistAtual($maquina, $periodicidade);
            $novoFormulario = $formularios->copiarModeloParaMaquina($modelo, $maquina, $periodicidade);

            if ($periodicidade === 'diario') {
                $maquina->update(['formulario_checklist_id' => $novoFormulario->id]);
            }
        }
    }

    private function arquivarChecklistAtual(Maquina $maquina, string $periodicidade): void
    {
        $maquina->formularios()
            ->where('tipo', 'checklist_maquina')
            ->where('periodicidade', $periodicidade)
            ->get()
            ->each(function (Formulario $formulario) {
                $formulario->update([
                    'periodicidade' => null,
                    'nome' => str_contains($formulario->nome, '(substituído)')
                        ? $formulario->nome
                        : $formulario->nome.' (substituído)',
                ]);
            });
    }

    private function tempoRestanteChecklist($agora, $fim): string
    {
        if ($fim->lessThanOrEqualTo($agora)) {
            return 'menos de 1 hora';
        }

        $minutos = $agora->diffInMinutes($fim);

        if ($minutos < 60) {
            return 'menos de 1 hora';
        }

        $horas = (int) ceil($minutos / 60);

        if ($horas < 48) {
            return $horas.' '.($horas === 1 ? 'hora' : 'horas');
        }

        $dias = (int) ceil($horas / 24);

        return $dias.' '.($dias === 1 ? 'dia' : 'dias');
    }
}
