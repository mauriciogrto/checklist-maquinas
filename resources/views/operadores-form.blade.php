@extends('layouts.admin')

@section('content')
<div class="top">
    <h1>{{ $operador->exists ? 'Editar operador' : 'Novo operador' }}</h1>
    <div class="actions">
        <a class="btn" href="{{ route('admin.operadores.index') }}">Voltar</a>
        @if($operador->exists)
            <form class="action-form" method="post" action="{{ route('admin.operadores.destroy', $operador) }}" data-confirm="Deseja excluir este operador? Ele não conseguirá mais acessar o sistema.">
                @csrf
                @method('delete')
                <button class="btn danger" type="submit">Excluir</button>
            </form>
        @endif
    </div>
</div>
<form class="panel grid grid-2" method="post" action="{{ $operador->exists ? route('admin.operadores.update', $operador) : route('admin.operadores.store') }}">
    @csrf
    @if($operador->exists) @method('patch') @endif
    @if(! empty($retornoClienteId))
        <input type="hidden" name="retorno_cliente_id" value="{{ $retornoClienteId }}">
    @endif
    <label>Nome <input name="nome" value="{{ old('nome', $operador->nome) }}" required></label>
    <label>Usuário <input name="usuario" value="{{ old('usuario', $operador->usuario) }}" required></label>
    <label>Cliente vinculado
        <select name="locacao_cliente_id">
            <option value="">Sem cliente vinculado</option>
            @foreach($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected((string) old('locacao_cliente_id', $operador->locacao_cliente_id) === (string) $cliente->id)>{{ $cliente->nome }}</option>
            @endforeach
        </select>
    </label>
    <label>Senha
        <input name="senha" type="password" minlength="6" @required(! $operador->exists)>
        <span>{{ $operador->exists ? 'Preencha apenas se quiser alterar a senha.' : 'Mínimo de 6 caracteres.' }}</span>
    </label>
    <label>Status
        <select name="status">
            <option value="ativo" @selected(old('status', $operador->status) === 'ativo')>Ativo</option>
            <option value="inativo" @selected(old('status', $operador->status) === 'inativo')>Inativo</option>
        </select>
    </label>
    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ route('admin.operadores.index') }}">Cancelar</a>
            <button class="btn primary" type="submit">Salvar</button>
        </div>
    </div>
</form>
@endsection
