@extends('layouts.admin')

@section('content')
@php
    $tipo = $tipo ?? 'checklist';
    $titulo = $titulo ?? 'Checklist '.$periodicidade;
@endphp
<style>
    [data-nao-conforme-box][hidden] {
        display: none !important;
    }
</style>

<div class="top">
    <div>
        <h1>{{ $titulo }}</h1>
        <p>{{ $maquina->nome }} - {{ $formulario->nome }}</p>
    </div>
    <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}">Voltar</a>
</div>

<form class="panel stack" method="post" enctype="multipart/form-data" data-checklist-form action="{{ $actionRoute ?? route('admin.maquinas.checklists.store', [$maquina, $periodicidade]) }}">
    @csrf
    @foreach ($formulario->itens as $item)
        <label>
            {{ $item->pergunta }}

            @if ($item->tipo_resposta === 'conformidade')
                <select name="respostas[{{ $item->id }}]" @required($item->obrigatorio) data-conformidade-select="{{ $item->id }}">
                    <option value="">Selecione</option>
                    <option value="conforme">Conforme</option>
                    <option value="nao_conforme">Não conforme</option>
                </select>
                <div class="stack" data-nao-conforme-box="{{ $item->id }}" hidden>
                    <label>Observação do não conforme
                        <textarea name="evidencias_texto[{{ $item->id }}]" placeholder="Descreva o problema encontrado"></textarea>
                    </label>
                    <label>Foto ou vídeo do não conforme
                        <input type="file" accept="image/*,video/mp4,video/quicktime,video/webm" name="evidencias_foto[{{ $item->id }}]">
                    </label>
                </div>
            @elseif ($item->tipo_resposta === 'numero')
                <input type="number" step="any" name="respostas[{{ $item->id }}]" @required($item->obrigatorio)>
            @elseif ($item->tipo_resposta === 'imagem')
                <input type="file" accept="image/*" name="anexos[{{ $item->id }}]" @required($item->obrigatorio)>
            @else
                <textarea name="respostas[{{ $item->id }}]" @required($item->obrigatorio)></textarea>
            @endif
        </label>
    @endforeach

    <div class="actions end">
        <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}">Cancelar</a>
        <button class="btn primary" type="submit">{{ $tipo === 'manutencao' ? 'Salvar manutenção' : 'Salvar checklist' }}</button>
    </div>
</form>

<script>
document.querySelectorAll('[data-conformidade-select]').forEach((select) => {
    const box = document.querySelector(`[data-nao-conforme-box="${select.dataset.conformidadeSelect}"]`);
    const toggle = () => {
        if (! box) {
            return;
        }

        const enabled = select.value === 'nao_conforme';
        box.hidden = !enabled;
        box.querySelectorAll('input, textarea, select').forEach((field) => {
            field.disabled = !enabled;

            if (!enabled && field.type === 'file') {
                field.value = '';
            }
        });
    };

    select.addEventListener('change', toggle);
    toggle();
});

const compressImageFile = async (file) => {
    if (!file || !file.type.startsWith('image/') || file.size < 700 * 1024) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const maxSide = 1600;
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.72));

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

document.querySelector('[data-checklist-form]')?.addEventListener('submit', async (event) => {
    const inputs = [...event.currentTarget.querySelectorAll('input[type="file"][name^="evidencias_foto"]:not(:disabled)')]
        .filter((input) => input.files?.[0]);

    if (!inputs.length || event.currentTarget.dataset.compressing === '1') {
        return;
    }

    event.preventDefault();
    event.currentTarget.dataset.compressing = '1';
    const submitButton = event.currentTarget.querySelector('button[type="submit"]');
    const originalText = submitButton?.textContent;

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Preparando arquivo...';
    }

    for (const input of inputs) {
        const compressed = await compressImageFile(input.files[0]);

        try {
            const transfer = new DataTransfer();
            transfer.items.add(compressed);
            input.files = transfer.files;
        } catch (error) {
            // Mantém a foto original se o navegador não permitir substituir o arquivo.
        }
    }

    if (submitButton) {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }

    event.currentTarget.requestSubmit();
});
</script>
@endsection
