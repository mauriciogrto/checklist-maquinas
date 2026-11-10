@extends('layouts.admin')

@section('content')
@php
    $labelsPadrao = [
        'diario' => 'Diário',
        'mensal' => 'Mensal',
        'trimestral' => 'Trimestral',
        'anual' => 'Anual',
    ];
    $labelsManutencao = \App\Services\FormularioService::TIPOS_MANUTENCAO;
@endphp
<style>
    .checklist-model-page {
        display: grid;
        gap: 14px;
    }

    .checklist-model-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .checklist-model-head h1 {
        margin: 0;
        font-size: 22px;
    }

    .checklist-model-head p {
        max-width: 760px;
        margin: 4px 0 0;
        color: var(--text-dim);
        font-size: 13px;
        line-height: 1.45;
    }

    .checklist-model-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 10px;
    }

    .checklist-model-card {
        display: grid;
        gap: 12px;
        padding: 13px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    .checklist-model-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .checklist-model-card h2 {
        margin: 0;
        font-size: 15px;
        line-height: 1.25;
    }

    .checklist-model-card small {
        display: block;
        margin-top: 3px;
        color: var(--text-dim);
        font-size: 12px;
    }

    .checklist-model-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .checklist-model-actions {
        display: flex;
        gap: 6px;
        align-items: center;
        justify-content: flex-end;
    }

    .checklist-model-actions .btn,
    .checklist-model-actions form {
        flex: 1 1 0;
    }

    .checklist-model-actions .btn {
        width: 100%;
        min-height: 34px;
        justify-content: center;
    }

    @media (max-width: 680px) {
        .checklist-model-head {
            display: grid;
        }

        .checklist-model-head .btn {
            width: 100%;
        }
    }
</style>

<div class="checklist-model-page">
    <div class="checklist-model-head">
        <div>
            <h1>Checklists</h1>
            <p>Edite os checklists padrão, as manutenções preventiva, preditiva e corretiva, além dos modelos extras. Alterações em checklist padrão valem somente para máquinas cadastradas depois da edição.</p>
        </div>
        <a class="btn primary" href="{{ route('admin.formularios-modelo.create') }}">Novo modelo extra</a>
    </div>

    <div class="checklist-model-grid">
        @foreach ($formularios as $formulario)
            @php
                $protegido = ($formulario->tipo === 'manutencao' && isset($labelsManutencao[$formulario->periodicidade])) || isset($labelsPadrao[$formulario->periodicidade]);
                $tipoLabel = $formulario->tipo === 'manutencao' ? 'Manutenção' : ($protegido ? 'Checklist padrão' : 'Checklist extra');
                $periodicidadeLabel = $formulario->tipo === 'manutencao'
                    ? ($labelsManutencao[$formulario->periodicidade] ?? 'Padrão antigo')
                    : ($labelsPadrao[$formulario->periodicidade] ?? 'Modelo extra');
            @endphp
            <article class="checklist-model-card">
                <div class="checklist-model-card-head">
                    <div>
                        <h2>{{ $formulario->nome }}</h2>
                        <small>{{ $periodicidadeLabel }}</small>
                    </div>
                    @if($protegido)
                        <span class="badge">Protegido</span>
                    @endif
                </div>

                <div class="checklist-model-meta">
                    <span class="badge">{{ $tipoLabel }}</span>
                    <span class="badge">{{ $formulario->itens_count }} itens</span>
                </div>

                <div class="checklist-model-actions">
                    <a class="btn small primary" href="{{ route('admin.formularios-modelo.edit', $formulario) }}">Editar</a>
                    @unless($protegido)
                        <form class="action-form" method="post" action="{{ route('admin.formularios-modelo.destroy', $formulario) }}" data-confirm="Excluir este modelo?">
                            @csrf
                            @method('delete')
                            <button class="btn small danger" type="submit">Excluir</button>
                        </form>
                    @endunless
                </div>
            </article>
        @endforeach
    </div>

    <div>
        {{ $formularios->links() }}
    </div>
</div>
@endsection
