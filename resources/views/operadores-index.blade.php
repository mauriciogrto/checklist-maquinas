@extends('layouts.admin')

@section('content')
<style>
    .operator-table {
        min-width: 720px;
        table-layout: fixed;
    }

    .operator-table th:nth-child(1) { width: 32%; }
    .operator-table th:nth-child(2) { width: 26%; }
    .operator-table th:nth-child(3) { width: 18%; }
    .operator-table th:nth-child(4) { width: 24%; }

    .operator-main {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .operator-main strong,
    .operator-main span {
        overflow-wrap: anywhere;
    }

    .operator-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        align-items: center;
    }

    .operator-actions .btn,
    .operator-actions form {
        width: 100%;
    }

    @media (max-width: 720px) {
        .operator-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="top">
    <h1>Operadores</h1>
    <a class="btn primary" href="{{ route('admin.operadores.create') }}">Novo operador</a>
</div>
<div class="panel table-wrap">
    <table class="operator-table">
        <thead><tr><th>Operador</th><th>Cliente</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($operadores as $operador)
            <tr>
                <td>
                    <div class="operator-main">
                        <strong>{{ $operador->nome }}</strong>
                        <span>{{ $operador->usuario }}</span>
                    </div>
                </td>
                <td><div class="operator-main"><span>{{ $operador->cliente?->nome ?? 'Sem cliente vinculado' }}</span></div></td>
                <td>
                    <span class="status-line">
                        <i class="status-dot {{ $operador->status === 'ativo' ? 'ok' : 'danger' }}"></i>
                        <span>{{ ucfirst($operador->status) }}</span>
                    </span>
                </td>
                <td>
                    <div class="operator-actions">
                        <a class="btn small primary" href="{{ route('admin.operadores.edit', $operador) }}">Editar</a>
                        <form class="action-form" method="post" action="{{ route('admin.operadores.destroy', $operador) }}" data-confirm="Deseja excluir este operador? Ele não conseguirá mais acessar o sistema.">
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
    {{ $operadores->links() }}
</div>
@endsection
