@extends('layouts.admin')

@section('content')
@php
    $filtros = $filtros ?? [];
@endphp

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .tracking-page {
        display: grid;
        gap: 16px;
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .tracking-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
        gap: 10px;
    }

    .tracking-kpi {
        display: grid;
        gap: 6px;
        min-width: 0;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .tracking-kpi span {
        color: var(--text-dim);
        font-size: 12px;
        font-weight: 700;
    }

    .tracking-kpi strong {
        color: var(--text);
        font-size: 26px;
        line-height: 1;
    }

    .tracking-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 330px);
        gap: 16px;
        align-items: stretch;
        min-width: 0;
    }

    .tracking-map-panel,
    .tracking-side-panel {
        min-width: 0;
        min-height: 0;
    }

    .tracking-map-panel {
        position: relative;
        z-index: 0;
        overflow: hidden;
    }

    #tracking-map {
        position: relative;
        z-index: 0;
        width: 100%;
        max-width: 100%;
        height: min(68vh, 680px);
        min-height: 430px;
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #eef2f7;
    }

    .tracking-map-panel .leaflet-container {
        max-width: 100%;
    }

    .tracking-map-panel .leaflet-control-container {
        z-index: 800;
    }

    .tracking-map-panel .leaflet-tile-pane {
        z-index: 200;
    }

    .tracking-map-panel .leaflet-overlay-pane {
        z-index: 400;
    }

    .tracking-map-panel .leaflet-marker-pane {
        z-index: 600;
    }

    .tracking-map-panel .leaflet-popup-pane {
        z-index: 700;
    }

    .tracking-side-panel {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        gap: 12px;
    }

    .tracking-list {
        display: grid;
        gap: 10px;
        max-height: min(68vh, 680px);
        overflow: auto;
        padding-right: 2px;
    }

    .tracking-machine {
        display: grid;
        gap: 9px;
        align-content: start;
        padding: 11px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .tracking-machine strong,
    .tracking-machine small {
        display: block;
        overflow-wrap: anywhere;
    }

    .tracking-machine small {
        color: var(--text-dim);
        font-weight: 500;
        line-height: 1.35;
    }

    .tracking-machine-title {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: start;
    }

    .tracking-machine-title strong {
        min-width: 0;
        font-size: 14px;
        line-height: 1.25;
    }

    .tracking-machine-title span {
        flex: 0 0 auto;
        color: var(--text-faint);
        font-size: 11px;
        font-weight: 800;
    }

    .tracking-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .tracking-machine .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 2px;
    }

    .tracking-machine .actions .btn {
        min-height: 32px;
        padding: 7px 10px;
        border-radius: 6px;
        font-size: 12px;
    }

    .tracking-empty {
        padding: 18px;
        border: 1px dashed var(--border-strong);
        border-radius: var(--radius-md);
        color: var(--text-dim);
        background: #fff;
    }

    .tracking-popup {
        min-width: 240px;
        max-width: 280px;
        display: grid;
        gap: 8px;
        line-height: 1.35;
    }

    .tracking-popup strong {
        display: block;
        color: var(--text);
        font-size: 14px;
        line-height: 1.25;
    }

    .tracking-popup span,
    .tracking-popup small {
        display: block;
        color: var(--text-dim);
    }

    .tracking-popup .actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 6px;
        margin-top: 2px;
    }

    .tracking-popup .btn {
        width: 100%;
        justify-content: center;
        min-height: 32px;
        padding: 7px 9px;
        border-radius: 6px;
        font-size: 12px;
    }

    .tracking-pin-wrap {
        width: 34px !important;
        height: 42px !important;
    }

    .tracking-pin {
        position: relative;
        display: grid;
        place-items: center;
        width: 30px;
        height: 30px;
        margin: 2px;
        border: 2px solid #ffffff;
        border-radius: 50% 50% 50% 8px;
        background: #64748b;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.28);
        transform: rotate(-45deg);
    }

    .tracking-pin::after {
        content: "";
        position: absolute;
        right: -3px;
        bottom: -3px;
        width: 13px;
        height: 13px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.18);
        filter: blur(6px);
        transform: rotate(45deg);
    }

    .tracking-pin span {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: inset 0 0 0 2px rgba(15, 118, 110, 0.12);
    }

    .tracking-pin.is-active {
        background: #14b8a6;
    }

    .tracking-pin.is-danger {
        background: #ef4444;
    }

    .tracking-pin.is-warning {
        background: #f59e0b;
    }

    @media (max-width: 980px) {
        .tracking-page .top.top-filter {
            display: grid;
            align-items: start;
            gap: 12px;
        }

        .tracking-page .top-filter-form {
            width: 100%;
            justify-content: stretch;
        }

        .tracking-layout {
            grid-template-columns: 1fr;
        }

        #tracking-map {
            height: 58vh;
            min-height: 360px;
        }

        .tracking-list {
            max-height: none;
        }
    }

    @media (max-width: 700px) {
        .tracking-page {
            gap: 12px;
        }

        .tracking-page .top.top-filter {
            padding: 14px;
        }

        .tracking-page .title-copy h1 {
            font-size: 24px;
        }

        .tracking-page .title-copy p {
            font-size: 13px;
            line-height: 1.4;
        }

        .tracking-page .top-filter-form {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 8px;
            align-items: end;
            justify-content: flex-start !important;
            width: 100% !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            padding: 0 2px 8px !important;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }

        .tracking-page .top-filter-form label {
            flex: 0 0 auto;
            width: auto !important;
            min-width: 108px;
            max-width: 158px;
            scroll-snap-align: start;
            font-size: 10px !important;
        }

        .tracking-page .top-filter-form input,
        .tracking-page .top-filter-form select {
            width: 128px !important;
            min-height: 32px !important;
            padding: 5px 8px !important;
            font-size: 12px !important;
        }

        .tracking-page .top-filter-actions {
            flex: 0 0 auto;
            display: flex !important;
            align-items: end;
            gap: 6px;
            width: auto !important;
            scroll-snap-align: start;
        }

        .tracking-page .top-filter-actions .btn {
            width: auto !important;
            min-height: 32px !important;
            padding: 6px 9px !important;
            white-space: nowrap !important;
        }

        .tracking-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .tracking-kpi {
            padding: 10px;
        }

        .tracking-kpi span {
            font-size: 11px;
        }

        .tracking-kpi strong {
            font-size: 22px;
        }

        .tracking-layout {
            gap: 12px;
        }

        #tracking-map {
            height: 52dvh;
            min-height: 310px;
            border-radius: 8px;
        }

        .tracking-side-panel {
            gap: 10px;
        }

        .tracking-list {
            gap: 8px;
            padding-right: 0;
        }

        .tracking-machine {
            padding: 11px;
        }

        .tracking-machine .actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            width: 100%;
        }

        .tracking-machine .actions .btn {
            width: 100%;
        }
    }

    @media (max-width: 460px) {
        .tracking-page .top-filter-form {
            display: flex !important;
            flex-wrap: nowrap !important;
        }

        .tracking-page .top-filter-form input,
        .tracking-page .top-filter-form select {
            width: 122px !important;
        }

        .tracking-page .top-filter-form label {
            min-width: 102px;
        }

        .tracking-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #tracking-map {
            height: 48dvh;
            min-height: 290px;
        }

        .tracking-machine .actions {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .tracking-page > .top.top-filter > .top-filter-form {
            display: flex !important;
            grid-template-columns: none !important;
            flex-wrap: nowrap !important;
            align-items: end !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            width: 100% !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            padding: 0 2px 8px !important;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }

        .tracking-page > .top.top-filter > .top-filter-form > label {
            flex: 0 0 auto !important;
            width: auto !important;
            min-width: 102px !important;
            max-width: 146px !important;
            scroll-snap-align: start;
        }

        .tracking-page > .top.top-filter > .top-filter-form > label > input,
        .tracking-page > .top.top-filter > .top-filter-form > label > select {
            width: 122px !important;
            min-height: 32px !important;
            padding: 5px 8px !important;
            font-size: 12px !important;
        }

        .tracking-page > .top.top-filter > .top-filter-form > .top-filter-actions {
            flex: 0 0 auto !important;
            display: flex !important;
            width: auto !important;
            gap: 5px !important;
            scroll-snap-align: start;
        }

        .tracking-page > .top.top-filter > .top-filter-form > .top-filter-actions .btn {
            width: auto !important;
            min-height: 32px !important;
            padding: 6px 9px !important;
            white-space: nowrap !important;
        }
    }

    @media (max-width: 720px) {
        .tracking-page > .top.top-filter > form.map-filter-rail {
            display: flex !important;
            grid-template-columns: none !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: end !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 2px 8px !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }

        .tracking-page > .top.top-filter > form.map-filter-rail > label {
            display: grid !important;
            flex: 0 0 auto !important;
            width: auto !important;
            min-width: 96px !important;
            max-width: 132px !important;
            gap: 4px !important;
            color: #475467 !important;
            font-size: 10px !important;
            line-height: 1.1 !important;
            white-space: nowrap !important;
            scroll-snap-align: start;
        }

        .tracking-page > .top.top-filter > form.map-filter-rail > label > input,
        .tracking-page > .top.top-filter > form.map-filter-rail > label > select,
        .tracking-page > .top.top-filter > form.map-filter-rail > label > input[name="busca"] {
            flex: 0 0 auto !important;
            width: 112px !important;
            min-width: 112px !important;
            max-width: 112px !important;
            min-height: 30px !important;
            padding: 5px 7px !important;
            border-radius: 6px !important;
            font-size: 12px !important;
        }

        .tracking-page > .top.top-filter > form.map-filter-rail > .top-filter-actions {
            flex: 0 0 auto !important;
            display: flex !important;
            width: auto !important;
            gap: 5px !important;
            margin: 0 !important;
            padding: 0 !important;
            scroll-snap-align: start;
        }

        .tracking-page > .top.top-filter > form.map-filter-rail > .top-filter-actions .btn {
            width: auto !important;
            min-width: 0 !important;
            min-height: 30px !important;
            padding: 6px 9px !important;
            font-size: 12px !important;
            white-space: nowrap !important;
        }

        .tracking-page .title-copy p {
            display: none !important;
        }

        .tracking-page .tracking-summary {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 0 !important;
            padding: 8px !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius-md) !important;
            background: #fff !important;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04) !important;
        }

        .tracking-page .tracking-kpi {
            min-height: 0 !important;
            padding: 9px 10px !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            gap: 3px !important;
            box-shadow: none !important;
        }

        .tracking-page .tracking-kpi:nth-child(odd) {
            border-right: 1px solid #e5e7eb !important;
        }

        .tracking-page .tracking-kpi:nth-child(n+3) {
            border-top: 1px solid #e5e7eb !important;
        }

        .tracking-page .tracking-kpi:last-child {
            border-right: 0 !important;
        }

        .tracking-page .tracking-kpi span {
            font-size: 10px !important;
            line-height: 1.15 !important;
        }

        .tracking-page .tracking-kpi strong {
            font-size: 20px !important;
            line-height: 1.05 !important;
        }
    }
