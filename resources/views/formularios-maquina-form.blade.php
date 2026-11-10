@extends('layouts.admin')

@section('content')
<style>
    .builder-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }

    .question-list {
        display: grid;
        gap: 12px;
    }

    .question-item {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(160px, 0.7fr) minmax(160px, 0.7fr) auto;
        gap: 12px;
        align-items: end;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .question-flags {
        display: grid;
        gap: 8px;
    }

    .question-flags span {
        color: var(--text-dim);
        font-weight: 500;
    }

    .question-empty {
        padding: 18px;
        border: 1px dashed var(--border-strong);
        border-radius: var(--radius-md);
        color: var(--text-dim);
    }

    @media (max-width: 920px) {
        .question-item {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="top">
    <div>
        <h1>Formulário da máquina</h1>
        <p>{{ $maquina->nome }} - {{ $tipo }}</p>
    </div>
    <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}">Voltar</a>
</div>
<form class="panel stack" method="post" action="{{ $updateRoute ?? route('admin.formularios-maquina.update', [$maquina, $tipo]) }}">
    @csrf
    @method('patch')
    <label>Nome <input name="nome" value="{{ old('nome', $formulario->nome) }}" required></label>
    <div class="builder-head">
        <h2>Perguntas</h2>
        <button class="btn primary" type="button" data-add-question>Adicionar pergunta</button>
    </div>

    @php($itens = old('itens', $formulario->itens?->toArray() ?: []))
    <div class="question-list" data-question-list data-next-index="{{ count($itens) }}">
        <div class="question-empty" data-question-empty @if(count($itens)) hidden @endif>
            Nenhuma pergunta cadastrada. Clique em adicionar pergunta para montar o formulário.
        </div>

        @foreach ($itens as $index => $item)
            <div class="question-item" data-question-item>
                <label>Pergunta
                    <input name="itens[{{ $index }}][pergunta]" value="{{ $item['pergunta'] ?? '' }}">
                </label>
                <label>Tipo
                    <select name="itens[{{ $index }}][tipo_resposta]">
                        @foreach (['conformidade', 'texto', 'numero', 'imagem'] as $tipoResposta)
                            <option value="{{ $tipoResposta }}" @selected(($item['tipo_resposta'] ?? 'conformidade') === $tipoResposta)>{{ $tipoResposta }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="question-flags">Flags
                    <input type="hidden" name="itens[{{ $index }}][obrigatorio]" value="0">
                    <input type="hidden" name="itens[{{ $index }}][dispara_alerta]" value="0">
                    <span><input style="width:auto" type="checkbox" name="itens[{{ $index }}][obrigatorio]" value="1" @checked($item['obrigatorio'] ?? true)> obrigatório</span>
                    <span><input style="width:auto" type="checkbox" name="itens[{{ $index }}][dispara_alerta]" value="1" @checked($item['dispara_alerta'] ?? false)> dispara alerta</span>
                </label>
                <button class="btn danger" type="button" data-remove-question>Remover</button>
            </div>
        @endforeach
    </div>

    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}">Cancelar</a>
            <button class="btn primary" type="submit">Salvar</button>
        </div>
    </div>
</form>

<template data-question-template>
    <div class="question-item" data-question-item>
        <label>Pergunta
            <input name="itens[__INDEX__][pergunta]">
        </label>
        <label>Tipo
            <select name="itens[__INDEX__][tipo_resposta]">
                <option value="conformidade">conformidade</option>
                <option value="texto">texto</option>
                <option value="numero">numero</option>
                <option value="imagem">imagem</option>
            </select>
        </label>
        <label class="question-flags">Flags
            <input type="hidden" name="itens[__INDEX__][obrigatorio]" value="0">
            <input type="hidden" name="itens[__INDEX__][dispara_alerta]" value="0">
            <span><input style="width:auto" type="checkbox" name="itens[__INDEX__][obrigatorio]" value="1" checked> obrigatório</span>
            <span><input style="width:auto" type="checkbox" name="itens[__INDEX__][dispara_alerta]" value="1"> dispara alerta</span>
        </label>
        <button class="btn danger" type="button" data-remove-question>Remover</button>
    </div>
</template>

<script>
(() => {
    const list = document.querySelector('[data-question-list]');
    const template = document.querySelector('[data-question-template]');
    const empty = document.querySelector('[data-question-empty]');
    const addButton = document.querySelector('[data-add-question]');

    const refreshEmpty = () => {
        empty.hidden = Boolean(list.querySelector('[data-question-item]'));
    };

    addButton.addEventListener('click', () => {
        const index = Number(list.dataset.nextIndex || 0);
        list.dataset.nextIndex = String(index + 1);
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
        refreshEmpty();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-question]');

        if (!button) {
            return;
        }

        button.closest('[data-question-item]')?.remove();
        refreshEmpty();
    });
})();
</script>
@endsection
