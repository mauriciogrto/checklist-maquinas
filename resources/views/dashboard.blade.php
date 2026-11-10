@extends('layouts.admin')

@section('content')
@php
    $statusLabels = [
        'pendente' => 'Pendente',
        'em_analise' => 'Em análise',
        'resolvido' => 'Resolvido',
        'falso_positivo' => 'Ignorar',
    ];

    $checklistsAbertosTotal = collect($checklistsOperacionais)->sum('abertos');
    $checklistsEsperadosTotal = collect($checklistsOperacionais)->sum('total');
    $checklistsGraficoBase = max($checklistsEsperadosTotal, 1);
    $checklistsConcluidosTotal = collect($checklistsOperacionais)->sum('concluidos');
    $checklistsPercentual = round(($checklistsConcluidosTotal / $checklistsGraficoBase) * 100);
    $contratosPercentual = $contratosResumo['total']
        ? round(($contratosResumo['com_contrato'] / $contratosResumo['total']) * 100)
        : 0;
    $contratosGraficoMaximo = max(
        $contratosResumo['locadas'],
        $contratosResumo['contratos_vencendo'],
        $contratosResumo['nao_devolvidas'],
        $contratosResumo['devolucoes_previstas'],
        1
    );
    $graficoMaximo = max(
        $pendentes,
        $contratosResumo['locadas'],
        $contratosResumo['contratos_vencendo'],
        $contratosResumo['nao_devolvidas'],
        $checklistsAbertosTotal,
        1
    );
    $rastreamentoGraficoMaximo = $rastreamentoHabilitado
        ? max(
            $rastreamentoResumo['online'],
            $rastreamentoResumo['em_movimento'],
            $rastreamentoResumo['sem_comunicacao'],
            $rastreamentoResumo['ligadas'],
            $rastreamentoResumo['sem_alimentacao'],
            1
        )
        : 1;
