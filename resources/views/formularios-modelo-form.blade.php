@extends('layouts.admin')

@section('content')
@php
    $tipoFormulario = old('tipo', $formulario->tipo ?: 'modelo_checklist');
    $isManutencao = $tipoFormulario === 'manutencao';
    $labelsPadrao = [
        'diario' => 'Diário',
        'mensal' => 'Mensal',
        'trimestral' => 'Trimestral',
        'anual' => 'Anual',
    ];
    $isProtegido = $protegido ?? ($isManutencao || isset($labelsPadrao[$formulario->periodicidade]));
@endphp
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
        <h1>{{ $formulario->exists ? 'Editar modelo' : 'Novo modelo extra' }}</h1>
        <p>
            @if($isManutencao)
                Modelo de manutenção para relatório e fotos do operador.
            @elseif($isProtegido)
                Alterações neste modelo serão usadas apenas em máquinas cadastradas depois da edição. Máquinas já cadastradas mantêm seus checklists atuais.
            @else
                Modelo de checklist.
            @endif
        </p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.formularios-modelo.index') }}">Voltar</a>
        @if($formulario->exists && ! $isProtegido)
            <form class="action-form" method="post" action="{{ route('admin.formularios-modelo.destroy', $formulario) }}" data-confirm="Excluir este modelo? Se estiver atribuído a alguma máquina, o sistema vai bloquear.">
                @csrf
                @method('delete')
                <button class="btn danger" type="submit">Excluir</button>
            </form>
        @elseif($formulario->exists)
            <span class="badge">Protegido</span>
        @endif
    </div>
</div>

<form class="panel stack" method="post" action="{{ $formulario->exists ? route('admin.formularios-modelo.update', $formulario) : route('admin.formularios-modelo.store') }}">
    @csrf
    @if($formulario->exists) @method('patch') @endif
    <input type="hidden" name="tipo" value="{{ $tipoFormulario }}">
    <p>
        <span class="badge">{{ $isManutencao ? 'Manutenção' : ($isProtegido ? 'Checklist padrão' : 'Checklist extra') }}</span>
        @if(isset($labelsPadrao[$formulario->periodicidade]))
            <span class="badge">{{ $labelsPadrao[$formulario->periodicidade] }}</span>
        @endif
    </p>
    <label>Nome <input name="nome" value="{{ old('nome', $formulario->nome) }}" required></label>
    <div class="builder-head">
        <h2>Perguntas</h2>
        <button class="btn primary" type="button" data-add-question>Adicionar pergunta</button>
    </div>

    @php($itens = old('itens', $formulario->itens?->toArray() ?: []))
    <div class="question-list" data-question-list data-next-index="{{ count($itens) }}" data-is-manutencao="{{ $isManutencao ? '1' : '0' }}">
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
                        @foreach (['conformidade', 'texto', 'numero', 'imagem'] as $tipo)
                            <option value="{{ $tipo }}" @selected(($item['tipo_resposta'] ?? 'conformidade') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="question-flags">Flags
                    <input type="hidden" name="itens[{{ $index }}][obrigatorio]" value="0">
                    <input type="hidden" name="itens[{{ $index }}][dispara_alerta]" value="0">
                    <span><input style="width:auto" type="checkbox" name="itens[{{ $index }}][obrigatorio]" value="1" @checked($item['obrigatorio'] ?? true)> obrigatório</span>
                    @if(! $isManutencao)
                        <span><input style="width:auto" type="checkbox" name="itens[{{ $index }}][dispara_alerta]" value="1" @checked($item['dispara_alerta'] ?? false)> gerar alerta</span>
                    @endif
                </label>
                <button class="btn danger" type="button" data-remove-question>Remover</button>
            </div>
        @endforeach
    </div>

    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ route('admin.formularios-modelo.index') }}">Cancelar</a>
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
            <span data-alert-flag><input style="width:auto" type="checkbox" name="itens[__INDEX__][dispara_alerta]" value="1"> gerar alerta</span>
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
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', index).trim();
        const item = wrapper.firstElementChild;

        if (list.dataset.isManutencao === '1') {
            item.querySelector('[data-alert-flag]')?.remove();
        }

        list.appendChild(item);
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
