@extends('layouts.operador')

@section('content')
@php
    $periodicidadeAtual = $periodicidade ?? null;
    $periodicidadeLabel = [
        'diario' => 'diário',
        'mensal' => 'mensal',
        'extra_diario' => 'extra diário',
        'extra_semanal' => 'extra semanal',
        'extra_mensal' => 'extra mensal',
        'extra_trimestral' => 'extra trimestral',
        'extra_anual' => 'extra anual',
    ][$periodicidadeAtual] ?? $periodicidadeAtual;
@endphp
<style>
    [data-nao-conforme-box][hidden] {
        display: none !important;
    }
</style>

<form class="panel stack" method="post" enctype="multipart/form-data" data-checklist-form data-compress-images
      action="{{ $actionRoute ?? ($tipo === 'checklist' ? route('maquinas.public.checklist.store', [$maquina->token, $periodicidadeAtual]) : route('maquinas.public.manutencao.store', $maquina->token)) }}">
    @csrf
    <div>
        <h1>{{ $tipo === 'checklist' ? 'Checklist '.$periodicidadeLabel : 'Manutenção' }}</h1>
        <p>{{ $maquina->nome }} - {{ $formulario->nome }}</p>
    </div>

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
                        <span>Informe uma observação ou envie uma foto/vídeo quando marcar Não conforme.</span>
                    </label>
                </div>
            @elseif ($item->tipo_resposta === 'numero')
                <input type="number" step="any" name="respostas[{{ $item->id }}]" @required($item->obrigatorio)>
            @elseif ($item->tipo_resposta === 'imagem')
                <input type="file" accept="image/*" capture="environment" name="anexos[{{ $item->id }}]" @required($item->obrigatorio)>
            @else
                <textarea name="respostas[{{ $item->id }}]" @required($item->obrigatorio)></textarea>
            @endif
        </label>
    @endforeach

    <div class="actions">
        <a class="btn secondary" href="{{ route('maquinas.public.show', $maquina->token) }}">Voltar</a>
        <button class="btn" type="submit">Salvar</button>
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

</script>
@endsection
