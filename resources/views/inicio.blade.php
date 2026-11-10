@php
    $usuario = auth('admin')->user()?->nome ?? 'Administrador';
    $isComercial = (bool) auth('admin')->user()?->isComercial();
    $atalhos = [];

    if (! $isComercial) {
        $atalhos[] = [
            'titulo' => 'Dashboard',
            'texto' => 'Painel operacional da empresa.',
            'url' => route('admin.dashboard'),
            'classe' => 'primary',
        ];
    }

    $atalhos = array_merge($atalhos, [
        [
            'titulo' => 'Máquinas',
            'texto' => $isComercial ? 'Consulta comercial dos equipamentos.' : 'Equipamentos, documentos e contratos.',
            'url' => route('admin.maquinas.index'),
            'classe' => 'success',
        ],
        [
            'titulo' => 'Clientes',
            'texto' => 'Cadastros para locação e propostas.',
            'url' => route('admin.clientes.index'),
            'classe' => 'neutral',
        ],
        [
            'titulo' => 'Orçamentos',
            'texto' => 'Propostas comerciais padronizadas.',
            'url' => route('admin.orcamentos.index'),
            'classe' => 'warning',
        ],
    ]);

    if ($isComercial) {
        $atalhos[] = [
            'titulo' => 'Contratos',
            'texto' => 'Consulta de contratos gerados.',
            'url' => route('admin.contratos.index'),
            'classe' => 'neutral',
        ];
    }

    if (! $isComercial) {
        $atalhos[] = [
            'titulo' => 'Alertas',
            'texto' => 'Pendências e tratativas operacionais.',
            'url' => route('admin.alertas.index'),
            'classe' => 'danger',
        ];
        $atalhos[] = [
            'titulo' => 'Checklists',
            'texto' => 'Modelos e padrões de inspeção.',
            'url' => route('admin.formularios-modelo.index'),
            'classe' => 'neutral',
        ];
        $atalhos[] = [
            'titulo' => 'Histórico',
            'texto' => 'Registros, anexos e evidências.',
            'url' => route('admin.historico.index'),
            'classe' => 'neutral',
        ];
    }

    if ($rastreamentoHabilitado && ! $isComercial) {
        $atalhos[] = [
            'titulo' => 'Mapa',
            'texto' => 'Localização das máquinas rastreadas.',
            'url' => route('admin.rastreamento.mapa'),
            'classe' => 'success',
        ];
    }

    if (auth('admin')->user()?->is_superadmin) {
        $atalhos[] = [
            'titulo' => 'Sistema',
            'texto' => 'Empresas, administradores e backup.',
            'url' => route('admin.sistema.index'),
            'classe' => 'neutral',
        ];
    }
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Máquinas</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}?v=20260807-transp">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --bg: #f7f8fb;
            --text: #10131a;
            --text-dim: #667085;
            --text-faint: #98a2b3;
            --border: #e7eaf0;
            --accent: #baff5a;
            --teal: #14b8a6;
            --red: #ef4444;
            --amber: #f59e0b;
            --radius: 10px;
            --shadow: 0 18px 46px rgba(16, 19, 26, .08);
        }

        * { box-sizing: border-box; }

        html,
        body {
            max-width: 100%;
            height: 100%;
            overflow-x: hidden;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 14% 16%, rgba(186, 255, 90, .20), transparent 24%),
                radial-gradient(circle at 92% 8%, rgba(20, 184, 166, .14), transparent 26%),
                var(--bg);
            overflow: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .home-shell {
            width: 100%;
            max-width: 100dvw;
            height: 100dvh;
            min-height: 0;
            display: grid;
            grid-template-columns: minmax(280px, .82fr) minmax(0, 1.18fr);
            overflow: hidden;
        }

        .brand-panel {
            position: relative;
            display: grid;
            place-items: center;
            gap: 28px;
            min-width: 0;
            padding: clamp(22px, 4vw, 52px);
            background: #080a12;
            color: #ffffff;
            overflow: hidden;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 42%;
            background: linear-gradient(0deg, rgba(186, 255, 90, .12), transparent);
            pointer-events: none;
        }

        .brand-logo {
            position: relative;
            z-index: 1;
            display: grid;
            justify-items: center;
            width: min(100%, 500px);
        }

        .brand-logo img {
            width: min(100%, 500px);
            max-height: 64vh;
            object-fit: contain;
            filter: drop-shadow(0 24px 36px rgba(0, 0, 0, .24));
        }

        .brand-logo h1 {
            margin: 0;
            max-width: 520px;
            font-size: clamp(32px, 5vw, 60px);
            line-height: 1.02;
            font-weight: 700;
            letter-spacing: 0;
        }

        .brand-logo p {
            margin: 0;
            max-width: 520px;
            color: rgba(255, 255, 255, .72);
            font-size: clamp(15px, 1.4vw, 18px);
            line-height: 1.55;
        }

        .action-panel {
            min-width: 0;
            display: grid;
            align-content: center;
            gap: clamp(12px, 2vh, 18px);
            padding: clamp(18px, 3vw, 42px);
            overflow: hidden;
        }

        .home-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .home-head span {
            display: block;
            color: var(--text-faint);
            font-size: 13px;
            font-weight: 600;
        }

        .home-head h2 {
            margin: 4px 0 0;
            font-size: clamp(23px, 2.6vw, 34px);
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: 0;
        }

        .logout-form {
            margin: 0;
        }

        .logout {
            min-height: 34px;
            padding: 7px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #ffffff;
            color: var(--text-dim);
            font: inherit;
            font-size: 13px;
            font-weight: 650;
            cursor: pointer;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 176px), 1fr));
            gap: clamp(8px, 1.4vh, 11px);
            min-width: 0;
        }

        .card {
            min-width: 0;
            min-height: clamp(92px, 13vh, 116px);
            display: grid;
            gap: 8px;
            align-content: space-between;
            padding: 13px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 10px 26px rgba(16, 19, 26, .045);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            border-color: #cfd6e3;
            box-shadow: var(--shadow);
        }

        .card::before {
            display: none;
        }

        .card-icon {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            border-radius: 9px;
            color: #ffffff;
            background: #101828;
            box-shadow: 0 8px 18px rgba(16, 24, 40, .16);
        }

        .card-icon svg {
            width: 19px;
            height: 19px;
            stroke-width: 2;
        }

        .card strong {
            display: block;
            color: var(--text);
            font-size: 16px;
            line-height: 1.2;
            font-weight: 700;
        }

        .card div span {
            display: block;
            color: var(--text-dim);
            font-size: 12px;
            line-height: 1.32;
            font-weight: 500;
        }

        .card small {
            color: var(--text-faint);
            font-size: 12px;
            font-weight: 650;
        }

        @media (max-width: 900px) {
            .home-shell {
                grid-template-columns: 1fr;
                grid-template-rows: auto minmax(0, 1fr);
                height: 100dvh;
                min-height: 0;
                overflow: hidden;
            }

            .brand-panel {
                min-height: 0;
                height: clamp(120px, 24dvh, 180px);
                padding: 14px 18px;
            }

            .brand-logo img {
                width: min(72vw, 260px);
                max-height: 150px;
            }

            .action-panel {
                align-content: start;
                gap: 10px;
                padding: 14px;
                overflow: hidden;
            }

            .home-head {
                align-items: center;
            }

            .home-head span {
                font-size: 11px;
            }

            .home-head h2 {
                font-size: 22px;
                margin-top: 2px;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .card {
                min-height: 76px;
                padding: 10px;
                gap: 6px;
            }

            .card::before {
                display: none;
            }

            .card-icon {
                width: 30px;
                height: 30px;
                border-radius: 8px;
            }

            .card-icon svg {
                width: 16px;
                height: 16px;
            }

            .card strong {
                font-size: 14px;
            }

            .card div span {
                display: none;
            }

            .card small {
                font-size: 11px;
            }
        }

        @media (max-width: 520px) {
            .home-head {
                align-items: center;
            }

            .logout,
            .logout-form {
                width: auto;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 380px) {
            .brand-panel {
                height: 106px;
            }

            .brand-logo img {
                width: min(70vw, 210px);
                max-height: 112px;
            }

            .action-panel {
                padding: 10px;
            }

            .card {
                min-height: 62px;
                padding: 8px;
            }

            .card strong {
                font-size: 13px;
            }
        }

        /* TailAdmin second pass: home */
        :root {
            --bg: #f9fafb;
            --text: #101828;
            --text-dim: #475467;
            --text-faint: #98a2b3;
            --border: #e4e7ec;
            --brand: #14b8a6;
            --brand-strong: #0f766e;
            --brand-soft: #ecfdf3;
            --shadow: 0 1px 2px rgba(16,24,40,.05);
            --shadow-lg: 0 12px 16px -4px rgba(16,24,40,.08), 0 4px 6px -2px rgba(16,24,40,.03);
            --radius: 8px;
        }

        body {
            background: var(--bg);
        }

        .brand-panel {
            background: #101828;
        }

        .brand-panel::after {
            height: 34%;
            background: linear-gradient(0deg, rgba(20,184,166,.16), transparent);
        }

        .brand-logo img {
            width: min(100%, 430px);
            filter: drop-shadow(0 22px 34px rgba(0,0,0,.26));
        }

        .action-panel {
            background: #f9fafb;
        }

        .home-head span {
            color: var(--text-faint);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .home-head h2 {
            font-size: clamp(22px, 2.2vw, 30px);
            color: var(--text);
        }

        .logout {
            border-radius: 6px;
            border-color: var(--border);
            box-shadow: var(--shadow);
        }

        .cards {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
            gap: 12px;
        }

        .card {
            min-height: 112px;
            padding: 16px;
            border-radius: 8px;
            background: #ffffff;
            border-color: var(--border);
            box-shadow: var(--shadow);
            align-content: start;
            gap: 10px;
        }

        .card:hover {
            transform: translateY(-1px);
            border-color: #d0d5dd;
            box-shadow: var(--shadow-lg);
        }

        .card::before {
            display: none;
        }

        .card-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            color: #ffffff;
            background: #101828;
            box-shadow: 0 8px 18px rgba(16, 24, 40, .14);
        }

        .card strong {
            font-size: 15px;
            color: var(--text);
        }

        .card div span {
            color: var(--text-dim);
            font-size: 12px;
        }

        .card small {
            margin-top: auto;
            color: var(--brand-strong);
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .brand-panel { background: #101828; }
            .brand-logo img { width: min(68vw, 230px); }
            .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .card { min-height: 100px; padding: 13px; }
        }

        @media (max-width: 520px) {
            .cards { grid-template-columns: 1fr; overflow-y: auto; padding-bottom: 8px; }
            .home-shell { overflow: auto; height: auto; min-height: 100dvh; }
            body { overflow: auto; }
        }


    </style>
</head>
<body>
<main class="home-shell">
    <section class="brand-panel">
        <div class="brand-logo">
            <img src="{{ asset('assets/logo.png') }}?v=20260807-transp" alt="Gestão de Máquinas">
        </div>
    </section>

    <section class="action-panel">
        <div class="home-head">
            <div>
                <span>{{ now()->format('d/m/Y') }}</span>
                <h2>Olá, {{ $usuario }}.</h2>
            </div>
            <form class="logout-form" method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout" type="submit">Sair</button>
            </form>
        </div>

        <div class="cards">
            @foreach($atalhos as $atalho)
                @php
                    $iconeAtalho = strtolower(\Illuminate\Support\Str::ascii($atalho['titulo']));
                @endphp
                <a class="card {{ $atalho['classe'] }}" href="{{ $atalho['url'] }}">
                    <span class="card-icon" aria-hidden="true">
                        @switch($iconeAtalho)
                            @case('dashboard')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="8" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="15" width="7" height="6" rx="1.5"/></svg>
                                @break
                            @case('maquinas')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 1.55V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.55V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.55 1H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"/></svg>
                                @break
                            @case('alertas')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 4.3 2.8 17.4A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.6L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                @break
                            @case('clientes')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                @break
                            @case('orcamentos')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v15H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
                                @break
                            @case('checklists')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l2 2 4-4"/><path d="M9 17l2 2 4-4"/><path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                                @break
                            @case('operadores')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/><path d="M18 3v3"/><path d="M21 6h-6"/></svg>
                                @break
                            @case('historico')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/></svg>
                                @break
                            @case('mapa')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v6H4z"/><path d="M4 14h7v6H4z"/><path d="M15 14h5v6h-5z"/></svg>
                        @endswitch
                    </span>
                    <div>
                        <strong>{{ $atalho['titulo'] }}</strong>
                        <span>{{ $atalho['texto'] }}</span>
                    </div>
                    <small>Abrir</small>
                </a>
            @endforeach
        </div>
    </section>
</main>
</body>
</html>
