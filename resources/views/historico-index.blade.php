@extends('layouts.admin')

@section('content')
<div class="top top-filter">
    <div class="title-copy">
        <h1>Histórico</h1>
    </div>
    <form class="top-filter-form" method="get">
        <label>Máquina
            <select name="maquina_id">
                <option value="">Todas</option>
                @foreach($maquinas as $maquina)
                    <option value="{{ $maquina->id }}" @selected((string) request('maquina_id') === (string) $maquina->id)>{{ $maquina->nome }} @if($maquina->codigo_interno) / {{ $maquina->codigo_interno }} @endif</option>
                @endforeach
            </select>
        </label>
        <label>Código
            <input name="codigo_maquina" value="{{ request('codigo_maquina') }}" placeholder="Código da máquina">
        </label>
        <label>Tipo
            <select name="tipo">
                <option value="">Todos</option>
                <option value="checklist" @selected(request('tipo') === 'checklist')>Checklist</option>
                <option value="manutencao" @selected(request('tipo') === 'manutencao')>Manutenção</option>
                <option value="problema" @selected(request('tipo') === 'problema')>Relato de problema</option>
            </select>
        </label>
        <label>Periodicidade
            <select name="periodicidade">
                <option value="">Todas</option>
                @foreach(['diario' => 'Diário', 'mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'anual' => 'Anual'] as $valor => $label)
                    <option value="{{ $valor }}" @selected(request('periodicidade') === $valor)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="status_geral">
                <option value="">Todos</option>
                <option value="conforme" @selected(request('status_geral') === 'conforme')>Conforme</option>
                <option value="com_pendencia" @selected(request('status_geral') === 'com_pendencia')>Com pendência</option>
            </select>
        </label>
        <label>Início
            <input type="date" name="inicio" value="{{ request('inicio') }}">
        </label>
        <label>Fim
            <input type="date" name="fim" value="{{ request('fim') }}">
        </label>
        <div class="top-filter-actions">
            <a class="btn small" href="{{ route('admin.historico.index') }}">Limpar</a>
            <button class="btn small primary" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<div class="panel table-wrap">
    <table>
        <thead><tr><th>Registro</th><th>Alertas</th><th>Data</th><th>Código</th><th>Máquina</th><th>Responsável</th><th>Tipo</th><th>Periodicidade</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($registros as $registro)
            <tr>
                <td><strong>#{{ $registro->id }}</strong></td>
                <td>
                    @forelse($registro->alertas as $alerta)
                        <span class="badge alert-status-{{ str_replace('_', '-', $alerta->status) }}">#{{ $alerta->id }}</span>
                    @empty
                        <span>-</span>
                    @endforelse
                </td>
                <td>{{ $registro->data_hora->format('d/m/Y H:i') }}</td>
                <td>{{ $registro->maquina->codigo_interno ?: '-' }}</td>
                <td>{{ $registro->maquina->nome }}</td>
                <td>{{ $registro->operador?->nome ?? $registro->admin?->nome ?? 'Acesso não identificado' }}</td>
                <td>{{ $registro->tipo }}</td>
                <td>{{ $registro->periodicidade }}</td>
                <td><span class="badge status-{{ str_replace('_', '-', $registro->status_geral) }}">{{ $registro->status_geral === 'com_pendencia' ? 'Com pendência' : 'Conforme' }}</span></td>
                <td>
                    <a class="btn small primary" href="{{ route('admin.historico.show', $registro) }}">Abrir</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $registros->links() }}
</div>
@endsection
