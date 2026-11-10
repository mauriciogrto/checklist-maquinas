@extends('layouts.admin')

@section('content')
@php
    $abasCliente = [
        'resumo' => 'Resumo',
        'maquinas' => 'Máquinas',
        'orcamentos' => 'Orçamentos',
    ];
@endphp

<div class="top">
    <div>
        <h1>{{ $cliente->nome }}</h1>
        <p>{{ $cliente->documento ?: 'Documento não informado' }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.clientes.index') }}">Voltar</a>
        <a class="btn primary" href="{{ route('admin.clientes.edit', $cliente) }}">Editar cliente</a>
    </div>
</div>

<nav class="tabs" aria-label="Seções do cliente">
    @foreach($abasCliente as $chave => $label)
        <a class="{{ $aba === $chave ? 'active' : '' }}" href="{{ route('admin.clientes.show', ['cliente' => $cliente, 'aba' => $chave]) }}">{{ $label }}</a>
    @endforeach
</nav>

@if($aba === 'resumo')
    <section class="grid grid-3">
        <div class="card">
            <span>Status</span>
            <strong>{{ ucfirst($cliente->status) }}</strong>
        </div>
        <div class="card">
            <span>Máquinas vinculadas</span>
            <strong>{{ $cliente->maquinas_count }}</strong>
        </div>
        <div class="card">
            <span>Orçamentos</span>
            <strong>{{ $cliente->orcamentos_count }}</strong>
        </div>
    </section>

    <section class="panel stack" style="margin-top:16px">
        <div class="section-head">
            <div>
                <h2>Dados do cliente</h2>
                <p>Informações usadas em contratos, orçamentos e vínculos operacionais.</p>
            </div>
        </div>
        <div class="grid grid-2">
            <div>
                <strong>Contato</strong>
                <p>{{ $cliente->telefone ?: 'Telefone não informado' }}</p>
                <p>{{ $cliente->email ?: 'E-mail não informado' }}</p>
            </div>
            <div>
                <strong>Endereço</strong>
                <p>{{ $cliente->endereco ?: 'Endereço não informado' }}</p>
            </div>
        </div>
        @if($cliente->observacoes)
            <div>
                <strong>Observações</strong>
                <p>{{ $cliente->observacoes }}</p>
            </div>
        @endif
    </section>
@elseif($aba === 'maquinas')
    <section class="panel table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Máquina</th>
                    <th>Situação</th>
                    <th>Contrato</th>
                    <th>Rastreamento</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cliente->maquinas as $maquina)
                <tr>
                    <td>
                        <strong>{{ $maquina->nome }}</strong>
                        <small>{{ $maquina->codigo_interno ?: 'Sem código interno' }}</small>
                    </td>
                    <td><span class="badge {{ $maquina->locacaoStatusClasse() }}">{{ $maquina->locacaoStatusLabel() }}</span></td>
                    <td>
                        <strong>{{ $maquina->contadorContrato() }}</strong>
                        <small>{{ $maquina->contrato_fim?->format('d/m/Y') ?? 'Sem término' }}</small>
                    </td>
                    <td>
                        <span class="badge {{ $maquina->rastreador?->statusClasse() }}">{{ $maquina->rastreador?->statusLabel() ?? 'Sem rastreador' }}</span>
                    </td>
                    <td><a class="btn small primary" href="{{ route('admin.maquinas.show', $maquina) }}">Abrir</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma máquina vinculada a este cliente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@elseif($aba === 'orcamentos')
    <section class="panel table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Orçamento</th>
                    <th>Status</th>
                    <th>Validade</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cliente->orcamentos as $orcamento)
                <tr>
                    <td>
                        <strong>{{ $orcamento->numero }}</strong>
                        <small>{{ $orcamento->data_orcamento?->format('d/m/Y') ?? 'Sem data' }}</small>
                    </td>
                    <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $orcamento->status)) }}</span></td>
                    <td>{{ $orcamento->validade?->format('d/m/Y') ?? 'Sem validade' }}</td>
                    <td><strong>R$ {{ number_format($orcamento->valorTotal(), 2, ',', '.') }}</strong></td>
                    <td><a class="btn small primary" href="{{ route('admin.orcamentos.edit', $orcamento) }}">Abrir</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum orçamento vinculado a este cliente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endif
@endsection
