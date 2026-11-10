@extends('layouts.admin')

@section('content')
<style>
    .quote-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
    }

    .quote-box {
        display: grid;
        gap: 10px;
        min-width: 0;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f8fafc;
    }

    .quote-box h3 {
        margin: 0;
    }

    .quote-text-box {
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }
</style>

<div class="top">
    <div>
        <h1>{{ $orcamento->numero ?: 'Orçamento #'.$orcamento->id }}</h1>
        <p>{{ $orcamento->titulo }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.orcamentos.index') }}">Voltar</a>
        <a class="btn primary" href="{{ route('admin.orcamentos.edit', $orcamento) }}">Editar</a>
        <a class="btn primary" href="{{ route('admin.orcamentos.pdf', $orcamento) }}">Exportar PDF</a>
        <form class="action-form" method="post" action="{{ route('admin.orcamentos.destroy', $orcamento) }}" data-confirm="Excluir este orçamento?">
            @csrf
            @method('delete')
            <button class="btn danger" type="submit">Excluir</button>
        </form>
    </div>
</div>

<div class="quote-summary">
    <section class="panel stack">
        <div>
            <h2>Cliente e contato</h2>
            <p>Dados principais para envio da proposta.</p>
        </div>
        <div class="metric-list">
            <div class="metric-row"><span>Cliente</span><strong>{{ $orcamento->cliente?->nome ?? 'Sem cliente selecionado' }}</strong></div>
            <div class="metric-row"><span>Documento</span><strong>{{ $orcamento->cliente?->documento ?: 'Não informado' }}</strong></div>
            <div class="metric-row"><span>Contato</span><strong>{{ $orcamento->contato_nome ?: 'Sem contato' }}</strong></div>
            <div class="metric-row"><span>Telefone</span><strong>{{ $orcamento->contato_telefone ?: 'Não informado' }}</strong></div>
            <div class="metric-row"><span>E-mail</span><strong>{{ $orcamento->contato_email ?: 'Não informado' }}</strong></div>
        </div>
    </section>

    <section class="panel stack">
        <div>
            <h2>Resumo financeiro</h2>
            <p>Valores definidos para a proposta.</p>
        </div>
        <div class="metric-list">
            <div class="metric-row"><span>Locação</span><strong>R$ {{ number_format((float) $orcamento->valor_locacao, 2, ',', '.') }}</strong></div>
            <div class="metric-row"><span>Frete</span><strong>R$ {{ number_format((float) $orcamento->valor_frete, 2, ',', '.') }}</strong></div>
            <div class="metric-row"><span>Desconto</span><strong>{{ number_format((float) $orcamento->desconto_percentual, 2, ',', '.') }}%</strong></div>
            <div class="metric-row"><span>Total</span><strong>R$ {{ number_format($orcamento->valorTotal(), 2, ',', '.') }}</strong></div>
        </div>
    </section>
</div>

<section class="panel stack" style="margin-top:16px">
    <div class="section-head">
        <div>
            <h2>Condições da locação</h2>
            <p>Máquina, período e observações comerciais.</p>
        </div>
        <span class="status-line">
            <i class="status-dot {{ in_array($orcamento->status, ['aprovado', 'enviado'], true) ? 'ok' : ($orcamento->status === 'recusado' ? 'danger' : '') }}"></i>
            <span>{{ ucfirst($orcamento->status) }}</span>
        </span>
    </div>
    <div class="metric-list">
        <div class="metric-row"><span>Máquina</span><strong>{{ $orcamento->maquina?->nome ?? 'Sem máquina' }}</strong></div>
        <div class="metric-row"><span>Data</span><strong>{{ $orcamento->data_orcamento?->format('d/m/Y') }}</strong></div>
        <div class="metric-row"><span>Validade</span><strong>{{ $orcamento->validade?->format('d/m/Y') ?? 'Não informada' }}</strong></div>
        <div class="metric-row"><span>Dias somados</span><strong>{{ $orcamento->tempo_contrato ?: 'Não informado' }}</strong></div>
        <div class="metric-row"><span>Contrato</span><strong>{{ $orcamento->contrato_inicio?->format('d/m/Y') ?? 'sem início' }} a {{ $orcamento->contrato_fim?->format('d/m/Y') ?? 'sem término' }}</strong></div>
        <div class="metric-row"><span>Pagamento</span><strong>{{ $orcamento->condicoes_pagamento ?: 'Não informado' }}@if($orcamento->condicoes_pagamento === 'Boleto' && $orcamento->prazo_faturamento) - {{ $orcamento->prazo_faturamento }}@endif</strong></div>
    </div>
    <div class="quote-text-box">
        <h3>Descrição</h3>
        <p>{{ $orcamento->descricao ?: 'Sem descrição.' }}</p>
    </div>
    <div class="quote-text-box">
        <h3>Observações</h3>
        <p>{{ $orcamento->observacoes ?: 'Sem observações.' }}</p>
    </div>
</section>
@endsection
