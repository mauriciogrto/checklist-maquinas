@extends('layouts.operador')

@section('content')
<style>
    .maintenance-select {
        display: grid;
        gap: 12px;
    }

    .maintenance-option {
        display: grid;
        gap: 8px;
        padding: 14px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        background: #fff;
    }

    .maintenance-option strong {
        color: #111827;
        font-size: 16px;
    }

    .maintenance-option span {
        color: #64748b;
        font-size: 13px;
        line-height: 1.35;
    }
</style>

<section class="panel maintenance-select">
    <div>
        <h1>Iniciar manutenção</h1>
        <p>{{ $maquina->nome }} - selecione o tipo de manutenção.</p>
    </div>

    @foreach($tiposManutencao as $valor => $label)
        <a class="maintenance-option" href="{{ route('maquinas.public.manutencao.create', [$maquina->token, 'tipo_manutencao' => $valor]) }}">
            <strong>Manutenção {{ $label }}</strong>
            <span>
                @switch($valor)
                    @case('preventiva')
                        Intervenção programada.
                        @break
                    @case('preditiva')
                        Avaliação por condição observada.
                        @break
                    @case('corretiva')
                        Correção de falha ou defeito.
                        @break
                @endswitch
            </span>
        </a>
    @endforeach

    <a class="btn secondary" href="{{ route('maquinas.public.show', $maquina->token) }}">Voltar</a>
</section>
@endsection
