@extends('layouts.admin')

@section('content')
@php
    $registro = $alerta->registro;
    $maquina = $alerta->maquina;
    $respostas = $registro ? $registro->respostas : collect();
    $anexos = $registro ? $registro->anexos : collect();
    $statusLabels = [
        'pendente' => 'Pendente',
        'em_analise' => 'Em análise',
        'resolvido' => 'Resolvido',
        'falso_positivo' => 'Ignorar',
    ];
    $isRelatoProblema = ! $registro || $registro->tipo === 'problema' || ! $alerta->formulario_item_id;
    $tituloAlerta = $isRelatoProblema ? 'Relato de problema' : optional($alerta->item)->pergunta;
    $tituloAlerta = $tituloAlerta ?: 'Item removido';
    $evidenciasTexto = $isRelatoProblema
        ? $respostas->whereNull('formulario_item_id')
        : $respostas
            ->where('formulario_item_id', $alerta->formulario_item_id)
            ->filter(function ($resposta) {
                return str_starts_with((string) $resposta->resposta, 'Observação do não conforme:');
            });
    $evidenciasFoto = $isRelatoProblema
        ? $anexos
        : $anexos->where('formulario_item_id', $alerta->formulario_item_id);
    $voltouDaMaquina = request('from_maquina') && $maquina && (string) request('from_maquina') === (string) $maquina->id;
    $voltarUrl = $voltouDaMaquina
        ? route('admin.maquinas.show', $maquina).'#alertas-problemas'
        : route('admin.alertas.index');
@endphp
<style>
    .alert-detail-list {
        display: grid;
        gap: 8px;
    }

    .alert-detail-row {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr);
        gap: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--border);
    }

    .alert-detail-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .alert-detail-row span {
        color: var(--text-faint);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .alert-detail-row strong {
        color: var(--text);
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    @media (max-width: 620px) {
        .alert-detail-row {
            grid-template-columns: 1fr;
            gap: 3px;
        }
    }
</style>

<div class="top">
    <div>
        <h1>Alerta #{{ $alerta->id }}</h1>
        <p>{{ $maquina->nome ?? 'Máquina removida' }} - {{ $tituloAlerta }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ $voltarUrl }}">Voltar</a>
        @if($registro)
            <a class="btn" href="{{ route('admin.historico.show', ['registro' => $registro, 'from_maquina' => $voltouDaMaquina ? $maquina->id : null]) }}">{{ $isRelatoProblema ? 'Ver registro completo' : 'Ver checklist completo' }}</a>
        @else
            <span class="btn" aria-disabled="true">Registro removido</span>
        @endif
        <form class="action-form" method="post" action="{{ route('admin.alertas.destroy', ['alerta' => $alerta, 'from_maquina' => $voltouDaMaquina && $maquina ? $maquina->id : null]) }}" data-confirm="Arquivar este alerta?">
            @csrf
            @method('delete')
            <button class="btn danger" type="submit">Excluir</button>
        </form>
    </div>
</div>

<div class="grid grid-2">
    <form class="panel stack alert-card-status-{{ str_replace('_', '-', $alerta->status) }}" method="post" action="{{ route('admin.alertas.update', ['alerta' => $alerta, 'from_maquina' => $voltouDaMaquina && $maquina ? $maquina->id : null]) }}">
        @csrf
        @method('patch')
        <label>Status
            <select name="status">
                @foreach (['pendente', 'em_analise', 'resolvido', 'falso_positivo'] as $status)
                    <option value="{{ $status }}" @selected($alerta->status === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                @endforeach
            </select>
        </label>
        <label>Observação
            <textarea name="observacao">{{ old('observacao', $alerta->observacao) }}</textarea>
        </label>
        <div class="actions end">
            <a class="btn" href="{{ $voltarUrl }}">Cancelar</a>
            <button class="btn primary" type="submit">Salvar tratativa</button>
        </div>
    </form>

    <div class="panel stack">
        <div>
            <h2>Dados originais</h2>
            <div class="alert-detail-list">
                <div class="alert-detail-row"><span>Tipo</span><strong>{{ $tituloAlerta }}</strong></div>
                <div class="alert-detail-row"><span>Responsável</span><strong>{{ optional($alerta->operador)->nome ?? 'Acesso não identificado' }}</strong></div>
                <div class="alert-detail-row"><span>Data</span><strong>{{ $alerta->created_at->format('d/m/Y H:i') }}</strong></div>
                <div class="alert-detail-row"><span>Status geral</span><strong>{{ $registro->status_geral ?? 'Registro não encontrado' }}</strong></div>
            </div>
        </div>

        <div>
            <h2>{{ $isRelatoProblema ? 'Relato enviado' : 'Evidência do não conforme' }}</h2>
            @if($isRelatoProblema)
                @forelse ($evidenciasTexto as $resposta)
                    <p>{{ $resposta->resposta }}</p>
                @empty
                    <p>Nenhuma descrição enviada.</p>
                @endforelse
            @else
                @forelse ($evidenciasTexto as $resposta)
                    <p>{{ str_replace('Observação do não conforme: ', '', $resposta->resposta) }}</p>
                @empty
                    <p>Nenhuma observação enviada.</p>
                @endforelse
            @endif

            @forelse ($evidenciasFoto as $anexo)
                <p>
                    <a class="btn small primary" href="{{ route('admin.historico.anexos.show', $anexo) }}" target="_blank">
                        Abrir arquivo enviado
                    </a>
                </p>
            @empty
                <p>Nenhum arquivo enviado.</p>
            @endforelse
        </div>

        <div>
            <h3>Histórico</h3>
            <p>
                <span class="status-line">
                    <i class="status-dot alert-status-{{ str_replace('_', '-', $alerta->status) }}"></i>
                    <span>Status atual: {{ $statusLabels[$alerta->status] ?? $alerta->status }}</span>
                </span>
            </p>
            @if(filled($alerta->observacao))
                <p><strong>Tratativa atual:</strong> {{ $alerta->observacao }}</p>
            @endif
            @forelse ($alerta->historicoStatus as $historico)
                <div class="history-row">
                    <strong>{{ $historico->alterado_em->format('d/m/Y H:i') }}</strong>
                    <p>Status: {{ $statusLabels[$historico->status_anterior] ?? $historico->status_anterior ?? 'Sem status anterior' }} para {{ $statusLabels[$historico->status_novo] ?? $historico->status_novo }}</p>
                    <small>Alterado por {{ optional($historico->admin)->nome ?? 'administrador' }}</small>
                    @if(filled($historico->observacao))
                        <p><strong>O que foi feito:</strong> {{ $historico->observacao }}</p>
                    @else
                        <p><strong>O que foi feito:</strong> Nenhuma tratativa informada neste registro.</p>
                    @endif
                </div>
            @empty
                <p>Nenhuma alteração de status.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
