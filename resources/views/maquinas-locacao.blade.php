@extends('layouts.admin')

@section('content')
@php
    $freteTipoAtual = old('frete_tipo', $maquina->frete_tipo);
    $freteTipos = [
        '' => 'Não informado',
        'CIF' => 'CIF',
        'FOB' => 'FOB',
        'Retirada pelo cliente' => 'Retirada pelo cliente',
        'Entrega própria' => 'Entrega própria',
        'Transportadora' => 'Transportadora',
        'A combinar' => 'A combinar',
    ];
@endphp

<div class="top">
    <div>
        <h1>Atualizar locação</h1>
        <p>{{ $maquina->nome }} · contrato, cliente, local, devolução e frete.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}#gestao-locacao">Voltar</a>
    </div>
</div>

<form class="panel grid grid-2" method="post" enctype="multipart/form-data" action="{{ route('admin.maquinas.locacao.update', $maquina) }}">
    @csrf
    @method('patch')

    <section class="stack" style="grid-column:1 / -1">
        <div>
            <h2>Locação e contrato</h2>
            <p>Atualize onde a máquina está locada, prazos e situação da devolução.</p>
        </div>
        <div class="grid grid-2">
            <label>Status de locação
                <select name="locacao_status">
                    @foreach(['disponivel' => 'Disponível', 'locada' => 'Locada', 'em_transporte' => 'Em transporte', 'devolvida' => 'Devolvida', 'manutencao' => 'Em manutenção'] as $valor => $label)
                        <option value="{{ $valor }}" @selected(old('locacao_status', $maquina->locacao_status ?: 'disponivel') === $valor)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Cliente cadastrado
                <select name="locacao_cliente_id" data-client-location-select>
                    <option value="">Sem cliente cadastrado</option>
                    @foreach($clientesLocacao as $cliente)
                        <option value="{{ $cliente->id }}" data-local="{{ $cliente->endereco }}" @selected((string) old('locacao_cliente_id', $maquina->locacao_cliente_id) === (string) $cliente->id)>{{ $cliente->nome }}</option>
                    @endforeach
                </select>
                @if($clientesLocacao->isEmpty())
                    <span>Cadastre clientes na aba Clientes para selecionar aqui.</span>
                @endif
            </label>
            <label>Local da máquina
                <input name="locacao_local" value="{{ old('locacao_local', $maquina->locacao_local) }}" placeholder="Ex.: Obra Centro, Galpão 2" data-machine-location-input>
            </label>
            <label>Início do contrato <input name="contrato_inicio" type="date" value="{{ old('contrato_inicio', $maquina->contrato_inicio?->format('Y-m-d')) }}"></label>
            <label>Término do contrato <input name="contrato_fim" type="date" value="{{ old('contrato_fim', $maquina->contrato_fim?->format('Y-m-d')) }}"></label>
            <label>Devolução prevista <input name="devolucao_prevista" type="date" value="{{ old('devolucao_prevista', $maquina->devolucao_prevista?->format('Y-m-d')) }}"></label>
            <label>Devolução realizada <input name="devolucao_realizada" type="date" value="{{ old('devolucao_realizada', $maquina->devolucao_realizada?->format('Y-m-d')) }}"></label>
        </div>
    </section>

    <section class="stack" style="grid-column:1 / -1">
        <div>
            <h2>Frete</h2>
            <p>Responsável, sistemática e observações logísticas da movimentação da máquina.</p>
        </div>
        <div class="grid grid-2">
            <label>Responsável pelo frete <input name="frete_responsavel" value="{{ old('frete_responsavel', $maquina->frete_responsavel) }}"></label>
            <label>Sistemática do frete
                <select name="frete_tipo">
                    @foreach($freteTipos as $valor => $label)
                        <option value="{{ $valor }}" @selected((string) $freteTipoAtual === (string) $valor)>{{ $label }}</option>
                    @endforeach
                    @if($freteTipoAtual && ! array_key_exists($freteTipoAtual, $freteTipos))
                        <option value="{{ $freteTipoAtual }}" selected>{{ $freteTipoAtual }}</option>
                    @endif
                </select>
            </label>
            <label style="grid-column:1 / -1">Observações de frete e logística
                <textarea name="frete_observacao" placeholder="Combine retirada, entrega, janela de horário, transportadora ou contato.">{{ old('frete_observacao', $maquina->frete_observacao) }}</textarea>
            </label>
        </div>
    </section>

    <section class="stack" style="grid-column:1 / -1">
        <div>
            <h2>Contrato anexado</h2>
            <p>Envie ou substitua o contrato vinculado à locação desta máquina.</p>
        </div>
        <div class="grid grid-2">
            <label>Arquivo do contrato
                <input type="file" name="contrato_arquivo" accept="application/pdf,image/jpeg,image/png,image/webp">
                <span>PDF ou imagem. Máximo de 15 MB.</span>
            </label>
            @if($maquina->contrato_atual)
                <div class="info-card">
                    <strong>Contrato atual</strong>
                    <span>{{ $maquina->contrato_atual->nome_original }}</span>
                    <a class="btn small primary" href="{{ route('admin.maquinas.arquivos.show', [$maquina, $maquina->contrato_atual]) }}" target="_blank">Abrir contrato</a>
                </div>
            @endif
        </div>
    </section>

    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ route('admin.maquinas.show', $maquina) }}#gestao-locacao">Cancelar</a>
            <button class="btn primary" type="submit">Atualizar locação</button>
        </div>
    </div>
</form>

<script>
    (() => {
        const clienteSelect = document.querySelector('[data-client-location-select]');
        const localInput = document.querySelector('[data-machine-location-input]');

        if (!clienteSelect || !localInput) {
            return;
        }

        clienteSelect.addEventListener('change', () => {
            const selected = clienteSelect.selectedOptions[0];
            const local = selected?.dataset.local?.trim();

            if (!local) {
                return;
            }

            localInput.value = local;
            localInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
    })();
</script>
@endsection
