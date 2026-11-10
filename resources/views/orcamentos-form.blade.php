@extends('layouts.admin')

@section('content')
<style>
    .orcamento-items {
        display: grid;
        gap: 12px;
    }

    .orcamento-itens-card {
        grid-column: 1 / -1;
        display: grid;
        gap: 14px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .orcamento-itens-card .section-head {
        margin-bottom: 0;
    }

    .orcamento-item-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
        padding: 12px;
        border: 1px solid #dbeafe;
        border-radius: var(--radius-sm);
        background: #ffffff;
        box-shadow: inset 4px 0 0 #2dd4bf;
    }

    .orcamento-item-row .btn {
        min-height: 42px;
    }

    .orcamento-item-row label:first-child,
    .orcamento-item-row label:nth-child(2) {
        grid-column: span 2;
    }

    .orcamento-item-total {
        display: grid;
        gap: 8px;
        min-height: 42px;
        padding: 9px 10px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: #f8fafc;
        color: var(--text);
        font-size: 12px;
    }

    .orcamento-calculo-card {
        grid-column: 1 / -1;
        display: grid;
        gap: 10px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .orcamento-calculo-card table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .orcamento-calculo-card th,
    .orcamento-calculo-card td {
        padding: 9px 10px;
        border-bottom: 1px solid var(--border);
        text-align: right;
        white-space: nowrap;
    }

    .orcamento-calculo-card th:first-child,
    .orcamento-calculo-card td:first-child {
        text-align: left;
        white-space: normal;
    }

    .orcamento-calculo-card tfoot td {
        border-bottom: 0;
        font-weight: 700;
        color: var(--text);
    }

    .orcamento-fieldset {
        display: grid;
        grid-column: 1 / -1;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .contrato-aprovado-box {
        display: grid;
        gap: 8px;
        grid-column: 1 / -1;
        grid-template-columns: minmax(160px, .75fr) minmax(160px, .55fr) minmax(220px, 1fr);
        align-items: end;
        justify-self: start;
        width: min(100%, 820px);
        padding: 10px 12px;
        border: 1px solid #99f6e4;
        border-radius: var(--radius-sm);
        background: #f0fdfa;
    }

    .contrato-aprovado-box strong {
        display: block;
        color: var(--text);
        font-size: 13px;
    }

    .contrato-aprovado-box small {
        display: block;
        color: var(--text-faint);
        font-size: 11px;
        line-height: 1.35;
        margin-top: 2px;
    }

    .contrato-aprovado-box label {
        margin: 0;
    }

    @media (max-width: 980px) {
        .orcamento-fieldset,
        .orcamento-item-row {
            grid-template-columns: 1fr 1fr;
        }

        .orcamento-item-row label:first-child,
        .orcamento-item-row label:nth-child(2) {
            grid-column: span 2;
        }
    }

    @media (max-width: 620px) {
        .orcamento-fieldset,
        .orcamento-item-row,
        .contrato-aprovado-box {
            grid-template-columns: 1fr;
        }

        .orcamento-item-row label:first-child,
        .orcamento-item-row label:nth-child(2) {
            grid-column: auto;
        }
    }
</style>

<div class="top">
    <div>
        <h1>{{ $orcamento->exists ? 'Editar orçamento' : 'Novo orçamento' }}</h1>
        <p>Preencha os dados comerciais para gerar a proposta padronizada.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.orcamentos.index') }}">Voltar</a>
        @if($orcamento->exists)
            <a class="btn primary" href="{{ route('admin.orcamentos.pdf', $orcamento) }}">Exportar PDF</a>
            @if($orcamento->status === 'aprovado')
                <a class="btn primary" href="{{ route('admin.orcamentos.contrato', $orcamento) }}">Gerar contrato</a>
            @endif
            <form class="action-form" method="post" action="{{ route('admin.orcamentos.destroy', $orcamento) }}" data-confirm="Excluir este orçamento? Esta ação não remove os contratos já anexados às máquinas." data-confirm-action="Excluir">
                @csrf
                @method('delete')
                <button class="btn danger" type="submit">Excluir</button>
            </form>
        @endif
    </div>
</div>

@php
    $itensOrcamento = old('itens');

    if ($itensOrcamento === null) {
        $itensOrcamento = $orcamento->exists && $orcamento->relationLoaded('itens')
            ? $orcamento->itens->map(fn ($item) => [
                'maquina_id' => $item->maquina_id,
                'descricao' => $item->descricao,
                'contrato_inicio' => $item->contrato_inicio?->format('Y-m-d'),
                'contrato_fim' => $item->contrato_fim?->format('Y-m-d'),
                'quantidade' => (int) $item->quantidade,
                'valor_unitario' => $item->valor_unitario,
            ])->values()->all()
            : [];
    }

    if (empty($itensOrcamento)) {
        $itensOrcamento = [[
            'maquina_id' => '',
            'descricao' => '',
            'contrato_inicio' => '',
            'contrato_fim' => '',
            'quantidade' => 1,
            'valor_unitario' => 0,
        ]];
    }

    $statusAtual = old('status', $orcamento->status ?: 'rascunho');
@endphp

<form class="panel grid grid-2" method="post" enctype="multipart/form-data" action="{{ $orcamento->exists ? route('admin.orcamentos.update', $orcamento) : route('admin.orcamentos.store') }}">
    @csrf
    @if($orcamento->exists) @method('patch') @endif

    <label>Cliente
        <select name="locacao_cliente_id">
            <option value="">Selecione</option>
            @foreach($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected((string) old('locacao_cliente_id', $orcamento->locacao_cliente_id) === (string) $cliente->id)>{{ $cliente->nome }}</option>
            @endforeach
        </select>
    </label>

    <label>Status
        <select name="status" data-orcamento-status>
            @foreach(['rascunho' => 'Rascunho', 'enviado' => 'Enviado', 'aprovado' => 'Aprovado', 'recusado' => 'Recusado'] as $valor => $label)
                <option value="{{ $valor }}" @selected($statusAtual === $valor)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <section class="contrato-aprovado-box" data-contrato-aprovado {{ $statusAtual !== 'aprovado' ? 'hidden' : '' }}>
        <div>
            <strong>Contrato aprovado</strong>
            <small>Ao salvar aprovado, as máquinas dos itens ficam locadas automaticamente.</small>
            @if($orcamento->exists && $orcamento->status === 'aprovado')
                <a class="btn small primary" href="{{ route('admin.orcamentos.contrato', $orcamento) }}">Gerar contrato</a>
            @endif
        </div>
        <label>Data de faturamento
            <input type="date" name="data_faturamento" value="{{ old('data_faturamento', $orcamento->data_faturamento?->format('Y-m-d') ?: now()->format('Y-m-d')) }}">
        </label>
        <label>Anexar contrato
            <input type="file" name="contrato_arquivo" accept="application/pdf,image/jpeg,image/png,image/webp">
        </label>
    </section>

    <section class="orcamento-itens-card">
        <div class="section-head">
            <div>
                <h2>Itens do orçamento</h2>
                <p>Cada item calcula máquina x dias de contrato x valor diário.</p>
            </div>
            <button class="btn small primary" type="button" data-add-orcamento-item>Adicionar item</button>
        </div>
        <div class="orcamento-items" data-orcamento-items>
            @foreach($itensOrcamento as $index => $item)
                <div class="orcamento-item-row" data-orcamento-item-row>
                    <label>Máquina
                        <select name="itens[{{ $index }}][maquina_id]">
                            <option value="">Selecione</option>
                            @foreach($maquinas as $maquina)
                                <option value="{{ $maquina->id }}" @selected((string) ($item['maquina_id'] ?? '') === (string) $maquina->id)>{{ $maquina->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Descrição
                        <input name="itens[{{ $index }}][descricao]" value="{{ $item['descricao'] ?? '' }}" placeholder="Ex.: Locação diária">
                    </label>
                    <label>Início
                        <input type="date" name="itens[{{ $index }}][contrato_inicio]" value="{{ $item['contrato_inicio'] ?? '' }}" data-item-start>
                    </label>
                    <label>Término
                        <input type="date" name="itens[{{ $index }}][contrato_fim]" value="{{ $item['contrato_fim'] ?? '' }}" data-item-end>
                    </label>
                    <label>Dias de contrato
                        <input type="number" step="1" min="1" name="itens[{{ $index }}][quantidade]" value="{{ $item['quantidade'] ?? 1 }}" data-item-days>
                    </label>
                    <label>Valor diário (R$)
                        <input type="number" step="0.01" min="0" name="itens[{{ $index }}][valor_unitario]" value="{{ $item['valor_unitario'] ?? 0 }}" data-item-rate>
                    </label>
                    <div class="orcamento-item-total">
                        <span>Total do item</span>
                        <strong data-item-total>R$ 0,00</strong>
                    </div>
                    <button class="btn small danger" type="button" data-remove-orcamento-item>Remover</button>
                </div>
            @endforeach
        </div>
    </section>

    <div class="orcamento-fieldset">
        <label>Tipo de frete
            <select name="frete_tipo">
                @foreach(['cif' => 'CIF', 'fob' => 'FOB', 'outro' => 'Outro'] as $valor => $label)
                    <option value="{{ $valor }}" @selected(old('frete_tipo', $orcamento->frete_tipo ?: 'cif') === $valor)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Valor do frete (R$)
            <input type="number" step="0.01" min="0" name="valor_frete" value="{{ old('valor_frete', $orcamento->valor_frete ?? 0) }}" data-finance-frete>
        </label>
        <label>Desconto (%)
            <input type="number" step="0.01" min="0" max="100" name="desconto_percentual" value="{{ old('desconto_percentual', $orcamento->desconto_percentual ?? 0) }}" data-finance-desconto>
        </label>
        <label>Imposto (%)
            <input type="number" step="0.01" min="0" max="100" name="imposto_percentual" value="{{ old('imposto_percentual', $orcamento->imposto_percentual ?? 0) }}" data-finance-imposto>
        </label>
        <label>Condições de pagamento
            <select name="condicoes_pagamento" data-payment-select>
                <option value="">Selecione</option>
                @foreach(['Boleto' => 'Boleto', 'PIX' => 'PIX', 'Cartão' => 'Cartão'] as $valor => $label)
                    <option value="{{ $valor }}" @selected(old('condicoes_pagamento', $orcamento->condicoes_pagamento) === $valor)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label data-boleto-prazo>
            Prazo do boleto
            <input name="prazo_faturamento" value="{{ old('prazo_faturamento', $orcamento->prazo_faturamento) }}" placeholder="Ex.: 28 dias ou 28/35">
        </label>
    </div>
    <label style="grid-column:1 / -1">Observações comerciais
        <textarea name="observacoes" rows="3">{{ old('observacoes', $orcamento->observacoes) }}</textarea>
    </label>

    <section class="orcamento-calculo-card" data-orcamento-calculo>
        <div class="section-head">
            <div>
                <h2>Resumo financeiro</h2>
                <p>Atualizado automaticamente conforme itens, frete, desconto e imposto.</p>
            </div>
        </div>
        <table>
            <tbody>
                <tr>
                    <th>Itens</th>
                    <td data-finance-itens>R$ 0,00</td>
                </tr>
                <tr>
                    <th>Frete</th>
                    <td data-finance-frete-total>R$ 0,00</td>
                </tr>
                <tr>
                    <th>Base antes do desconto</th>
                    <td data-finance-base>R$ 0,00</td>
                </tr>
                <tr>
                    <th>Desconto</th>
                    <td data-finance-desconto-total>R$ 0,00</td>
                </tr>
                <tr>
                    <th>Subtotal</th>
                    <td data-finance-subtotal>R$ 0,00</td>
                </tr>
                <tr>
                    <th>Imposto</th>
                    <td data-finance-imposto-total>R$ 0,00</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total do orçamento</td>
                    <td data-finance-total>R$ 0,00</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ route('admin.orcamentos.index') }}">Cancelar</a>
            <button class="btn primary" type="submit">Salvar</button>
        </div>
    </div>
</form>

<template data-orcamento-item-template>
    <div class="orcamento-item-row" data-orcamento-item-row>
        <label>Máquina
            <select name="itens[__INDEX__][maquina_id]">
                <option value="">Selecione</option>
                @foreach($maquinas as $maquina)
                    <option value="{{ $maquina->id }}">{{ $maquina->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>Descrição
            <input name="itens[__INDEX__][descricao]" value="" placeholder="Ex.: Locação diária">
        </label>
        <label>Início
            <input type="date" name="itens[__INDEX__][contrato_inicio]" value="" data-item-start>
        </label>
        <label>Término
            <input type="date" name="itens[__INDEX__][contrato_fim]" value="" data-item-end>
        </label>
        <label>Dias de contrato
            <input type="number" step="1" min="1" name="itens[__INDEX__][quantidade]" value="1" data-item-days>
        </label>
        <label>Valor diário (R$)
            <input type="number" step="0.01" min="0" name="itens[__INDEX__][valor_unitario]" value="0" data-item-rate>
        </label>
        <div class="orcamento-item-total">
            <span>Total do item</span>
            <strong data-item-total>R$ 0,00</strong>
        </div>
        <button class="btn small danger" type="button" data-remove-orcamento-item>Remover</button>
    </div>
</template>

<script>
const itemsList = document.querySelector('[data-orcamento-items]');
const itemTemplate = document.querySelector('[data-orcamento-item-template]');
const addItemButton = document.querySelector('[data-add-orcamento-item]');
const statusSelect = document.querySelector('[data-orcamento-status]');
const contratoAprovado = document.querySelector('[data-contrato-aprovado]');
const paymentSelect = document.querySelector('[data-payment-select]');
const boletoPrazo = document.querySelector('[data-boleto-prazo]');
let itemIndex = {{ count($itensOrcamento) }};
const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

const parseNumber = (value) => {
    const raw = String(value ?? '0').trim();
    const normalized = raw.includes(',') ? raw.replace(/\./g, '').replace(',', '.') : raw;
    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
};

const inclusiveDays = (start, end) => {
    if (!start || !end) {
        return null;
    }

    const startDate = new Date(`${start}T00:00:00`);
    const endDate = new Date(`${end}T00:00:00`);

    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
        return null;
    }

    return Math.max(Math.round((endDate - startDate) / 86400000) + 1, 1);
};

const setText = (selector, value) => {
    const target = document.querySelector(selector);

    if (target) {
        target.textContent = money.format(value);
    }
};

function recalculateOrcamento() {
    let itens = 0;

    document.querySelectorAll('[data-orcamento-item-row]').forEach((row) => {
        const start = row.querySelector('[data-item-start]')?.value;
        const end = row.querySelector('[data-item-end]')?.value;
        const daysInput = row.querySelector('[data-item-days]');
        const daysFromDates = inclusiveDays(start, end);

        if (daysFromDates && daysInput) {
            daysInput.value = daysFromDates;
        }

        const days = Math.max(parseNumber(daysInput?.value), 1);
        const rate = parseNumber(row.querySelector('[data-item-rate]')?.value);
        const total = days * rate;
        itens += total;

        const rowTotal = row.querySelector('[data-item-total]');
        if (rowTotal) {
            rowTotal.textContent = money.format(total);
        }
    });

    const frete = parseNumber(document.querySelector('[data-finance-frete]')?.value);
    const descontoPercentual = parseNumber(document.querySelector('[data-finance-desconto]')?.value);
    const impostoPercentual = parseNumber(document.querySelector('[data-finance-imposto]')?.value);
    const base = itens + frete;
    const desconto = base * (Math.min(Math.max(descontoPercentual, 0), 100) / 100);
    const subtotal = Math.max(base - desconto, 0);
    const imposto = subtotal * (Math.max(impostoPercentual, 0) / 100);
    const total = subtotal + imposto;

    setText('[data-finance-itens]', itens);
    setText('[data-finance-frete-total]', frete);
    setText('[data-finance-base]', base);
    setText('[data-finance-desconto-total]', desconto);
    setText('[data-finance-subtotal]', subtotal);
    setText('[data-finance-imposto-total]', imposto);
    setText('[data-finance-total]', total);
}

addItemButton?.addEventListener('click', () => {
    const html = itemTemplate.innerHTML.replaceAll('__INDEX__', String(itemIndex++));
    itemsList.insertAdjacentHTML('beforeend', html);
    recalculateOrcamento();
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-orcamento-item]');

    if (!button) {
        return;
    }

    button.closest('[data-orcamento-item-row]')?.remove();
    recalculateOrcamento();
});

document.addEventListener('input', (event) => {
    if (event.target.closest('[data-orcamento-item-row]') || event.target.matches('[data-finance-frete], [data-finance-desconto], [data-finance-imposto]')) {
        recalculateOrcamento();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.closest('[data-orcamento-item-row]') || event.target.matches('[data-finance-frete], [data-finance-desconto], [data-finance-imposto]')) {
        recalculateOrcamento();
    }
});

statusSelect?.addEventListener('change', () => {
    if (!contratoAprovado) {
        return;
    }

    contratoAprovado.hidden = statusSelect.value !== 'aprovado';
});

function syncBoletoPrazo() {
    if (!paymentSelect || !boletoPrazo) {
        return;
    }

    boletoPrazo.hidden = paymentSelect.value !== 'Boleto';
}

paymentSelect?.addEventListener('change', syncBoletoPrazo);

recalculateOrcamento();
syncBoletoPrazo();
</script>
@endsection
