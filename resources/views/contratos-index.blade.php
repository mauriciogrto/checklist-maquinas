@extends('layouts.admin')

@section('content')
<style>
    .contract-model-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(240px, 320px);
        gap: 14px;
        align-items: start;
    }

    .placeholder-list {
        display: grid;
        gap: 8px;
    }

    .placeholder-list code {
        display: inline-block;
        padding: 4px 6px;
        border-radius: 6px;
        background: #eef2f7;
        color: var(--text);
        font-size: 12px;
    }

    @media (max-width: 820px) {
        .contract-model-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="top">
    <div>
        <h1>Contratos</h1>
        <p>{{ ($somenteConsulta ?? false) ? 'Contratos gerados a partir dos orçamentos aprovados.' : 'Condições fixas usadas ao gerar contrato a partir de um orçamento aprovado.' }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.orcamentos.index') }}">Orçamentos</a>
    </div>
</div>

@if($somenteConsulta ?? false)
    <section class="panel table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Orçamento</th>
                    <th>Cliente</th>
                    <th>Máquinas</th>
                    <th>Período</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($orcamentosAprovados as $orcamento)
                <tr>
                    <td>
                        <strong>{{ $orcamento->numero ?: '#'.$orcamento->id }}</strong><br>
                        <small>Aprovado</small>
                    </td>
                    <td>{{ $orcamento->cliente?->nome ?? 'Cliente não informado' }}</td>
                    <td>
                        {{ $orcamento->itens->pluck('maquina.nome')->filter()->join(', ') ?: 'Sem máquina vinculada' }}
                    </td>
                    <td>
                        {{ $orcamento->contrato_inicio?->format('d/m/Y') ?? 'Sem início' }}
                        até
                        {{ $orcamento->contrato_fim?->format('d/m/Y') ?? 'sem término' }}
                    </td>
                    <td>
                        <a class="btn small primary" href="{{ route('admin.orcamentos.contrato', $orcamento) }}">Baixar contrato</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum orçamento aprovado com contrato disponível.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        {{ $orcamentosAprovados->links() }}
    </section>
@else
<form class="panel contract-model-grid" method="post" action="{{ route('admin.contratos.update') }}">
    @csrf
    @method('patch')

    <label>Condições fixas do contrato
        <textarea name="modelo" rows="24" required>{{ old('modelo', $modelo) }}</textarea>
        <span>Os dados de locadora, locatária, máquinas, valores e datas entram automaticamente pelo orçamento.</span>
    </label>

    <aside class="stack">
        <div>
            <h2>Campos disponíveis</h2>
            <p>Use estes códigos se precisar inserir dados variáveis dentro das cláusulas.</p>
        </div>
        <div class="placeholder-list">
            <code>@{{ locador_nome }}</code>
            <code>@{{ locador_documento }}</code>
            <code>@{{ cliente_nome }}</code>
            <code>@{{ cliente_documento }}</code>
            <code>@{{ orcamento_numero }}</code>
            <code>@{{ emissao_data }}</code>
            <code>@{{ emissao_hora }}</code>
            <code>@{{ data_faturamento }}</code>
            <code>@{{ contrato_inicio }}</code>
            <code>@{{ contrato_fim }}</code>
            <code>@{{ itens }}</code>
            <code>@{{ valor_total }}</code>
            <code>@{{ condicoes_pagamento }}</code>
            <code>@{{ frete_tipo }}</code>
            <code>@{{ observacoes }}</code>
        </div>
        <div class="actions">
            <button class="btn primary" type="submit">Salvar modelo</button>
        </div>
    </aside>
</form>
@endif
@endsection
