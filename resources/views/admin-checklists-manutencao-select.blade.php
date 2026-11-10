@extends('layouts.admin')

@section('content')
<style>
    .maintenance-type-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .maintenance-type-card {
        display: grid;
        gap: 10px;
        align-content: space-between;
        min-height: 150px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
        box-shadow: var(--shadow-sm);
    }

    .maintenance-type-card strong {
        color: var(--text);
        font-size: 16px;
    }

    .maintenance-type-card p {
        margin: 0;
        color: var(--text-dim);
        font-size: 13px;
        line-height: 1.4;
    }
</style>

<div class="top">
    <div>
        <h1>Iniciar manutenção</h1>
        <p>{{ $maquina->nome }} - selecione o tipo de manutenção para continuar.</p>
    </div>
    <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}">Voltar</a>
</div>

<section class="maintenance-type-grid">
    @foreach($tiposManutencao as $valor => $label)
        <article class="maintenance-type-card">
            <div>
                <strong>Manutenção {{ $label }}</strong>
                <p>
                    @switch($valor)
                        @case('preventiva')
                            Intervenção programada para reduzir falhas e manter a máquina disponível.
                            @break
                        @case('preditiva')
                            Análise baseada em sinais, uso ou condição observada antes da falha.
                            @break
                        @case('corretiva')
                            Correção de falha, defeito ou problema identificado na operação.
                            @break
                    @endswitch
                </p>
            </div>
            <a class="btn primary" href="{{ route('admin.maquinas.manutencao.create', [$maquina, 'tipo_manutencao' => $valor]) }}">Selecionar</a>
        </article>
    @endforeach
</section>
@endsection
