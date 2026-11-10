@extends('layouts.admin')

@section('content')
@php
    $statusLabels = [
        'pendente' => 'Pendente',
        'em_analise' => 'Em análise',
    ];
@endphp
<style>
    .alert-table {
        min-width: 860px;
        table-layout: fixed;
    }

    .alert-table th:nth-child(1) { width: 8%; }
    .alert-table th:nth-child(2) { width: 8%; }
    .alert-table th:nth-child(3) { width: 13%; }
    .alert-table th:nth-child(4) { width: 18%; }
    .alert-table th:nth-child(5) { width: 24%; }
    .alert-table th:nth-child(6) { width: 13%; }
    .alert-table th:nth-child(7) { width: 8%; }
    .alert-table th:nth-child(8) { width: 8%; }

    .alert-main {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .alert-main strong,
    .alert-main span {
        overflow-wrap: anywhere;
    }

    .alert-actions {
        display: grid;
        gap: 6px;
        min-width: 92px;
    }

    .alert-actions .btn,
    .alert-actions form {
        width: 100%;
    }

    .alert-actions .btn.small {
        min-height: 32px;
        padding: 7px 10px;
        white-space: nowrap;
        overflow-wrap: normal;
    }
</style>

<div class="top top-filter">
    <div class="title-copy">
        <h1>Alertas</h1>
    </div>
    <form class="top-filter-form" method="get">
        <label>Início
            <input type="date" name="inicio" value="{{ request('inicio') }}">
        </label>
        <label>Fim
            <input type="date" name="fim" value="{{ request('fim') }}">
        </label>
        <label>Máquina
            <select name="maquina_id">
                <option value="">Todas</option>
                @foreach($maquinas as $maquina)
                    <option value="{{ $maquina->id }}" @selected((string) request('maquina_id') === (string) $maquina->id)>{{ $maquina->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>Registro
            <input name="registro" inputmode="numeric" value="{{ request('registro') }}" placeholder="# do registro">
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                @foreach($statusLabels as $valor => $label)
                    <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="top-filter-actions">
            <a class="btn small" href="{{ route('admin.alertas.index') }}">Limpar</a>
            <button class="btn small primary" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<div class="panel table-wrap">
    <table class="alert-table">
        <thead><tr><th>Alerta</th><th>Registro</th><th>Data</th><th>Máquina</th><th>Ocorrência</th><th>Responsável</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($alertas as $alerta)
            <tr class="alert-card-status-{{ str_replace('_', '-', $alerta->status) }}">
                <td><strong>#{{ $alerta->id }}</strong></td>
                <td>
                    @if($alerta->registro_id)
                        <strong>#{{ $alerta->registro_id }}</strong>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $alerta->created_at->format('d/m/Y H:i') }}</td>
                <td><div class="alert-main"><strong>{{ $alerta->maquina->nome }}</strong></div></td>
                <td><div class="alert-main"><span>{{ $alerta->item?->pergunta ?? 'Relato de problema' }}</span></div></td>
                <td>{{ $alerta->operador?->nome ?? 'Acesso não identificado' }}</td>
                <td>
                    <span class="status-line">
                        <i class="status-dot alert-status-{{ str_replace('_', '-', $alerta->status) }}"></i>
                        <span>{{ $statusLabels[$alerta->status] ?? ucfirst(str_replace('_', ' ', $alerta->status)) }}</span>
                    </span>
                </td>
                <td>
                    <div class="alert-actions">
                        <a class="btn small primary" href="{{ route('admin.alertas.show', $alerta) }}">Detalhes</a>
                        <form class="action-form" method="post" action="{{ route('admin.alertas.destroy', $alerta) }}" data-confirm="Arquivar este alerta?">
                            @csrf
                            @method('delete')
                            <button class="btn small danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $alertas->links() }}
</div>
@endsection
