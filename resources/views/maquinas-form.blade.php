@extends('layouts.admin')

@section('content')
<style>
    .machine-data-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(250px, 320px);
        gap: 16px;
        align-items: start;
    }

    .machine-file-box {
        align-self: start;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface-muted);
        padding: 12px;
    }

    .machine-file-box label + label {
        margin-top: 12px;
    }

    .machine-file-box span {
        font-size: 12px;
        line-height: 1.35;
    }

    @media (max-width: 820px) {
        .machine-data-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="top">
    <div>
        <h1>{{ $maquina->exists ? 'Editar máquina' : 'Nova máquina' }}</h1>
        <p>{{ $maquina->exists ? 'Edite dados técnicos, arquivos e rastreamento. Locação e contrato ficam na aba Locação dentro da máquina.' : 'Ao criar, a máquina recebe os checklists padrão cadastrados em Checklists.' }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ $maquina->exists ? route('admin.maquinas.show', $maquina) : route('admin.maquinas.index') }}">Voltar</a>
        @if($maquina->exists)
            <form class="action-form" method="post" action="{{ route('admin.maquinas.destroy', $maquina) }}" data-confirm="Excluir esta máquina? Os históricos vinculados podem ser afetados.">
                @csrf
                @method('delete')
                <button class="btn danger" type="submit">Excluir</button>
            </form>
        @endif
    </div>
</div>

<form class="panel grid grid-2" method="post" enctype="multipart/form-data" action="{{ $maquina->exists ? route('admin.maquinas.update', $maquina) : route('admin.maquinas.store') }}">
    @csrf
    @if($maquina->exists) @method('patch') @endif

    <section class="stack" style="grid-column:1 / -1">
        <div>
            <h2>Dados da máquina</h2>
            <p>Identificação técnica e status operacional.</p>
        </div>
        <div class="machine-data-layout">
            <div class="grid grid-2">
                <label>Nome <input name="nome" value="{{ old('nome', $maquina->nome) }}" required></label>
                <label>Setor <input name="setor" value="{{ old('setor', $maquina->setor) }}"></label>
                <label>Modelo <input name="modelo" value="{{ old('modelo', $maquina->modelo) }}"></label>
                <label>Código interno <input name="codigo_interno" value="{{ old('codigo_interno', $maquina->codigo_interno) }}"></label>
                <label>Horímetro inicial
                    <input name="horimetro_inicial_horas" type="number" min="0" step="0.01" value="{{ old('horimetro_inicial_horas', $maquina->horimetro_inicial_horas) }}" placeholder="Ex.: 1250.5">
                    <span>Horas anteriores ao início do rastreamento.</span>
                </label>
                <label>Vencimento da ART de segurança <input name="art_seguranca_vencimento" type="date" value="{{ old('art_seguranca_vencimento', $maquina->art_seguranca_vencimento?->format('Y-m-d')) }}"></label>
                <label>Status
                    <select name="status">
                        <option value="ativa" @selected(old('status', $maquina->status) === 'ativa')>Ativa</option>
                        <option value="inativa" @selected(old('status', $maquina->status) === 'inativa')>Inativa</option>
                    </select>
                </label>
            </div>
            <div class="machine-file-box">
                <label>Manuais, imagens ou PDFs
                    <input type="file" name="arquivos[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple>
                    <span>JPG, PNG, WEBP e PDF. Máximo de 10 MB por arquivo.</span>
                </label>
            </div>
        </div>
    </section>

    @if($rastreamentoHabilitado && ($podeGerenciarRastreamento ?? false))
        <section class="stack" style="grid-column:1 / -1">
            <div>
                <h2>Rastreamento</h2>
                <p>Dados técnicos para integrar o rastreador GV50CG via Flespi.</p>
            </div>
            <div class="grid grid-2">
                <label>IMEI do rastreador
                    <input name="rastreador_imei" value="{{ old('rastreador_imei', $maquina->rastreador?->imei) }}" placeholder="Informe depois que receber o equipamento">
                </label>
                <label>Modelo
                    <input name="rastreador_modelo" value="{{ old('rastreador_modelo', $maquina->rastreador?->modelo ?: 'Queclink GV50CG') }}">
                </label>
                <label>Operadora do chip
                    <input name="rastreador_operadora" value="{{ old('rastreador_operadora', $maquina->rastreador?->operadora) }}" placeholder="Ex.: Vivo, Claro, TIM ou M2M">
                </label>
                <label>Número/identificação do chip
                    <input name="rastreador_chip_numero" value="{{ old('rastreador_chip_numero', $maquina->rastreador?->chip_numero) }}">
                </label>
                <label style="grid-column:1 / -1">Observações do rastreador
                    <textarea name="rastreador_observacao" placeholder="Anote canal Flespi, APN, local de instalação ou instruções de teste.">{{ old('rastreador_observacao', $maquina->rastreador?->observacao) }}</textarea>
                </label>
            </div>
        </section>
    @endif

    <div class="form-footer">
        <span></span>
        <div class="actions">
            <a class="btn" href="{{ $maquina->exists ? route('admin.maquinas.show', $maquina) : route('admin.maquinas.index') }}">Cancelar</a>
            <button class="btn primary" type="submit">Salvar</button>
        </div>
    </div>
</form>

@if($maquina->exists && $maquina->arquivos->isNotEmpty())
    <section class="panel table-wrap" style="margin-top:16px">
        <h2>Arquivos cadastrados</h2>
        <table>
            <thead><tr><th>Arquivo</th><th>Categoria</th><th>Tipo</th><th>Tamanho</th><th></th></tr></thead>
            <tbody>
            @foreach($maquina->arquivos as $arquivo)
                <tr>
                    <td>{{ $arquivo->nome_original }}</td>
                    <td><span class="badge">{{ $arquivo->categoria_label }}</span></td>
                    <td><span class="badge">{{ $arquivo->tipo }}</span></td>
                    <td>{{ number_format($arquivo->tamanho / 1024 / 1024, 2, ',', '.') }} MB</td>
                    <td>
                        <div class="table-actions">
                            <a class="btn small primary" href="{{ route('admin.maquinas.arquivos.show', [$maquina, $arquivo]) }}" target="_blank">Abrir</a>
                            <form class="action-form" method="post" action="{{ route('admin.maquinas.arquivos.destroy', [$maquina, $arquivo]) }}" data-confirm="Excluir este arquivo da máquina?">
                                @csrf
                                @method('delete')
                                <button class="btn small danger" type="submit">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endif
@endsection
