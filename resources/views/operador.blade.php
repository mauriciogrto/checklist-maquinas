<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <title>{{ $title ?? 'Gestão de Máquinas' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}?v=20260807-transp">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #f7f8fb;
            --panel: #ffffff;
            --border: #e7eaf0;
            --border-strong: #cfd6e3;
            --orange: #10131a;
            --orange-2: #baff5a;
            --text: #111827;
            --text-dim: #6b7280;
            --text-faint: #9ca3af;
            --green: #059669;
            --red: #dc2626;
            --radius-lg: 10px;
            --radius-md: 8px;
            --shadow: 0 8px 22px rgba(16, 19, 26, 0.06);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 20% 10%, rgba(186, 255, 90, 0.2), transparent 24%),
                radial-gradient(circle at 88% 4%, rgba(79, 70, 229, 0.14), transparent 28%),
                var(--bg);
            overflow-x: hidden;
            font-size: 15px;
            line-height: 1.5;
        }

        .wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 28px 18px 60px;
        }

        .operator-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            color: var(--text);
            font-size: 18px;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
        }

        .operator-brand img {
            width: 118px;
            height: 92px;
            object-fit: contain;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .stack { display: grid; gap: 16px; }

        .top {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            margin-bottom: 18px;
        }

        h1 {
            font-size: 28px;
            line-height: 1.15;
            margin: 0 0 8px;
            color: var(--text);
            font-weight: 700;
            letter-spacing: 0;
        }

        p {
            color: var(--text-dim);
            margin: 0;
            line-height: 1.55;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-md);
            font: inherit;
            color: var(--text);
            background: #ffffff;
            outline: none;
            transition: 0.18s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
            background: #ffffff;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        label {
            display: grid;
            gap: 8px;
            color: #374151;
            font-weight: 500;
        }

        label span {
            color: var(--text-dim);
            font-weight: 500;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            width: 100%;
            justify-content: center;
            align-items: center;
            min-height: 48px;
            padding: 12px 20px;
            border: 1px solid rgba(20, 184, 166, .28);
            border-radius: var(--radius-md);
            background: rgba(20, 184, 166, .12);
            color: #0f766e;
            font-weight: 600;
            line-height: 1.25;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: none;
            transition: 0.18s ease;
        }

        .btn:hover {
            background: #0f766e;
            border-color: #0f766e;
            color: #ffffff;
        }

        .btn.secondary {
            background: #ffffff;
            border-color: var(--border-strong);
            color: #374151;
            box-shadow: none;
        }

        .btn.secondary:hover {
            background: #f9fafb;
            color: var(--orange);
            border-color: #cfd6e3;
        }

        .btn:disabled,
        .btn.disabled {
            opacity: 0.4;
            box-shadow: none;
            cursor: not-allowed;
            pointer-events: none;
        }

        .operator-actions {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .operator-actions .btn {
            width: auto;
            min-width: 132px;
        }

        .notice,
        .errors {
            position: relative;
            margin-bottom: 18px;
            padding: 14px 16px 14px 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: #ffffff;
            color: var(--text);
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .notice::before,
        .errors::before {
            content: "";
            position: absolute;
            left: 8px;
            top: 14px;
            bottom: 14px;
            width: 3px;
            border-radius: 99px;
        }

        .notice {
            border-color: rgba(20, 184, 166, .28);
        }

        .errors {
            border-color: rgba(239, 68, 68, .28);
        }

        .notice::before {
            background: #14b8a6;
        }

        .errors::before {
            background: #ef4444;
        }

        @media (max-width: 620px) {
            .wrap { padding: 16px 12px 44px; }
            .panel { padding: 20px; }
            .top { align-items: stretch; flex-direction: column; }
            .actions { grid-template-columns: 1fr; }
            .operator-actions .btn { width: 100%; }
            h1 { font-size: 24px; }
            .btn { white-space: normal; }
        }
    </style>
</head>
<body>
<main class="wrap">
    <div class="operator-brand">
        <img src="{{ asset('assets/logo.png') }}?v=20260807-transp" alt="Empresa Demo">
        Gestão de Máquinas
    </div>

    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif
    @yield('content')
</main>
<script>
const compressOperatorImage = async (file) => {
    if (!file || !file.type.startsWith('image/') || file.size < 350 * 1024) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const maxSide = 1280;
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.68));

        if (!blob || blob.size >= file.size) {
            return file;
        }

        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });
    } catch (error) {
        return file;
    }
};

document.querySelectorAll('form[data-compress-images]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.compressing === '1') {
            return;
        }

        const inputs = [...form.querySelectorAll('input[type="file"]:not(:disabled)')]
            .filter((input) => input.files?.[0]?.type?.startsWith('image/'));

        if (!inputs.length) {
            return;
        }

        event.preventDefault();
        form.dataset.compressing = '1';

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton?.textContent;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Enviando foto...';
        }

        for (const input of inputs) {
            const compressed = await compressOperatorImage(input.files[0]);

            try {
                const transfer = new DataTransfer();
                transfer.items.add(compressed);
                input.files = transfer.files;
            } catch (error) {
                // Se o navegador bloquear a troca, o envio segue com a imagem original.
            }
        }

        if (submitButton) {
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }

        form.requestSubmit();
    });
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>
</body>
</html>