@endphp
<style>
    .ops-dashboard {
        display: grid;
        gap: 16px;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }

    .ops-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: start;
        min-width: 0;
    }

    .ops-hero h1 {
        max-width: 760px;
        font-size: clamp(22px, 2vw, 28px);
    }

    .ops-hero p {
        max-width: 760px;
    }

    .ops-dashboard .section-head {
        align-items: flex-start;
        gap: 12px;
    }

    .ops-dashboard .section-head > div {
        min-width: 0;
    }

    .ops-dashboard .section-head .btn {
        align-self: flex-start;
        flex: 0 0 auto;
        margin-top: 0;
    }

    .ops-date {
        min-width: 154px;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
        color: var(--text-dim);
        font-size: 12px;
        font-weight: 600;
        text-align: right;
    }

    .ops-date strong {
        display: block;
        color: var(--text);
        font-size: 15px;
        line-height: 1.15;
    }

    .ops-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 178px), 1fr));
        gap: 12px;
        min-width: 0;
    }

    .ops-cards.tracking-cards {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }

    .ops-card {
        min-width: 0;
        min-height: 112px;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        grid-template-rows: auto 1fr auto;
        align-content: start;
        align-items: start;
        column-gap: 9px;
        row-gap: 10px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .ops-card strong {
        display: block;
        grid-column: 1 / -1;
        color: var(--text);
        font-size: 30px;
        line-height: 1;
        align-self: end;
        margin-top: 2px;
    }

    .ops-card span {
        display: block;
        grid-column: 2;
        min-height: 36px;
        color: var(--text-dim);
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
    }

    .ops-card small {
        grid-column: 1 / -1;
        color: var(--text-faint);
        font-size: 12px;
        line-height: 1.35;
    }

    .ops-card.danger {
        border-color: rgba(239, 68, 68, .28);
        background: #ffffff;
    }

    .ops-card.warning {
        border-color: rgba(245, 158, 11, .32);
        background: #ffffff;
    }

    .ops-card.success {
        border-color: rgba(20, 184, 166, .28);
        background: #ffffff;
    }

    .ops-card::before {
        content: "";
        grid-column: 1;
        margin-top: 5px;
        width: 8px;
        height: 8px;
        border-radius: 99px;
        background: #94a3b8;
    }

    .ops-card.danger::before {
        background: #ef4444;
    }

    .ops-card.warning::before {
        background: #f59e0b;
    }

    .ops-card.success::before {
        background: #14b8a6;
    }

    .ops-layout {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
        gap: 16px;
        align-items: start;
        min-width: 0;
    }

    .ops-layout.tracking-layout {
        grid-template-columns: minmax(0, 1.08fr) minmax(0, .98fr) minmax(0, .9fr);
        align-items: stretch;
    }

    .ops-layout.tracking-layout > .panel {
        height: 100%;
    }

    .ops-stack {
        display: grid;
        gap: 16px;
        min-width: 0;
    }

    .chart-panel {
        display: grid;
        gap: 18px;
        min-width: 0;
    }

    .bar-list {
        display: grid;
        gap: 13px;
    }

    .bar-row {
        display: grid;
        gap: 7px;
    }

    .bar-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        color: var(--text-dim);
        font-size: 13px;
        font-weight: 600;
    }

    .bar-head strong {
        color: var(--text);
    }

    .bar-track {
        height: 12px;
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: 999px;
        background: #f3f4f6;
    }

    .bar-track i {
        display: block;
        height: 100%;
        min-width: 6px;
        border-radius: inherit;
    }

    .ring-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 132px), 1fr));
        gap: 12px;
        min-width: 0;
    }

    .ring-card {
        display: grid;
        gap: 10px;
        justify-items: center;
        min-width: 0;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
        text-align: center;
    }

    .ring {
        position: relative;
        width: min(100%, 132px);
        aspect-ratio: 1;
        border-radius: 50%;
        box-shadow: inset 0 0 0 1px var(--border);
    }

    .ring::before {
        content: "";
        position: absolute;
        inset: 20%;
        border-radius: 50%;
        background: #ffffff;
        border: 1px solid var(--border);
    }

    .ring strong {
        position: absolute;
        inset: 0;
        z-index: 1;
        display: grid;
        place-items: center;
        color: var(--text);
        font-size: 28px;
        line-height: 1;
    }

    .ring-card span {
        max-width: 100%;
        color: var(--text-dim);
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .checklist-mini {
        display: grid;
        gap: 11px;
        margin-top: 4px;
    }

    .checklist-mini-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
        color: var(--text-dim);
        font-size: 12px;
        font-weight: 600;
    }

    .checklist-mini-row strong {
        color: var(--text);
        font-size: 13px;
    }

    .attention-list {
        display: grid;
        gap: 10px;
    }

    .attention-item {
        display: grid;
        grid-template-columns: minmax(160px, 1fr) minmax(170px, 1fr) minmax(150px, 0.85fr) minmax(132px, 0.75fr) auto;
        gap: 12px;
        align-items: center;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .attention-item strong,
    .attention-item span {
        min-width: 0;
    }

    .attention-item small,
    .attention-empty {
        display: block;
        margin-top: 5px;
        color: var(--text-faint);
        line-height: 1.35;
    }

    .attention-label {
        display: block;
        margin-bottom: 4px;
        color: var(--text-faint);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .alert-list {
        display: grid;
        gap: 10px;
    }

    .alert-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .alert-item strong,
    .alert-item span {
        display: block;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .alert-item .btn {
        grid-column: 1 / -1;
        width: fit-content;
    }

    .alert-item span {
        color: var(--text-dim);
        font-size: 12px;
        line-height: 1.35;
    }

    .tracker-list {
        display: grid;
        gap: 10px;
    }

    .tracker-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        min-width: 0;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .tracker-item strong,
    .tracker-item span,
    .tracker-item small {
        display: block;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .tracker-item span {
        color: var(--text-dim);
        font-size: 13px;
        font-weight: 600;
    }

    .tracker-item small {
        color: var(--text-faint);
        font-size: 12px;
        line-height: 1.35;
    }

    .tracker-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    @media (max-width: 1180px) {
        .ops-layout {
            grid-template-columns: 1fr;
        }

        .ops-cards.tracking-cards {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .ops-layout.tracking-layout {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .attention-item {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .attention-actions {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 780px) {
        .ops-hero {
            grid-template-columns: 1fr;
        }

        .ops-cards.tracking-cards {
            grid-template-columns: 1fr;
        }

        .ops-layout.tracking-layout {
            grid-template-columns: 1fr;
        }

        .ops-date {
            text-align: left;
        }

        .alert-item {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .alert-item .btn {
            width: 100%;
        }

        .attention-item {
            grid-template-columns: 1fr;
        }

        .attention-actions .btn {
            width: 100%;
        }

        .tracker-item {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="ops-dashboard">
    <section class="panel">
        <div class="ops-hero">
            <div>
                <h1>{{ $rastreamentoHabilitado ? 'Painel de rastreamento' : 'Painel operacional' }}</h1>
                <p>
                    @if($rastreamentoHabilitado)
                        Visão rápida de máquinas online, em movimento, sem comunicação e horas de uso.
                    @else
                        Acompanhamento direto de alertas, locações, contratos, devoluções e checklists que ainda precisam de ação.
                    @endif
                </p>
            </div>
            <div class="ops-date">
                <strong>{{ now('America/Sao_Paulo')->format('d/m/Y H:i') }}</strong>
                Horário de São Paulo
            </div>
        </div>
    </section>

    <section class="ops-cards {{ $rastreamentoHabilitado ? 'tracking-cards' : '' }}" aria-label="Indicadores operacionais">
        @if($rastreamentoHabilitado)
            <a class="ops-card warning" href="{{ route('admin.rastreamento.mapa') }}">
                <span>Máquinas em movimento</span>
                <strong>{{ $rastreamentoResumo['em_movimento'] }}</strong>
                <small>Ignição/fio branco ativo no último sinal.</small>
            </a>
            <a class="ops-card success" href="{{ route('admin.maquinas.index', ['rastreador' => 'online']) }}">
                <span>Máquinas online</span>
                <strong>{{ $rastreamentoResumo['online'] }}</strong>
                <small>{{ $rastreamentoResumo['com_rastreador'] }} rastreadores vinculados.</small>
            </a>
            <a class="ops-card danger" href="{{ route('admin.maquinas.index', ['rastreador' => 'offline']) }}">
                <span>Sem comunicação</span>
                <strong>{{ $rastreamentoResumo['sem_comunicacao'] }}</strong>
                <small>Sem sinal recente ou aguardando primeiro pacote.</small>
            </a>
            <a class="ops-card success" href="{{ route('admin.maquinas.index', ['rastreador' => 'ligada']) }}">
                <span>Com alimentação</span>
                <strong>{{ $rastreamentoResumo['ligadas'] }}</strong>
                <small>Alimentação externa presente no último sinal.</small>
            </a>
            <a class="ops-card warning" href="{{ route('admin.maquinas.index', ['rastreador' => 'sem_alimentacao']) }}">
                <span>Sem alimentação externa</span>
                <strong>{{ $rastreamentoResumo['sem_alimentacao'] }}</strong>
                <small>Rastreador online usando a bateria interna.</small>
            </a>
            <a class="ops-card" href="{{ route('admin.maquinas.index') }}">
                <span>Horas trabalhadas hoje</span>
                <strong>{{ $rastreamentoResumo['horas_hoje_label'] }}</strong>
                <small>Estimativa por ignição e posições recebidas.</small>
            </a>
        @else
            <a class="ops-card danger" href="{{ route('admin.alertas.index') }}">
                <span>Alertas em aberto</span>
                <strong id="pendentes">{{ $pendentes }}</strong>
                <small>Pendentes ou em análise.</small>
            </a>
            <a class="ops-card" href="{{ route('admin.maquinas.index') }}">
                <span>Máquinas locadas</span>
                <strong>{{ $contratosResumo['locadas'] }}</strong>
                <small>Equipamentos em operação externa.</small>
            </a>
            <a class="ops-card warning" href="{{ route('admin.maquinas.index') }}">
                <span>Contratos vencem em 15 dias</span>
                <strong>{{ $contratosResumo['contratos_vencendo'] }}</strong>
                <small>Contratos ativos próximos do término.</small>
            </a>
            <a class="ops-card danger" href="{{ route('admin.maquinas.index') }}">
                <span>Não devolvidas</span>
                <strong>{{ $contratosResumo['nao_devolvidas'] }}</strong>
                <small>Locadas ou em transporte sem devolução registrada.</small>
            </a>
            <a class="ops-card warning" href="{{ route('admin.historico.index') }}">
                <span>Checklists em aberto</span>
                <strong>{{ $checklistsAbertosTotal }}</strong>
                <small>Somando hoje, mês e ano.</small>
            </a>
        @endif
    </section>

    @if($rastreamentoHabilitado)
        <div class="ops-layout tracking-layout">
            <section class="panel chart-panel">
                <div class="section-head">
                    <div>
                        <h2>Status dos rastreadores</h2>
                        <p>Leitura operacional para identificar máquinas ativas, paradas e sem comunicação.</p>
                    </div>
                    <a class="btn small" href="{{ route('admin.rastreamento.mapa') }}">Abrir mapa</a>
                </div>
                <div class="bar-list">
                    @foreach([
                        ['label' => 'Online', 'total' => $rastreamentoResumo['online'], 'color' => '#14b8a6'],
                        ['label' => 'Em movimento', 'total' => $rastreamentoResumo['em_movimento'], 'color' => '#f59e0b'],
                        ['label' => 'Com alimentação', 'total' => $rastreamentoResumo['ligadas'], 'color' => '#22c55e'],
                        ['label' => 'Sem alimentação externa', 'total' => $rastreamentoResumo['sem_alimentacao'], 'color' => '#f59e0b'],
                        ['label' => 'Sem comunicação', 'total' => $rastreamentoResumo['sem_comunicacao'], 'color' => '#ef4444'],
                        ['label' => 'Sem posição GPS', 'total' => $rastreamentoResumo['sem_posicao'], 'color' => '#94a3b8'],
                    ] as $item)
                        <div class="bar-row">
                            <div class="bar-head">
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['total'] }}</strong>
                            </div>
                            <div class="bar-track" aria-label="{{ $item['label'] }}: {{ $item['total'] }}">
                                <i style="width: {{ max(round(($item['total'] / $rastreamentoGraficoMaximo) * 100), $item['total'] ? 2 : 0) }}%; background: {{ $item['color'] }};"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel chart-panel">
                <div class="section-head">
                    <div>
                        <h2>Locação e contratos</h2>
                        <p>Contratos ativos, vencimentos e devoluções que impactam a operação.</p>
                    </div>
                </div>
                <div class="bar-list">
                    @foreach([
                        ['label' => 'Máquinas locadas', 'total' => $contratosResumo['locadas'], 'color' => '#14b8a6'],
                        ['label' => 'Contratos vencendo', 'total' => $contratosResumo['contratos_vencendo'], 'color' => '#f59e0b'],
                        ['label' => 'Devoluções previstas', 'total' => $contratosResumo['devolucoes_previstas'], 'color' => '#38bdf8'],
                        ['label' => 'Não devolvidas', 'total' => $contratosResumo['nao_devolvidas'], 'color' => '#dc2626'],
                    ] as $item)
                        <div class="bar-row">
                            <div class="bar-head">
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['total'] }}</strong>
                            </div>
                            <div class="bar-track" aria-label="{{ $item['label'] }}: {{ $item['total'] }}">
                                <i style="width: {{ max(round(($item['total'] / $contratosGraficoMaximo) * 100), $item['total'] ? 2 : 0) }}%; background: {{ $item['color'] }};"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="section-head">
                    <div>
                        <h2>Rotina de checklists</h2>
                        <p>Resumo compacto dos ciclos atuais.</p>
                    </div>
                    <a class="btn small" href="{{ route('admin.historico.index') }}">Histórico</a>
                </div>
                <div class="ring-grid">
                    <div class="ring-card">
                        <div class="ring" style="background: conic-gradient(var(--accent) 0 {{ $checklistsPercentual }}%, #edf0f5 {{ $checklistsPercentual }}% 100%);">
                            <strong>{{ $checklistsPercentual }}%</strong>
                        </div>
                        <span>{{ $checklistsConcluidosTotal }} concluídos de {{ $checklistsEsperadosTotal }}</span>
                    </div>
                </div>
                <div class="checklist-mini">
                    @foreach($checklistsOperacionais as $item)
                        <div class="checklist-mini-row">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['abertos'] }} abertos</strong>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    @else
        <div class="ops-layout">
            <div class="ops-stack">
                <section class="panel chart-panel">
                    <div class="section-head">
                        <div>
                            <h2>Checklists em aberto</h2>
                            <p>Comparativo por ciclo operacional: diário, mensal e anual.</p>
                        </div>
                        <a class="btn small" href="{{ route('admin.historico.index') }}">Ver histórico</a>
                    </div>
                    <div class="bar-list">
                        @foreach($checklistsOperacionais as $item)
                            <div class="bar-row">
                                <div class="bar-head">
                                    <span>{{ $item['label'] }}</span>
                                    <strong>{{ $item['abertos'] }} abertos / {{ $item['total'] }} esperados</strong>
                                </div>
                                <div class="bar-track" aria-label="{{ $item['label'] }}: {{ $item['abertos'] }} checklists em aberto">
                                    <i style="width: {{ $item['total'] ? max(round(($item['abertos'] / $item['total']) * 100), 2) : 0 }}%; background: {{ $item['cor'] }};"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="panel chart-panel">
                    <div class="section-head">
                        <div>
                            <h2>Locação e contratos</h2>
                            <p>Leitura rápida dos principais pontos de atenção.</p>
                        </div>
                    </div>
                    <div class="bar-list">
                        @foreach([
                            ['label' => 'Alertas em aberto', 'total' => $pendentes, 'color' => '#ef4444'],
                            ['label' => 'Máquinas locadas', 'total' => $contratosResumo['locadas'], 'color' => '#14b8a6'],
                            ['label' => 'Contratos vencendo', 'total' => $contratosResumo['contratos_vencendo'], 'color' => '#f59e0b'],
                            ['label' => 'Não devolvidas', 'total' => $contratosResumo['nao_devolvidas'], 'color' => '#dc2626'],
                        ] as $item)
                            <div class="bar-row">
                                <div class="bar-head">
                                    <span>{{ $item['label'] }}</span>
                                    <strong>{{ $item['total'] }}</strong>
                                </div>
                                <div class="bar-track" aria-label="{{ $item['label'] }}: {{ $item['total'] }}">
                                    <i style="width: {{ max(round(($item['total'] / $graficoMaximo) * 100), $item['total'] ? 2 : 0) }}%; background: {{ $item['color'] }};"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="ops-stack">
                <section class="panel">
                    <div class="section-head">
                        <div>
                            <h2>Execução de checklists</h2>
                            <p>Percentual concluído dentro dos ciclos atuais.</p>
                        </div>
                    </div>
                    <div class="ring-grid">
                        <div class="ring-card">
                            <div class="ring" style="background: conic-gradient(var(--accent) 0 {{ $checklistsPercentual }}%, #edf0f5 {{ $checklistsPercentual }}% 100%);">
                                <strong>{{ $checklistsPercentual }}%</strong>
                            </div>
                            <span>{{ $checklistsConcluidosTotal }} concluídos de {{ $checklistsEsperadosTotal }}</span>
                        </div>
                        <div class="ring-card">
                            <div class="ring" style="background: conic-gradient(#14b8a6 0 {{ $contratosPercentual }}%, #edf0f5 {{ $contratosPercentual }}% 100%);">
                                <strong>{{ $contratosPercentual }}%</strong>
                            </div>
                            <span>{{ $contratosResumo['com_contrato'] }} contratos anexados de {{ $contratosResumo['total'] }}</span>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    @endif
</div>

<script>
const pendentesEl = document.getElementById('pendentes');
if (pendentesEl) {
    setInterval(async () => {
        const response = await fetch('{{ route('admin.dashboard.alertas-json') }}');
        const data = await response.json();
        pendentesEl.textContent = data.pendentes;
    }, 15000);
}
</script>
@endsection
