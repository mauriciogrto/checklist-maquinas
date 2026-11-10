@extends('layouts.admin')

@section('content')
<style>
    .quote-wrap {
        overflow-x: visible;
    }

    .quote-table {
        min-width: 0;
        table-layout: fixed;
    }

    .quote-table th:nth-child(1) { width: 16%; }
    .quote-table th:nth-child(2) { width: 20%; }
    .quote-table th:nth-child(3) { width: 24%; }
    .quote-table th:nth-child(4) { width: 16%; }
    .quote-table th:nth-child(5) { width: 14%; }
    .quote-table th:nth-child(6) { width: 12%; }

    .quote-cell {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .quote-cell strong,
    .quote-cell span {
        overflow-wrap: anywhere;
    }

    .quote-cell small {
        color: var(--text-faint);
        font-size: 12px;
        line-height: 1.35;
    }

    .quote-actions {
        display: grid;
        gap: 6px;
        min-width: 90px;
    }

    .quote-actions .btn {
        width: 100%;
        min-height: 32px;
        padding: 7px 10px;
        white-space: nowrap;
    }

    .quote-actions form {
        margin: 0;
    }

    @media (max-width: 760px) {
        .quote-wrap table,
        .quote-wrap thead,
        .quote-wrap tbody,
        .quote-wrap tr,
        .quote-wrap th,
        .quote-wrap td {
            display: block;
            min-width: 0;
            width: 100%;
        }

        .quote-wrap thead {
            display: none;
        }

        .quote-wrap tbody {
            display: grid;
            gap: 10px;
        }

        .quote-wrap tr {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: #ffffff;
        }

        .quote-wrap td {
            display: grid;
            grid-template-columns: 92px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding: 8px 0;
            border-bottom: 1px solid rgba(226, 232, 240, .75);
            white-space: normal;
        }

        .quote-wrap td:last-child {
            display: block;
            border-bottom: 0;
        }

        .quote-wrap td::before {
            content: attr(data-label);
            color: var(--text-faint);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .quote-wrap td:last-child::before {
            display: none;
        }

        .quote-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            min-width: 0;
        }
    }
</style>

<div class="top top-filter">
    <div class="title-copy">
        <h1>Orçamentos</h1>
        <a class="btn primary" href="{{ route('admin.orcamentos.create') }}">Novo orçamento</a>
    </div>
    <form class="top-filter-form" method="get" action="{{ route('admin.orcamentos.index') }}">
        <label>Busca
            <input name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="Número, cliente ou item">
        </label>
        <label>Cliente
            <select name="locacao_cliente_id">
                <option value="">Todos</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected((string) ($filtros['locacao_cliente_id'] ?? '') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                @foreach(['rascunho' => 'Rascunho', 'enviado' => 'Enviado', 'aprovado' => 'Aprovado', 'recusado' => 'Recusado'] as $valor => $label)
                    <option value="{{ $valor }}" @selected(($filtros['status'] ?? '') === $valor)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="top-filter-actions">
            <a class="btn small" href="{{ route('admin.orcamentos.index') }}">Limpar</a>
            <button class="btn small primary" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<section class="panel table-wrap quote-wrap">
    <table class="quote-table">
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Itens</th>
                <th>Datas</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($orcamentos as $orcamento)
            <tr>
                <td data-label="Número">
                    <div class="quote-cell">
                        <strong>{{ $orcamento->numero ?: '#'.$orcamento->id }}</strong>
                        <small>Total: R$ {{ number_format($orcamento->valorTotal(), 2, ',', '.') }}</small>
                    </div>
                </td>
                <td data-label="Cliente"><div class="quote-cell"><strong>{{ $orcamento->cliente?->nome ?? 'Sem cliente' }}</strong></div></td>
                <td data-label="Itens">
                    <div class="quote-cell">
                        @if($orcamento->itens->isNotEmpty())
                            <strong>{{ $orcamento->itens->count() }} item{{ $orcamento->itens->count() === 1 ? '' : 's' }}</strong>
                            <small>{{ $orcamento->itens->pluck('descricao')->filter()->take(2)->implode(', ') ?: 'Sem descrição' }}</small>
                        @else
                            <strong>{{ $orcamento->maquina?->nome ?? 'Sem itens' }}</strong>
                        @endif
                    </div>
                </td>
                <td data-label="Datas">
                    <div class="quote-cell">
                        <strong>{{ $orcamento->data_orcamento?->format('d/m/Y') }}</strong>
                        <small>Validade: {{ $orcamento->validade?->format('d/m/Y') ?? 'não informada' }}</small>
                    </div>
                </td>
                <td data-label="Status">
                    <span class="status-line">
                        <i class="status-dot {{ in_array($orcamento->status, ['aprovado', 'enviado'], true) ? 'ok' : ($orcamento->status === 'recusado' ? 'danger' : '') }}"></i>
                        <span>{{ ucfirst($orcamento->status) }}</span>
                    </span>
                </td>
                <td data-label="Ações">
                    <div class="quote-actions">
                        <a class="btn small primary" href="{{ route('admin.orcamentos.edit', $orcamento) }}">Editar</a>
                        <a class="btn small" href="{{ route('admin.orcamentos.pdf', $orcamento) }}">PDF</a>
                        @if($orcamento->status === 'aprovado')
                            <a class="btn small" href="{{ route('admin.orcamentos.contrato', $orcamento) }}">Contrato</a>
                        @endif
                        <form method="post" action="{{ route('admin.orcamentos.destroy', $orcamento) }}" data-confirm="Excluir este orçamento? Esta ação não remove os contratos já anexados às máquinas." data-confirm-action="Excluir">
                            @csrf
                            @method('delete')
                            <button class="btn small danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Nenhum orçamento cadastrado.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</section>

{{ $orcamentos->links() }}
@endsection
