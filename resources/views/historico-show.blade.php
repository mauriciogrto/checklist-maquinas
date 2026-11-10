@extends('layouts.admin')

@section('content')
@php
    $voltouDaMaquina = request('from_maquina') && $registro->maquina && (string) request('from_maquina') === (string) $registro->maquina->id;
@endphp
<div class="top">
    <div>
        <h1>Registro #{{ $registro->id }}</h1>
        <p>
            {{ $registro->maquina?->nome ?? 'Máquina removida' }}
            -
            {{ $registro->operador?->nome ?? $registro->admin?->nome ?? 'Responsável não identificado' }}
            -
            {{ $registro->periodicidade ?? $registro->tipo }}
            -
            {{ $registro->data_hora->format('d/m/Y H:i') }}
        </p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ $voltouDaMaquina ? route('admin.maquinas.show', $registro->maquina).'#historico' : route('admin.historico.index') }}">Voltar</a>
        @if($registro->maquina)
            <a class="btn" href="{{ route('admin.maquinas.show', $registro->maquina) }}">Ver máquina</a>
        @endif
    </div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <h2>Respostas</h2>
        @forelse ($registro->respostas as $resposta)
            <p>
                <strong>{{ $registro->tipo === 'problema' ? 'Descrição do problema' : ($resposta->item?->pergunta ?? 'Pergunta removida do checklist') }}:</strong>
                {{ $resposta->resposta }}
            </p>
        @empty
            <p>Nenhuma resposta registrada.</p>
        @endforelse
    </div>

    <div class="panel">
        <h2>Anexos</h2>
        @forelse ($registro->anexos as $anexo)
            <p>
                <strong>{{ $registro->tipo === 'problema' ? 'Foto do problema' : ($anexo->item?->pergunta ?? 'Anexo do registro') }}:</strong>
                <a href="{{ route('admin.historico.anexos.show', $anexo) }}" target="_blank">Abrir imagem</a>
            </p>
        @empty
            <p>Nenhum anexo registrado.</p>
        @endforelse
    </div>
</div>
@endsection
