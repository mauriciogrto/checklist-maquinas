@extends('layouts.admin')

@section('content')
@php
    $filtros = $filtros ?? [];
@endphp

<style>
    .machine-card-list {
        display: grid;
        gap: 12px;
    }

    .machine-card {
        display: grid;
        grid-template-columns: minmax(220px, 1.2fr) minmax(190px, .95fr) minmax(230px, 1fr) minmax(220px, 1fr) minmax(98px, auto);
        gap: 14px;
        align-items: stretch;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
    }

    .machine-card-section {
        display: grid;
        align-content: start;
        gap: 8px;
        min-width: 0;
        padding-right: 14px;
        border-right: 1px solid rgba(226, 232, 240, .82);
    }

    .machine-card-section:last-child {
        padding-right: 0;
        border-right: 0;
    }

    .machine-card-label {
        color: var(--text-faint);
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        text-transform: uppercase;
    }

    .machine-title {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .machine-title strong,
    .machine-meta strong {
        display: block;
        overflow-wrap: anywhere;
        color: var(--text);
    }

    .machine-title small,
    .machine-meta small {
        display: block;
        color: var(--text-dim);
        font-size: 12px;
        line-height: 1.35;
    }

    .machine-detail-line {
        display: block;
        color: var(--text-dim);
        font-size: 12px;
        line-height: 1.35;
    }

    .machine-detail-line strong {
        display: inline;
        color: var(--text);
        font-weight: 700;
    }

    .machine-meta {
        display: grid;
        gap: 8px;
        min-width: 0;
    }

    .machine-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .machine-info-list {
        display: grid;
        gap: 7px;
    }

    .machine-status-line {
        display: grid;
        grid-template-columns: 9px minmax(0, 1fr);
        gap: 8px;
        align-items: center;
        min-width: 0;
        color: var(--text);
        font-size: 13px;
        line-height: 1.35;
    }

    .machine-status-line strong {
        display: inline;
        font-weight: 700;
    }

    .machine-status-line span {
        overflow-wrap: anywhere;
    }

    .machine-dot {
        width: 8px;
        height: 8px;
        border-radius: 99px;
        background: #94a3b8;
    }

    .machine-dot.status-conforme {
        background: #14b8a6;
    }

    .machine-dot.warning,
    .machine-dot.alert-status-em-analise {
        background: #f59e0b;
    }

    .machine-dot.danger,
    .machine-dot.status-com-pendencia {
        background: #ef4444;
    }

    .machine-note {
        color: var(--text-dim);
        font-size: 12px;
        line-height: 1.35;
    }

    .machine-actions {
        display: grid;
        gap: 6px;
        justify-items: stretch;
        align-content: start;
    }

    .machine-actions .btn,
    .machine-actions form {
        width: 100%;
    }

    .machine-actions .btn.small,
    .machine-meta .btn.small {
        justify-content: center;
        min-height: 32px;
        padding: 7px 9px;
        border-radius: 6px;
        font-weight: 600;
        white-space: normal;
    }

    .machine-actions .btn.small:not(.danger):not(.primary),
    .machine-meta .btn.small {
        background: #ffffff;
        color: var(--text);
    }

    .machine-actions .btn.small.primary {
        background: rgba(20, 184, 166, .12);
        color: #0f766e;
        border-color: rgba(20, 184, 166, .28);
    }

    .machine-empty {
        padding: 18px;
        border: 1px dashed var(--border-strong);
        border-radius: var(--radius-md);
        color: var(--text-dim);
        background: #fff;
    }

    @media (max-width: 1180px) {
        .machine-card {
            grid-template-columns: minmax(240px, 1.2fr) minmax(210px, 1fr) minmax(210px, 1fr);
        }

        .machine-card-section:nth-child(3) {
            border-right: 0;
            padding-right: 0;
        }

        .machine-card-section.actions-section {
            grid-column: 1 / -1;
            padding-right: 0;
            border-right: 0;
        }

        .machine-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .machine-card {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 12px;
        }

        .machine-card-section {
            padding-right: 0;
            padding-bottom: 12px;
            border-right: 0;
            border-bottom: 1px solid rgba(226, 232, 240, .82);
        }

        .machine-card-section:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .machine-actions {
            grid-template-columns: 1fr;
        }
    }

</style>

<div class="top top-filter">
    <div class="title-copy">
        <h1>Acompanhamento de máquinas</h1>
        @unless($isComercial ?? false)
            <a class="btn primary" href="{{ route('admin.maquinas.create') }}">Nova máquina</a>
        @endunless
    </div>
    <form class="top-filter-form" method="get" action="{{ route('admin.maquinas.index') }}">
        <label>Máquina
            <input name="maquina" value="{{ $filtros['maquina'] ?? '' }}" placeholder="Nome, código ou modelo">
        </label>
        <label>Situação
            <select name="situacao">
                <option value="">Todas</option>
                <option value="disponivel" @selected(($filtros['situacao'] ?? '') === 'disponivel')>Disponível</option>
                <option value="locada" @selected(($filtros['situacao'] ?? '') === 'locada')>Locada</option>
                <option value="em_transporte" @selected(($filtros['situacao'] ?? '') === 'em_transporte')>Em transporte</option>
                <option value="devolvida" @selected(($filtros['situacao'] ?? '') === 'devolvida')>Devolvida</option>
                <option value="manutencao" @selected(($filtros['situacao'] ?? '') === 'manutencao')>Em manutenção</option>
            </select>
        </label>
        <label>Cliente
            <select name="cliente">
                <option value="">Todos</option>
                @foreach(($clientesLocacao ?? collect()) as $cliente)
                    <option value="{{ $cliente->id }}" @selected((string) ($filtros['cliente'] ?? '') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                @endforeach
            </select>
        </label>
        @if($superadmin ?? false)
            <label>Empresa
                <select name="empresa">
                    <option value="">Todas</option>
                    @foreach(($empresasFiltro ?? collect()) as $empresa)
                        <option value="{{ $empresa->id }}" @selected((string) ($filtros['empresa'] ?? '') === (string) $empresa->id)>{{ $empresa->nome }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <label>ART
            <select name="art">
                <option value="">Todas</option>
                <option value="vencida" @selected(($filtros['art'] ?? '') === 'vencida')>Vencida</option>
                <option value="vencendo" @selected(($filtros['art'] ?? '') === 'vencendo')>Vence em 30 dias</option>
                <option value="em_dia" @selected(($filtros['art'] ?? '') === 'em_dia')>Em dia</option>
                <option value="sem_data" @selected(($filtros['art'] ?? '') === 'sem_data')>Sem data</option>
            </select>
        </label>
        @if($rastreamentoHabilitado)
            <label>Rastreador
                <select name="rastreador">
                    <option value="">Todos</option>
                    <option value="com" @selected(($filtros['rastreador'] ?? '') === 'com')>Com rastreador</option>
                    <option value="sem" @selected(($filtros['rastreador'] ?? '') === 'sem')>Sem rastreador</option>
                    <option value="online" @selected(($filtros['rastreador'] ?? '') === 'online')>Online</option>
                    <option value="movimento" @selected(($filtros['rastreador'] ?? '') === 'movimento')>Em movimento</option>
                    <option value="ligada" @selected(($filtros['rastreador'] ?? '') === 'ligada')>Com alimentação</option>
                    <option value="sem_alimentacao" @selected(($filtros['rastreador'] ?? '') === 'sem_alimentacao')>Sem alimentação</option>
                    <option value="offline" @selected(($filtros['rastreador'] ?? '') === 'offline')>Sem comunicação</option>
                </select>
            </label>
        @endif
        <div class="top-filter-actions">
            <a class="btn small" href="{{ route('admin.maquinas.index') }}">Limpar</a>
            <button class="btn small primary" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<section class="machine-card-list" aria-label="Máquinas cadastradas">
        @forelse ($maquinas as $maquina)
            @php
                $cobrancaFinanceiraLabel = $maquina->cobrancaFinanceiraStatusLabel();
                $cobrancaFinanceiraClasse = $maquina->cobrancaFinanceiraStatusClasse();
            @endphp
            <article class="machine-card">
                <div class="machine-card-section">
                    <span class="machine-card-label">Máquina</span>
                    <div class="machine-title">
                        <strong>{{ $maquina->nome }}</strong>
                        <small>{{ $maquina->codigo_interno ?: 'Sem código' }} @if($maquina->setor) / {{ $maquina->setor }} @endif</small>
                        @if($maquina->modelo)
                            <small>{{ $maquina->modelo }}</small>
                        @endif
                        @if($superadmin ?? false)
                            <small>Empresa: {{ $maquina->empresa?->nome ?? 'Não informada' }}</small>
                        @endif
                    </div>
                </div>

                <div class="machine-card-section">
                    <span class="machine-card-label">Situação</span>
                    <div class="machine-meta">
                        <div class="machine-info-list">
                            <span class="machine-status-line">
                                <i class="machine-dot {{ $maquina->locacaoStatusClasse() }}"></i>
                                <span><strong>{{ $maquina->locacaoStatusLabel() }}</strong></span>
                            </span>
                            <span class="machine-status-line">
                                <i class="machine-dot {{ $maquina->status === 'ativa' ? 'status-conforme' : 'danger' }}"></i>
                                <span>{{ ucfirst($maquina->status) }}</span>
                            </span>
                            <span class="machine-status-line">
                                <i class="machine-dot {{ $cobrancaFinanceiraClasse }}"></i>
                                <span>Financeiro: {{ $cobrancaFinanceiraLabel }}</span>
                            </span>
                        </div>
                        @if($maquina->locacao_cliente)
                            <span class="machine-detail-line"><strong>Cliente:</strong> {{ $maquina->locacao_cliente }}</span>
                        @endif
                        <span class="machine-detail-line"><strong>Local:</strong> {{ $maquina->localOperacionalLabel($rastreamentoHabilitado) }}</span>
                    </div>
                </div>

                <div class="machine-card-section">
                    <span class="machine-card-label">Contrato e ART</span>
                    <div class="machine-meta">
                        <div class="machine-info-list">
                            @if($maquina->contrato_fim)
                                <span class="machine-status-line">
                                    <i class="machine-dot {{ $maquina->contadorContratoClasse() }}"></i>
                                    <span><strong>{{ $maquina->contadorContrato() }}</strong></span>
                                </span>
                            @endif
                            @if($maquina->art_seguranca_vencimento)
                                <span class="machine-status-line">
                                    <i class="machine-dot {{ $maquina->contadorArtSegurancaClasse() }}"></i>
                                    <span>ART: {{ $maquina->art_seguranca_vencimento->format('d/m/Y') }}</span>
                                </span>
                            @endif
                        </div>
                        @if($maquina->contrato_inicio || $maquina->contrato_fim)
                            <small>
                                <strong>Contrato:</strong>
                                {{ $maquina->contrato_inicio?->format('d/m/Y') ?? 'sem início' }}
                                até
                                {{ $maquina->contrato_fim?->format('d/m/Y') ?? 'sem término' }}
                            </small>
                        @endif
                        @if($maquina->devolucao_prevista)
                            <small><strong>Devolução prevista:</strong> {{ $maquina->devolucao_prevista->format('d/m/Y') }}</small>
                        @endif
                        @if($maquina->contrato_atual)
                            <a class="btn small" href="{{ route('admin.maquinas.arquivos.show', [$maquina, $maquina->contrato_atual]) }}" target="_blank">Abrir contrato</a>
                        @endif
                    </div>
                </div>

                <div class="machine-card-section">
                    <span class="machine-card-label">Operação</span>
                    <div class="machine-meta">
                        @if($rastreamentoHabilitado)
                            <span class="machine-status-line">
                                <i class="machine-dot {{ $maquina->rastreador?->statusClasse() }}"></i>
                                <span>Comunicação: <strong>{{ $maquina->rastreador?->statusLabel() ?? 'Não vinculado' }}</strong></span>
                            </span>
                            @if($maquina->rastreador)
                                <span class="machine-status-line">
                                    <i class="machine-dot {{ $maquina->rastreador->operacaoClasse() }}"></i>
                                    <span><strong>{{ $maquina->rastreador->operacaoLabel() }}</strong> · {{ $maquina->horasTrabalhadasHojeLabel() }}</span>
                                </span>
                                <span class="machine-status-line">
                                    <i class="machine-dot {{ $maquina->rastreador->alimentacaoClasse() }}"></i>
                                    <span>{{ $maquina->rastreador->alimentacaoLabel() }}</span>
                                </span>
                                @if($maquina->rastreador->ultimo_sinal_em)
                                    <small><strong>Último sinal:</strong> {{ $maquina->rastreador->ultimoSinalLocalLabel() }}</small>
                                @endif
                            @endif
                        @endif
                        @if($maquina->devolucao_realizada)
                            <small>Devolvida em: {{ $maquina->devolucao_realizada->format('d/m/Y') }}</small>
                        @endif
                    </div>
                </div>

                <div class="machine-card-section actions-section">
                    <div class="machine-actions">
                        <a class="btn small primary" href="{{ route('admin.maquinas.show', $maquina) }}">Abrir</a>
                        @unless($isComercial ?? false)
                            <a class="btn small" href="{{ route('admin.maquinas.edit', $maquina) }}">Editar</a>
                            <form class="action-form" method="post" action="{{ route('admin.maquinas.destroy', $maquina) }}" data-confirm="Excluir esta máquina? Os históricos vinculados podem ser afetados.">
                                @csrf
                                @method('delete')
                                <button class="btn small danger" type="submit">Excluir</button>
                            </form>
                        @endunless
                    </div>
                </div>
            </article>
        @empty
            <div class="machine-empty">Nenhuma máquina cadastrada.</div>
        @endforelse
    {{ $maquinas->links() }}
</section>
@endsection
