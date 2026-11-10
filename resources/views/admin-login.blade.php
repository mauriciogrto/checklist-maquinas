<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Máquinas</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}?v=20260807-transp">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #2d323a;
            --panel: #ffffff;
            --border: #e7eaf0;
            --border-strong: #cfd6e3;
            --primary: #10131a;
            --primary-hover: #0f766e;
            --text: #f8fafc;
            --text-dim: #cbd5e1;
            --red: #dc2626;
            --shadow: 0 8px 22px rgba(16, 19, 26, 0.06);
            --radius-md: 8px;
        }

        * { box-sizing: border-box; }

        html {
            min-height: 100%;
            background: var(--bg);
        }

        body {
            margin: 0;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 22px;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        form {
            width: min(420px, 100%);
            display: grid;
            gap: 16px;
            justify-items: stretch;
            background: transparent;
            border: 0;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 28px;
            line-height: 1.1;
            letter-spacing: 0;
            color: var(--text);
            font-weight: 700;
            text-align: center;
        }

        .login-brand {
            width: 100%;
            max-width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            justify-self: center;
            margin: 0 auto 8px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .login-logo {
            width: min(100%, 230px);
            max-height: 170px;
            height: auto;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 16px 30px rgba(16, 19, 26, 0.18));
        }

        input {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-md);
            font: inherit;
            color: #111827;
            background: #f8fafc;
            outline: none;
            transition: 0.18s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        label {
            display: grid;
            gap: 8px;
            color: #f1f5f9;
            font-weight: 500;
        }

        button {
            min-height: 48px;
            border: 1px solid var(--primary);
            border-radius: var(--radius-md);
            background: rgba(20, 184, 166, .12);
            border-color: rgba(20, 184, 166, .28);
            color: #0f766e;
            font-weight: 600;
            line-height: 1.25;
            cursor: pointer;
            box-shadow: none;
            transition: 0.18s ease;
        }

        button:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: #ffffff;
        }

        button:disabled {
            opacity: 0.4;
            box-shadow: none;
            cursor: not-allowed;
        }

        .errors {
            position: relative;
            padding: 14px 16px 14px 18px;
            border: 1px solid rgba(239, 68, 68, .28);
            border-radius: var(--radius-md);
            background: #ffffff;
            color: var(--text);
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .errors::before {
            content: "";
            position: absolute;
            left: 8px;
            top: 14px;
            bottom: 14px;
            width: 3px;
            border-radius: 99px;
            background: #ef4444;
        }

        @media (max-width: 520px) {
            body { padding: 14px; }
            form {
                padding: 0;
                border-radius: 0;
            }
            h1 { font-size: 24px; }
            .login-brand {
                padding: 0;
            }
            .login-logo { width: min(100%, 190px); max-height: 142px; }
        }

        /* TailAdmin second pass: login */
        :root {
            --bg: #1d2939;
            --panel: #ffffff;
            --border: #e4e7ec;
            --border-strong: #d0d5dd;
            --brand: #14b8a6;
            --brand-strong: #0f766e;
            --text: #101828;
            --text-dim: #475467;
            --shadow: 0 20px 24px -4px rgba(16, 24, 40, .08), 0 8px 8px -4px rgba(16, 24, 40, .03);
        }

        body {
            background:
                linear-gradient(145deg, rgba(255,255,255,.04), transparent 34%),
                var(--bg);
            padding: 20px;
        }

        form {
            width: min(410px, 100%);
            gap: 14px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 8px;
            background: rgba(255,255,255,.04);
            box-shadow: var(--shadow);
            backdrop-filter: blur(8px);
        }

        .login-brand {
            margin-bottom: 4px;
            padding: 8px 0 10px;
        }

        .login-logo {
            width: min(100%, 180px);
            max-height: 132px;
            filter: drop-shadow(0 16px 26px rgba(0,0,0,.22));
        }

        label {
            color: #f9fafb;
            font-size: 13px;
            font-weight: 600;
        }

        input {
            min-height: 44px;
            border-color: rgba(255,255,255,.18);
            background: rgba(255,255,255,.96);
            border-radius: 6px;
            color: var(--text);
            box-shadow: 0 1px 2px rgba(16,24,40,.04);
        }

        input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(20,184,166,.16);
        }

        button {
            min-height: 44px;
            border-radius: 6px;
            background: var(--brand);
            border-color: var(--brand);
            color: #ffffff;
            font-weight: 700;
        }

        button:hover {
            background: var(--brand-strong);
            border-color: var(--brand-strong);
        }

        .errors {
            border-color: #fecdca;
            background: #fef3f2;
            color: #b42318;
            box-shadow: none;
        }

        @media (max-width: 520px) {
            body { padding: 14px; }
            form { padding: 20px; }
            .login-logo { width: min(100%, 156px); max-height: 116px; }
        }


    </style>
</head>
<body>
<form method="post" action="{{ route('admin.login.store') }}">
    @csrf
    <div class="login-brand">
        <img class="login-logo" src="{{ asset('assets/logo-header.png') }}?v=20260807-transp" alt="Empresa Demo">
    </div>
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif
    <label>Email
        <input name="email" type="email" value="{{ old('email') }}" required autofocus>
    </label>
    <label>Senha
        <input name="senha" type="password" required>
    </label>
    <button>Entrar</button>
</form>
</body>
</html>