</style>

<div class="tracking-page">
    <div class="top top-filter">
        <div class="title-copy">
            <h1>Mapa de rastreamento</h1>
            <p>Vis&atilde;o em tempo real das m&aacute;quinas com rastreador vinculado.</p>
        </div>
        <form class="top-filter-form map-filter-rail" method="get" action="{{ route('admin.rastreamento.mapa') }}">
            <label>Busca
                <input name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="Maquina, codigo ou IMEI">
            </label>
            <label>Cliente
                <select name="cliente">
                    <option value="">Todos</option>
                    @foreach($clientesLocacao as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string) ($filtros['cliente'] ?? '') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option>
                    <option value="disponivel" @selected(($filtros['status'] ?? '') === 'disponivel')>Disponivel</option>
                    <option value="locada" @selected(($filtros['status'] ?? '') === 'locada')>Locada</option>
                    <option value="em_transporte" @selected(($filtros['status'] ?? '') === 'em_transporte')>Em transporte</option>
                    <option value="devolvida" @selected(($filtros['status'] ?? '') === 'devolvida')>Devolvida</option>
                    <option value="manutencao" @selected(($filtros['status'] ?? '') === 'manutencao')>Em manutencao</option>
                </select>
            </label>
            <label>Comunica&ccedil;&atilde;o
                <select name="comunicacao">
                    <option value="">Todas</option>
                    <option value="online" @selected(($filtros['comunicacao'] ?? '') === 'online')>Online</option>
                    <option value="offline" @selected(($filtros['comunicacao'] ?? '') === 'offline')>Sem comunicação</option>
                    <option value="sem_alimentacao" @selected(($filtros['comunicacao'] ?? '') === 'sem_alimentacao')>Sem alimentação</option>
                    <option value="sem_posicao" @selected(($filtros['comunicacao'] ?? '') === 'sem_posicao')>Sem posição</option>
                    <option value="sem_rastreador" @selected(($filtros['comunicacao'] ?? '') === 'sem_rastreador')>Sem rastreador</option>
                </select>
            </label>
            <div class="top-filter-actions">
                <a class="btn small" href="{{ route('admin.rastreamento.mapa') }}">Limpar</a>
                <button class="btn small primary" type="submit">Filtrar</button>
            </div>
        </form>
    </div>

    <section class="tracking-summary" aria-label="Resumo do rastreamento">
        <div class="tracking-kpi"><span>Total</span><strong>{{ $resumo['total'] }}</strong></div>
        <div class="tracking-kpi"><span>Com rastreador</span><strong>{{ $resumo['com_rastreador'] }}</strong></div>
        <div class="tracking-kpi"><span>Online</span><strong>{{ $resumo['online'] }}</strong></div>
        <div class="tracking-kpi"><span>Sem alimentação</span><strong>{{ $resumo['sem_alimentacao'] }}</strong></div>
        <div class="tracking-kpi"><span>Sem comunicação</span><strong>{{ $resumo['sem_comunicacao'] }}</strong></div>
        <div class="tracking-kpi"><span>Com posição</span><strong>{{ $resumo['com_posicao'] }}</strong></div>
    </section>

    <section class="tracking-layout">
        <div class="panel tracking-map-panel">
            <div id="tracking-map" aria-label="Mapa das máquinas"></div>
        </div>
        <aside class="panel tracking-side-panel">
            <div class="section-head">
                <div>
                    <h2>Máquinas</h2>
                    <p id="tracking-updated">Carregando pontos...</p>
                </div>
            </div>
            <div class="tracking-list" id="tracking-list"></div>
        </aside>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(() => {
    const map = L.map('tracking-map', { scrollWheelZoom: true }).setView([-14.235, -51.9253], 4);
    const markers = L.layerGroup().addTo(map);
    const list = document.getElementById('tracking-list');
    const updated = document.getElementById('tracking-updated');
    const dataUrl = @json(route('admin.rastreamento.dados', request()->query()));
    const initialFocusId = @json((string) request('maquina', ''));
    const markerByMachineId = new Map();
    let openedMachineId = null;
    let initialBoundsApplied = false;
    let loadingMapData = false;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    function badgeClass(value) {
        return value ? `badge ${value}` : 'badge';
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function machineHtml(machine) {
        const ignicao = machine.ignicao === null ? 'Ignição sem leitura' : (machine.ignicao ? 'Ligada' : 'Desligada');
        const posicao = machine.endereco || machine.local || 'Sem posição recebida';
        const operacao = machine.operacao_status || ignicao;

        return `
            <article class="tracking-machine">
                <div>
                    <div class="tracking-machine-title">
                        <strong>${escapeHtml(machine.nome)}</strong>
                        <span>#${escapeHtml(machine.id)}</span>
                    </div>
                    <small>${escapeHtml(machine.cliente || 'Cliente não informado')}</small>
                    <small>${escapeHtml(posicao)}</small>
                </div>
                <div class="tracking-meta">
                    <span class="${badgeClass(machine.rastreador_classe)}">${escapeHtml(machine.rastreador_status)}</span>
                    <span class="badge">${escapeHtml(machine.status_locacao)}</span>
                    <span class="${badgeClass(machine.operacao_classe)}">${escapeHtml(operacao)}</span>
                    <span class="${badgeClass(machine.alimentacao_classe)}">${escapeHtml(machine.alimentacao_status || 'Alimentação sem leitura')}</span>
                    <span class="badge">${escapeHtml(machine.horimetro_hoje || '0 min hoje')}</span>
                </div>
                <div class="actions">
                    <a class="btn small primary" href="${machine.url}">Abrir</a>
                    ${machine.latitude !== null && machine.longitude !== null
                        ? `<button class="btn small" type="button" data-map-focus="${machine.id}" data-lat="${machine.latitude}" data-lng="${machine.longitude}">Direcionar</button>`
                        : ''}
                </div>
            </article>
        `;
    }

    function popupHtml(machine) {
        return `
            <div class="tracking-popup">
                <strong>${escapeHtml(machine.nome)}</strong>
                <span>${escapeHtml(machine.cliente || 'Cliente nao informado')}</span>
                <small>${escapeHtml(machine.endereco || machine.local || 'Local não informado')}</small>
                <div class="tracking-meta">
                    <span class="${badgeClass(machine.rastreador_classe)}">${escapeHtml(machine.rastreador_status)}</span>
                    <span class="${badgeClass(machine.operacao_classe)}">${escapeHtml(machine.operacao_status || (machine.ignicao === null ? 'Ignição sem leitura' : (machine.ignicao ? 'Ligada' : 'Desligada')))}</span>
                    <span class="${badgeClass(machine.alimentacao_classe)}">${escapeHtml(machine.alimentacao_status || 'Alimentação sem leitura')}</span>
                </div>
                <small>Último sinal: ${escapeHtml(machine.ultimo_sinal || 'Sem sinal')}</small>
                <small>Horímetro hoje: ${escapeHtml(machine.horimetro_hoje || '0 min hoje')}</small>
                <div class="actions">
                    <a class="btn small primary" href="${machine.url}">Abrir máquina</a>
                </div>
            </div>
        `;
    }

    function pinIcon(machine) {
        const stateClass = machine.rastreador_classe === 'danger'
            ? 'is-danger'
            : (machine.operacao_classe === 'warning'
                ? 'is-warning'
                : (machine.ignicao ? 'is-active' : ''));

        return L.divIcon({
            className: 'tracking-pin-wrap',
            html: `<span class="tracking-pin ${stateClass}"><span></span></span>`,
            iconSize: [34, 42],
            iconAnchor: [17, 38],
            popupAnchor: [0, -34]
        });
    }

    async function loadMapData(shouldFitBounds = false) {
        if (loadingMapData) {
            return;
        }

        loadingMapData = true;

        try {
            const url = new URL(dataUrl, window.location.origin);
            url.searchParams.set('_', Date.now().toString());
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) {
                throw new Error('Não foi possível carregar o mapa.');
            }

            const data = await response.json();
            const bounds = [];
            markers.clearLayers();
            markerByMachineId.clear();
            list.innerHTML = '';

            data.maquinas.forEach((machine) => {
                list.insertAdjacentHTML('beforeend', machineHtml(machine));

                if (machine.latitude !== null && machine.longitude !== null) {
                    const marker = L.marker([machine.latitude, machine.longitude], {
                        icon: pinIcon(machine),
                        title: machine.nome,
                        alt: machine.nome
                    });

                    marker.bindPopup(popupHtml(machine), {
                        closeButton: true,
                        autoPan: true,
                        keepInView: true,
                        maxWidth: 300
                    });

                    marker.on('click', () => {
                        openedMachineId = String(machine.id);
                        marker.openPopup();
                    });

                    marker.on('popupclose', () => {
                        if (openedMachineId === String(machine.id)) {
                            openedMachineId = null;
                        }
                    });

                    markers.addLayer(marker);
                    markerByMachineId.set(String(machine.id), marker);
                    bounds.push([machine.latitude, machine.longitude]);
                }
            });

            if (!data.maquinas.length) {
                list.innerHTML = '<div class="tracking-empty">Nenhuma máquina encontrada para os filtros selecionados.</div>';
            }

            if (bounds.length && shouldFitBounds && !initialBoundsApplied) {
                const focusedMarker = initialFocusId ? markerByMachineId.get(String(initialFocusId)) : null;

                if (focusedMarker) {
                    map.setView(focusedMarker.getLatLng(), 17, { animate: false });
                    focusedMarker.openPopup();
                    list.querySelector(`[data-map-focus="${initialFocusId}"]`)?.closest('.tracking-machine')?.scrollIntoView({ block: 'nearest' });
                } else {
                    map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });
                }

                initialBoundsApplied = true;
            }

            if (openedMachineId && markerByMachineId.has(openedMachineId)) {
                markerByMachineId.get(openedMachineId).openPopup();
            }

            updated.textContent = `Atualizado em ${data.atualizado_em} - atualiza a cada 1 min`;
        } catch (error) {
            updated.textContent = error.message || 'Erro ao carregar o mapa.';
            list.innerHTML = '<div class="tracking-empty">Não foi possível carregar os dados de rastreamento.</div>';
        } finally {
            loadingMapData = false;
        }
    }

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-map-focus]');

        if (!button) {
            return;
        }

        const lat = Number(button.dataset.lat);
        const lng = Number(button.dataset.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        map.setView([lat, lng], Math.max(map.getZoom(), 16), { animate: true });
        openedMachineId = String(button.dataset.mapFocus);
        markerByMachineId.get(openedMachineId)?.openPopup();
    });

    window.addEventListener('resize', () => map.invalidateSize());

    loadMapData(true);
    setTimeout(() => map.invalidateSize(), 150);
    setInterval(() => loadMapData(false), 60000);
})();
</script>
@endsection
