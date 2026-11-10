@extends('layouts.operador')

@section('content')
<style>
    .problem-report {
        display: grid;
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .problem-report summary {
        list-style: none;
    }

    .problem-report summary::-webkit-details-marker {
        display: none;
    }

    .problem-report[open] summary {
        margin-bottom: 12px;
    }

    .problem-report form {
        display: grid;
        gap: 14px;
    }

    .problem-trigger {
        border-color: #fecaca;
        background: #fff1f2;
        color: #b91c1c;
        box-shadow: none;
    }

    .problem-trigger:hover,
    .problem-trigger:focus {
        border-color: #fca5a5;
        background: #fee2e2;
        color: #991b1b;
    }

    .machine-mobile-actions {
        display: grid;
        gap: 10px;
    }

    /* TailAdmin second pass: QR/checklist mobile */
    .machine-mobile-actions {
        gap: 8px;
    }

    .machine-mobile-actions .btn {
        justify-content: center;
        min-height: 52px;
        padding: 13px 14px;
        border-radius: 8px;
        text-align: center;
    }

    .machine-mobile-actions p {
        margin: -2px 0 8px;
        padding: 0 4px;
        font-size: 12px;
        color: var(--text-dim);
    }

    .problem-report {
        padding: 12px;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: var(--shadow);
    }

    .problem-trigger {
        justify-content: center;
        border-color: #fecdca;
        background: #fef3f2;
        color: #b42318;
    }

    .problem-trigger:hover,
    .problem-trigger:focus {
        border-color: #fda29b;
        background: #fee4e2;
        color: #912018;
    }

    .top {
        align-items: flex-start;
        gap: 10px;
    }

    .operator-actions .btn {
        min-height: 38px;
        padding: 8px 12px;
    }

    @media (max-width: 620px) {
        .top { gap: 12px; }
        .operator-actions .btn { width: 100%; }
    }

</style>

<section class="panel stack">
    <div class="top">
        <div>
            <h1>{{ $maquina->nome }}</h1>
            <p>{{ $maquina->codigo_interno }} @if($maquina->setor) - {{ $maquina->setor }} @endif</p>
        </div>
        @auth('operador')
            <form class="operator-actions" method="post" action="{{ route('operador.logout') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $maquina->token }}">
                <button class="btn secondary">Sair</button>
            </form>
        @endauth
    </div>

    @guest('operador')
        <form class="stack" method="post" action="{{ route('operador.login.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $maquina->token }}">
            <label>Login da máquina
                <input name="usuario" value="{{ old('usuario', $maquina->loginQrPadrao()) }}" required autofocus>
            </label>
            <label>Senha
                <input name="senha" type="password" required>
            </label>
            <button class="btn">Entrar</button>
        </form>
    @else
        <div class="machine-mobile-actions">
            @foreach ($checklists as $checklist)
                @if ($checklist['concluido'] || ($checklist['bloqueado'] ?? false))
                    <a class="btn secondary disabled" aria-disabled="true" href="#">
                        {{ $checklist['label'] }} - {{ $checklist['status'] }}
                    </a>
                    <p>{{ $checklist['hint'] }}</p>
                @else
                    <a class="btn {{ $checklist['periodicidade'] === 'diario' ? '' : 'secondary' }}" href="{{ $checklist['url'] ?? route('maquinas.public.checklist.create', [$maquina->token, $checklist['periodicidade']]) }}">
                        {{ $checklist['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
        <details class="problem-report">
            <summary class="btn secondary problem-trigger">Relatar problema</summary>
            <form method="post" enctype="multipart/form-data" data-compress-images action="{{ route('maquinas.public.problemas.store', $maquina->token) }}">
                @csrf
                <label>Foto <span>Opcional</span>
                    <input name="foto" type="file" accept="image/*" capture="environment">
                </label>
                <label>Descrição <span>Opcional</span>
                    <textarea name="descricao" placeholder="Descreva o que aconteceu durante a operação.">{{ old('descricao') }}</textarea>
                </label>
                <button class="btn" type="submit">Enviar relato</button>
            </form>
        </details>
    @endguest

</section>
@endsection
