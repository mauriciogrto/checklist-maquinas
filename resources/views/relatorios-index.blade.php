@extends('layouts.admin')

@section('content')
<style>
    .reports-page {
        display: grid;
        gap: 16px;
    }

    .reports-filter-card {
        display: grid;
        gap: 14px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .reports-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        align-items: end;
    }

    .reports-filter-grid label {
        gap: 5px;
        font-size: 12px;
    }

    .reports-filter-grid input,
    .reports-filter-grid select {
        min-height: 38px;
        padding: 8px 10px;
        font-size: 13px;
    }

    .reports-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .reports-actions .btn {
        width: auto;
        min-height: 34px;
        border-radius: 6px;
    }

    @media (max-width: 720px) {
        .reports-filter-card {
            overflow: hidden;
        }

        .reports-filter-card form {
            display: flex !important;
            align-items: end;
            gap: 8px;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 0 2px 8px;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }

        .reports-filter-grid {
            flex: 0 0 auto;
            display: flex !important;
            grid-template-columns: none !important;
            flex-wrap: nowrap !important;
            gap: 8px;
            overflow: visible !important;
            padding: 0 !important;
        }

        .reports-filter-grid label {
            flex: 0 0 auto;
            width: auto !important;
            min-width: 124px;
            max-width: 168px;
            scroll-snap-align: start;
        }

        .reports-filter-grid input,
        .reports-filter-grid select {
            width: 140px !important;
            min-height: 32px !important;
            padding: 5px 8px !important;
            font-size: 12px !important;
        }

        .reports-actions {
            flex: 0 0 auto;
            justify-content: flex-start;
            flex-wrap: nowrap !important;
            overflow: visible !important;
            padding: 0 !important;
            margin-top: 0 !important;
            scroll-snap-align: start;
        }

        .reports-actions .btn {
            flex: 0 0 auto;
            width: auto !important;
            min-height: 32px !important;
            padding: 6px 9px !important;
            white-space: nowrap !important;
        }
    }

    @media (max-width: 720px) {
        .reports-filter-card > form {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: end !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            padding: 0 2px 8px !important;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }

        .reports-filter-card > form > .reports-filter-grid {
            display: flex !important;
            grid-template-columns: none !important;
            flex: 0 0 auto !important;
            flex-wrap: nowrap !important;
            overflow: visible !important;
            padding: 0 !important;
            gap: 6px !important;
        }

        .reports-filter-card > form > .reports-filter-grid > label {
            flex: 0 0 auto !important;
            width: auto !important;
            min-width: 116px !important;
            max-width: 150px !important;
            scroll-snap-align: start;
        }

        .reports-filter-card > form > .reports-filter-grid > label > input,
        .reports-filter-card > form > .reports-filter-grid > label > select {
            width: 132px !important;
            min-height: 32px !important;
            padding: 5px 8px !important;
            font-size: 12px !important;
        }

        .reports-filter-card > form > .reports-actions {
            flex: 0 0 auto !important;
            display: flex !important;
            width: auto !important;
            gap: 5px !important;
            margin-top: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            scroll-snap-align: start;
        }
    }

    .reports-types {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }

    .report-type-card {
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .report-type-card strong {
        display: block;
        margin-bottom: 4px;
        font-size: 13px;
    }

    .report-type-card small {
        display: block;
        color: var(--text-dim);
        line-height: 1.35;
    }
</style>

<div class="reports-page">
    <div class="top">
        <div>
            <h1>Relatórios</h1>
            <p>Filtre os dados e exporte um PDF para análise operacional, financeira e de rastreamento.</p>
        </div>
    </div>

    <section class="reports-filter-card">
        <form method="get" action="{{ route('admin.relatorios.export') }}" data-unsaved-ignore="1">
            <div class="reports-filter-grid">
                <label>Tipo
                    <select name="tipo" required>
                        @foreach($tiposRelatorio as $valor => $label)
                            <option value="{{ $valor }}" @selected(request('tipo', 'geral') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                @if(auth('admin')->user()?->is_superadmin)
                    <label>Empresa
                        <select name="empresa_id">
                            <option value="">Todas</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" @selected((string) request('empresa_id') === (string) $empresa->id)>{{ $empresa->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label>Cliente
                    <select name="cliente_id">
                        <option value="">Todos</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected((string) request('cliente_id') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Máquina
                    <select name="maquina_id">
                        <option value="">Todas</option>
                        @foreach($maquinas as $maquina)
                            <option value="{{ $maquina->id }}" @selected((string) request('maquina_id') === (string) $maquina->id)>{{ $maquina->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Início
                    <input name="inicio" type="date" value="{{ request('inicio') }}">
                </label>
                <label>Fim
                    <input name="fim" type="date" value="{{ request('fim') }}">
                </label>
            </div>
            <div class="reports-actions" style="margin-top:14px">
                <a class="btn" href="{{ route('admin.relatorios.index') }}">Limpar</a>
                <button class="btn primary" type="submit">Exportar PDF</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="section-head" style="margin-bottom:12px">
            <div>
                <h2>Modelos disponíveis</h2>
                <p>O PDF é gerado diretamente com resumo executivo, indicadores e tabelas dos dados filtrados.</p>
            </div>
        </div>
        <div class="reports-types">
            <div class="report-type-card">
                <strong>Geral operacional</strong>
                <small>Máquinas, alertas, checklists, localizações e financeiro em uma visão única.</small>
            </div>
            <div class="report-type-card">
                <strong>Máquinas e contratos</strong>
                <small>Situação, cliente atual, vencimentos e último sinal recebido.</small>
            </div>
            <div class="report-type-card">
                <strong>Clientes</strong>
                <small>Clientes cadastrados, máquinas vinculadas e orçamentos.</small>
            </div>
            <div class="report-type-card">
                <strong>Horímetro</strong>
                <small>Uso estimado por ignição, horímetro inicial e status de rastreadores.</small>
            </div>
            <div class="report-type-card">
                <strong>Localizações</strong>
                <small>Últimas posições, localização, velocidade e ignição.</small>
            </div>
            <div class="report-type-card">
                <strong>Financeiro</strong>
                <small>Orçamentos, propostas e cobranças mensais.</small>
            </div>
        </div>
    </section>
</div>

@endsection
