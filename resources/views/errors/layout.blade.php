@php
    $status = $status ?? 'Erro';
    $title = $title ?? 'Algo saiu do esperado';
    $message = $message ?? 'Não foi possível concluir esta solicitação agora.';
    $primaryUrl = $primaryUrl ?? url('/admin/login');
    $primaryLabel = $primaryLabel ?? 'Acessar sistema';
    $autoRedirectUrl = $autoRedirectUrl ?? null;
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($autoRedirectUrl)
        <meta http-equiv="refresh" content="1;url={{ $autoRedirectUrl }}">
    @endif
    <title>{{ $status }} - Gestão de Máquinas</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #121826;
            --muted: #667085;
            --line: #d9e2ec;
            --panel: #ffffff;
            --soft: #f5f7fb;
            --brand: #1e4fd8;
            --brand-strong: #173ea9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px 18px;
            background:
                linear-gradient(135deg, rgba(30, 79, 216, 0.08), transparent 34%),
                var(--soft);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        main {
            width: min(100%, 560px);
            padding: 34px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 24px 70px rgba(18, 24, 38, 0.10);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 10px 14px;
            border-radius: 14px;
            background: #121826;
            color: #fff;
            font-weight: 800;
            letter-spacing: 0;
        }

        .status {
            display: inline-flex;
            margin-top: 28px;
            padding: 7px 11px;
            border-radius: 999px;
            background: #eef4ff;
            color: var(--brand-strong);
            font-size: 13px;
            font-weight: 700;
        }

        h1 {
            margin: 18px 0 10px;
            font-size: clamp(28px, 5vw, 42px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.65;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
        }

        a,
        button {
            min-height: 44px;
            padding: 11px 16px;
            border: 1px solid transparent;
            border-radius: 10px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        a {
            background: var(--brand);
            color: #fff;
        }

        button {
            background: #fff;
            color: var(--ink);
            border-color: var(--line);
        }

        @media (max-width: 520px) {
            main {
                padding: 26px 22px;
                border-radius: 14px;
            }

            .actions {
                display: grid;
            }

            a,
            button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="brand">Gestão de Máquinas</div>
        <div class="status">Erro {{ $status }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <a href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
            <button type="button" onclick="history.back()">Voltar</button>
        </div>
    </main>
    @if($autoRedirectUrl)
        <script>
            window.setTimeout(function () {
                window.location.replace(@json($autoRedirectUrl));
            }, 300);
        </script>
    @endif
</body>
</html>
