<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            --shadow: 0 24px 70px rgba(16, 19, 26, 0.12);
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
            border: 1px solid var(--orange);
            border-radius: 14px;
            background: var(--orange);
            color: #ffffff;
            font-weight: 500;
            line-height: 1.25;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 16px 30px rgba(186, 255, 90, 0.22);
            transition: 0.18s ease;
        }

        .btn:hover {
            background: var(--orange-2);
            border-color: var(--orange-2);
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
            border-color: #c7d2fe;
        }

        .btn:disabled,
        .btn.disabled {
            opacity: 0.4;
            box-shadow: none;
            cursor: not-allowed;
            pointer-events: none;
        }

        .notice,
        .errors {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-weight: 500;
        }

        .notice {
            background: #ecfdf5;
            color: var(--green);
            border-color: #bbf7d0;
        }

        .errors {
            background: #fee2e2;
            color: var(--red);
            border-color: #fecaca;
        }

        @media (max-width: 620px) {
            .wrap { padding: 16px 12px 44px; }
            .panel { padding: 20px; }
            .top { align-items: stretch; flex-direction: column; }
            .actions { grid-template-columns: 1fr; }
            h1 { font-size: 24px; }
            .btn { white-space: normal; }
        }

        /* TailAdmin second pass: mobile operator */
        :root {
            --bg: #f9fafb;
            --panel: #ffffff;
            --border: #e4e7ec;
            --border-strong: #d0d5dd;
            --brand: #14b8a6;
            --brand-strong: #0f766e;
            --brand-soft: #ecfdf3;
            --text: #101828;
            --text-dim: #475467;
            --text-faint: #98a2b3;
            --shadow: 0 1px 2px rgba(16,24,40,.05);
            --radius-lg: 8px;
            --radius-md: 6px;
        }

        body {
            background: var(--bg);
            font-size: 14px;
        }

        .wrap {
            max-width: 720px;
            padding: 18px 14px 44px;
        }

        .operator-brand {
            margin-bottom: 14px;
            gap: 8px;
            color: var(--text);
            font-size: 0;
        }

        .operator-brand img {
            width: 108px;
            height: 78px;
            object-fit: contain;
            filter: none;
        }

        .panel {
            border-radius: 8px;
            padding: 18px;
            box-shadow: var(--shadow);
        }

        h1 { font-size: 22px; color: var(--text); }
        h2 { font-size: 16px; color: var(--text); }
        p { color: var(--text-dim); font-size: 13px; }

        input,
        select,
        textarea {
            min-height: 42px;
            border-radius: 6px;
            border-color: var(--border-strong);
            font-size: 14px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(20,184,166,.14);
        }

        label {
            color: #344054;
            font-size: 13px;
            font-weight: 650;
        }

        .btn {
            min-height: 46px;
            border-radius: 6px;
            border-color: var(--brand);
            background: var(--brand);
            color: #ffffff;
            box-shadow: var(--shadow);
            font-weight: 700;
        }

        .btn:hover { background: var(--brand-strong); border-color: var(--brand-strong); }

        .btn.secondary {
            background: #ffffff;
            border-color: var(--border-strong);
            color: #344054;
        }

        .btn.secondary:hover {
            background: #f9fafb;
            border-color: #98a2b3;
            color: var(--text);
        }

        .notice { background: var(--brand-soft); color: #027a48; border-color: #a6f4c5; }
        .errors { background: #fef3f2; color: #b42318; border-color: #fecdca; }

        @media (max-width: 620px) {
            .wrap { padding: 12px 10px 34px; }
            .panel { padding: 14px; }
            .operator-brand img { width: 94px; height: 68px; }
        }


    </style>
</head>
<body>
<main class="wrap">
    <div class="operator-brand">
        <img src="{{ asset('assets/logo-checklist.png') }}?v=20260818-dark" alt="Empresa Demo">
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
(() => {
    const maxDimension = 1600;
    const jpegQuality = 0.7;
    const minCompressSize = 420 * 1024;

    const loadBitmap = (file) => {
        if ('createImageBitmap' in window) {
            return createImageBitmap(file);
        }

        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = URL.createObjectURL(file);
        });
    };

    const compressImageFile = async (file) => {
        if (!file || !file.type.startsWith('image/') || file.size < minCompressSize) {
            return file;
        }

        try {
            const bitmap = await loadBitmap(file);
            const width = bitmap.width || bitmap.naturalWidth;
            const height = bitmap.height || bitmap.naturalHeight;
            const scale = Math.min(1, maxDimension / Math.max(width, height));

            if (scale >= 1 && file.type === 'image/jpeg') {
                return file;
            }

            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(width * scale));
            canvas.height = Math.max(1, Math.round(height * scale));
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

            if ('close' in bitmap) {
                bitmap.close();
            }

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', jpegQuality));

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

    const replaceInputFiles = async (input) => {
        if (!input.files?.length || !window.DataTransfer) {
            return;
        }

        const transfer = new DataTransfer();

        for (const file of input.files) {
            transfer.items.add(await compressImageFile(file));
        }

        input.files = transfer.files;
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-compress-images]');

        if (!form || form.dataset.imagesReady === '1') {
            return;
        }

        const inputs = [...form.querySelectorAll('input[type="file"]:not(:disabled)')]
            .filter((input) => [...(input.files || [])].some((file) => file.type.startsWith('image/')));

        if (!inputs.length) {
            return;
        }

        event.preventDefault();
        form.dataset.imagesReady = '1';

        const submitButton = event.submitter || form.querySelector('button[type="submit"]');
        const originalText = submitButton?.textContent;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Otimizando fotos...';
        }

        for (const input of inputs) {
            await replaceInputFiles(input);
        }

        if (submitButton) {
            submitButton.textContent = 'Enviando...';
        }

        form.submit();
    });
})();
</script>
</body>
</html>
