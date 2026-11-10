@extends('layouts.admin')

@section('content')
<div class="top top-filter">
    <div class="title-copy">
        <h1>Clientes</h1>
        <a class="btn primary" href="{{ route('admin.clientes.create') }}">Cadastrar cliente</a>
    </div>
    <form class="top-filter-form" method="get" action="{{ route('admin.clientes.index') }}">
        <label>Busca
            <input name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="Nome, documento ou contato">
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <option value="ativo" @selected(($filtros['status'] ?? '') === 'ativo')>Ativo</option>
                <option value="inativo" @selected(($filtros['status'] ?? '') === 'inativo')>Inativo</option>
            </select>
        </label>
        <div class="top-filter-actions">
            <a class="btn small" href="{{ route('admin.clientes.index') }}">Limpar</a>
            <button class="btn small primary" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<section class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Contato</th>
                <th>Status</th>
                <th>Máquinas</th>
                <th>Orçamentos</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($clientes as $cliente)
            <tr>
                <td>
                    <strong>{{ $cliente->nome }}</strong>
                    <small>{{ $cliente->documento ?: 'Documento não informado' }}</small>
                </td>
                <td>
                    <strong>{{ $cliente->telefone ?: 'Sem telefone' }}</strong>
                    <small>{{ $cliente->email ?: 'Sem e-mail' }}</small>
                </td>
                <td><span class="badge {{ $cliente->status === 'ativo' ? 'status-conforme' : '' }}">{{ ucfirst($cliente->status) }}</span></td>
                <td><span class="badge">{{ $cliente->maquinas_count }}</span></td>
                <td><span class="badge">{{ $cliente->orcamentos_count }}</span></td>
                <td>
                    <div class="table-actions">
                        <a class="btn small primary" href="{{ route('admin.clientes.show', $cliente) }}">Abrir</a>
                        <a class="btn small" href="{{ route('admin.clientes.edit', $cliente) }}">Editar</a>
                        <form class="action-form" method="post" action="{{ route('admin.clientes.destroy', $cliente) }}" data-confirm="Remover este cliente? As máquinas vinculadas ficarão sem cliente cadastrado.">
                            @csrf
                            @method('delete')
                            <button class="btn small danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Nenhum cliente cadastrado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</section>

{{ $clientes->links() }}
@endsection
