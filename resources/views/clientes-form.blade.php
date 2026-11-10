@extends('layouts.admin')

@section('content')
<div class="top">
    <div>
        <h1>{{ $cliente->exists ? 'Editar cliente' : 'Cadastrar cliente' }}</h1>
        <p>Dados usados em locações, contratos e orçamentos.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.clientes.index') }}">Voltar</a>
    </div>
</div>

<form class="panel grid grid-2" method="post" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}">
    @csrf
    @if($cliente->exists) @method('patch') @endif

    <label>Nome do cliente
        <input name="nome" value="{{ old('nome', $cliente->nome) }}" required>
    </label>
    <label>Documento
        <input name="documento" value="{{ old('documento', $cliente->documento) }}" placeholder="CNPJ, CPF ou identificação interna">
    </label>
    <label>E-mail
        <input type="email" name="email" value="{{ old('email', $cliente->email) }}">
    </label>
    <label>Telefone
        <input name="telefone" value="{{ old('telefone', $cliente->telefone) }}">
    </label>
    <label>Status
        <select name="status">
            <option value="ativo" @selected(old('status', $cliente->status ?: 'ativo') === 'ativo')>Ativo</option>
            <option value="inativo" @selected(old('status', $cliente->status) === 'inativo')>Inativo</option>
        </select>
    </label>
    <label style="grid-column:1 / -1">Endereço
        <textarea name="endereco" rows="3">{{ old('endereco', $cliente->endereco) }}</textarea>
    </label>
    <label style="grid-column:1 / -1">Observações
        <textarea name="observacoes" rows="4">{{ old('observacoes', $cliente->observacoes) }}</textarea>
    </label>

    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ route('admin.clientes.index') }}">Cancelar</a>
            <button class="btn primary" type="submit">Salvar</button>
        </div>
    </div>
</form>
@endsection
