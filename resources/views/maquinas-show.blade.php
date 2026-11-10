@extends('layouts.admin')

@section('content')
@php
    $qrUrl = route('maquinas.public.show', $maquina->token);
@endphp

<style>
    .machine-resource-grid {
        display: grid;
        grid-template-columns: minmax(280px, 0.9fr) minmax(0, 1.1fr);
        gap: 16px;
        align-items: stretch;
        margin-top: 16px;
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .qr-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 18px;
        align-items: center;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .qr-preview {
        display: grid;
        place-items: center;
        width: 172px;
        aspect-ratio: 1;
        padding: 12px;
        border-radius: var(--radius-md);
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .qr-preview svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    .qr-link-box {
        display: block;
        width: min(100%, 100%);
        max-width: 100%;
        min-width: 0;
        margin: 12px 0;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        color: var(--text-dim);
        background: #f9fafb;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 13px;
        font-weight: 500;
    }

    .file-list,
    .history-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .file-row,
    .history-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .file-row strong,
    .history-main strong {
        display: block;
        overflow: hidden;
        color: var(--text);
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 14px;
    }

    .file-row small,
    .history-main small {
        display: block;
        overflow: hidden;
        color: var(--text-dim);
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 500;
    }

    .file-empty,
    .history-empty {
        margin-top: 14px;
        padding: 16px;
        border: 1px dashed var(--border-strong);
        border-radius: var(--radius-md);
        color: var(--text-dim);
    }

    .history-panel {
        overflow: hidden;
    }

    .alert-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .alert-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .alert-row strong,
    .alert-row small {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .alert-row small {
        color: var(--text-dim);
        font-weight: 500;
    }

    .history-main {
        min-width: 0;
    }

    .history-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .extra-checklist-panel {
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .extra-checklist-panel[hidden] {
        display: none;
    }

    .management-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .management-item {
        display: grid;
        gap: 6px;
        min-width: 0;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .management-item span {
        color: var(--text-faint);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .management-item strong,
    .management-item small {
        overflow-wrap: anywhere;
    }

    .management-item.wide {
        grid-column: span 2;
    }

    .machine-billing {
        display: grid;
        gap: 14px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--border);
    }

    .machine-billing-form {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        gap: 10px;
        align-items: end;
    }

    .machine-billing-list {
        display: grid;
        gap: 8px;
    }

    .machine-billing-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .machine-billing-row strong,
    .machine-billing-row small {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .machine-billing-row small {
        color: var(--text-dim);
        font-weight: 500;
    }

    .machine-billing-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
    }

    .machine-billing-actions form {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .machine-billing-actions select {
        width: 120px;
        min-height: 34px;
        padding: 6px 26px 6px 9px;
        font-size: 12px;
    }

    .btn.disabled {
        opacity: 0.45;
        pointer-events: none;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .machine-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
        padding: 8px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .machine-tabs button {
        min-height: 36px;
        padding: 8px 12px;
        border: 1px solid transparent;
        border-radius: 6px;
        background: transparent;
        color: var(--text-dim);
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .machine-tabs button.is-active {
        border-color: rgba(20, 184, 166, .28);
        background: #ffffff;
        color: #0f766e;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }

    .tracking-log-panel {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .tracking-log-list {
        display: grid;
        gap: 8px;
        max-height: 320px;
        overflow: auto;
        padding-right: 2px;
    }

    .tracking-log-row {
        display: grid;
        grid-template-columns: minmax(120px, .72fr) minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        min-width: 0;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .tracking-log-row strong,
    .tracking-log-row small {
        display: block;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .tracking-log-row small {
        color: var(--text-dim);
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
    }

    .horimeter-filter {
        display: grid;
        grid-template-columns: repeat(3, minmax(120px, 170px)) auto;
        gap: 10px;
        align-items: end;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .horimeter-toolbar {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(240px, .65fr);
        gap: 10px;
        align-items: stretch;
    }

    .horimeter-toolbar .horimeter-filter {
        margin: 0;
    }

    .horimeter-initial-form {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .horimeter-filter label {
        min-width: 0;
        margin: 0;
    }

    .horimeter-filter input {
        width: 100%;
    }

    .horimeter-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 10px;
    }

    .horimeter-card {
        display: grid;
        gap: 6px;
        min-width: 0;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .horimeter-card span {
        color: var(--text-faint);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .horimeter-card strong {
        color: var(--text);
        font-size: 22px;
        line-height: 1.05;
    }

    .horimeter-list {
        display: grid;
        gap: 8px;
    }

    .horimeter-row {
        display: grid;
        grid-template-columns: minmax(120px, .55fr) minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 11px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .horimeter-row strong,
    .horimeter-row small {
        display: block;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .horimeter-row small {
        color: var(--text-dim);
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
    }

    .tracking-log-tools {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
        align-items: center;
    }

    .tracking-log-filter {
        display: flex;
        gap: 8px;
        align-items: center;
        min-width: 0;
    }

    .tracking-log-filter select {
        width: 132px;
        min-height: 34px;
        padding: 6px 30px 6px 10px;
        font-size: 12px;
    }

    .horimeter-filter input {
        min-height: 36px;
        font-size: 12px;
    }

    .horimeter-filter .actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .horimeter-sessions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }

    .horimeter-session {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 26px;
        padding: 5px 8px;
        border: 1px solid #bfe8df;
        border-radius: 999px;
        background: #ecfdf9;
        color: #0f766e;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }

    [data-machine-panel][hidden] {
        display: none !important;
    }

    @media (max-width: 900px) {
        .machine-resource-grid,
        .management-grid {
            grid-template-columns: 1fr;
        }

        .management-item.wide {
            grid-column: auto;
        }

        .machine-billing-form,
        .machine-billing-row {
            grid-template-columns: 1fr;
        }

        .machine-billing-actions,
        .machine-billing-actions form {
            width: 100%;
        }

        .machine-billing-actions .btn,
        .machine-billing-actions select {
            width: 100%;
        }
    }

    @media (max-width: 620px) {
        .machine-resource-grid {
            width: 100%;
            grid-template-columns: minmax(0, 1fr);
            overflow-x: clip;
        }

        .qr-card,
        .file-row,
        .history-row,
        .alert-row {
            width: 100%;
            max-width: 100%;
            grid-template-columns: minmax(0, 1fr);
        }

        .qr-card {
            justify-items: center;
            text-align: center;
            padding-inline: 14px;
        }

        .qr-preview {
            width: min(188px, 100%);
            max-width: calc(100vw - 64px);
            padding: 10px;
        }

        .qr-card > div:not(.qr-preview) {
            width: 100%;
            min-width: 0;
        }

        .qr-link-box {
            white-space: normal;
            overflow-wrap: anywhere;
            text-align: left;
            font-size: 12px;
            line-height: 1.35;
        }

        .qr-card .actions {
            display: grid;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .qr-card .actions .btn {
            width: 100%;
            justify-content: center;
        }

        .machine-tabs {
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 6px;
            scrollbar-width: thin;
        }

        .machine-tabs button {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 12px;
            white-space: nowrap;
        }

        .tracking-log-row {
            grid-template-columns: 1fr;
            gap: 7px;
        }

        .horimeter-filter,
        .horimeter-toolbar,
        .horimeter-cards,
        .horimeter-row {
            grid-template-columns: 1fr;
        }
    }

    /* TailAdmin second pass: machine internals */
    .machine-tabs {
        position: sticky;
        top: 68px;
        z-index: 30;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
        gap: 6px;
        margin: 0 0 14px;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: rgba(255,255,255,.96);
        box-shadow: 0 1px 2px rgba(16,24,40,.05);
        backdrop-filter: blur(8px);
    }

    .machine-tabs button {
        position: relative;
        min-height: 38px;
        justify-content: center;
        padding: 8px 10px;
        border-radius: 6px;
        border: 0;
        background: transparent;
        color: #475467;
        font-size: 12px;
        font-weight: 700;
        transition: .18s ease;
    }

    .machine-tabs button::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #d0d5dd;
        margin-right: 7px;
        display: inline-block;
        vertical-align: 1px;
    }

    .machine-tabs button:hover {
        background: #f9fafb;
        color: #101828;
    }

    .machine-tabs button.is-active {
        background: #ecfdf3;
        color: #0f766e;
        box-shadow: inset 0 0 0 1px #a6f4c5;
    }

    .machine-tabs button.is-active::before {
        background: #14b8a6;
    }

    .machine-resource-grid,
    .management-grid,
    .horimeter-cards,
    .grid.grid-4 {
        gap: 12px;
    }

    .machine-resource-grid {
        grid-template-columns: minmax(300px, .78fr) minmax(0, 1.22fr);
        margin-top: 0;
    }

    .panel[data-machine-panel],
    .machine-resource-grid[data-machine-panel],
    #historico[data-machine-panel] {
        margin-top: 0 !important;
        margin-bottom: 14px;
    }

    .panel[data-machine-panel],
    .machine-resource-grid .panel,
    #historico .panel {
        border-radius: 8px;
        border-color: var(--border);
        box-shadow: 0 1px 2px rgba(16,24,40,.05);
    }

    .qr-card {
        grid-template-columns: minmax(130px, 160px) minmax(0, 1fr);
        gap: 16px;
        align-items: center;
        padding: 16px;
    }

    .qr-preview {
        width: 150px;
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: none;
        background: #ffffff;
    }

    .qr-link-box {
        margin: 10px 0;
        padding: 9px 10px;
        border-radius: 6px;
        background: #f9fafb;
        font-size: 12px;
    }

    .management-grid {
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    }

    .management-item,
    .horimeter-card,
    .checklist-card,
    .file-row,
    .history-row,
    .alert-row,
    .tracking-log-row,
    .horimeter-row,
    .extra-checklist-panel {
        border: 1px solid var(--border);
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }

    .management-item,
    .horimeter-card {
        min-height: 118px;
        padding: 14px;
        align-content: start;
    }

    .management-item span,
    .horimeter-card span,
    .checklist-meta span {
        color: #667085;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .management-item strong,
    .horimeter-card strong {
        color: #101828;
        font-size: 17px;
        line-height: 1.25;
        font-weight: 700;
    }

    .management-item small,
    .horimeter-card small {
        color: #475467;
        font-size: 12px;
        line-height: 1.35;
    }

    .management-item.wide {
        grid-column: span 2;
    }

    .file-list,
    .history-list,
    .alert-list,
    .tracking-log-panel,
    .horimeter-list {
        gap: 8px;
        margin-top: 12px;
    }

    .file-row,
    .history-row,
    .alert-row {
        padding: 12px;
        background: #ffffff;
        align-items: center;
    }

    .alert-row[class*="alert-card-status"],
    .history-row,
    .file-row {
        box-shadow: inset 3px 0 0 #e4e7ec, 0 1px 2px rgba(16,24,40,.04);
    }

    .alert-row.alert-card-status-pendente { box-shadow: inset 3px 0 0 #f04438, 0 1px 2px rgba(16,24,40,.04); }
    .alert-row.alert-card-status-em-analise,
    .alert-row.alert-card-status-em_analise { box-shadow: inset 3px 0 0 #f79009, 0 1px 2px rgba(16,24,40,.04); }
    .alert-row.alert-card-status-resolvido { box-shadow: inset 3px 0 0 #12b76a, 0 1px 2px rgba(16,24,40,.04); }
    .alert-row.alert-card-status-falso-positivo,
    .alert-row.alert-card-status-falso_positivo { box-shadow: inset 3px 0 0 #98a2b3, 0 1px 2px rgba(16,24,40,.04); }

    .history-meta {
        gap: 5px;
        margin-top: 7px;
    }

    .tracking-log-list {
        max-height: 280px;
        gap: 8px;
    }

    .tracking-log-row,
    .horimeter-row {
        background: #ffffff;
        grid-template-columns: minmax(120px, .55fr) minmax(0, 1fr) auto;
        padding: 10px 12px;
    }

    .tracking-log-row strong,
    .horimeter-row strong,
    .history-main strong,
    .alert-row strong,
    .file-row strong {
        color: #101828;
        font-size: 13px;
        font-weight: 700;
    }

    .tracking-log-row small,
    .horimeter-row small,
    .history-main small,
    .alert-row small,
    .file-row small {
        color: #667085;
        font-size: 12px;
        font-weight: 500;
    }

    .horimeter-toolbar {
        display: flex;
        justify-content: flex-start;
    }

    .horimeter-filter {
        display: flex;
        flex-wrap: wrap;
        width: fit-content;
        max-width: 100%;
        gap: 8px;
        padding: 10px;
        background: #f9fafb;
        border-radius: 8px;
    }

    .horimeter-filter label {
        width: 138px;
    }

    .horimeter-filter input {
        min-height: 34px;
        border-radius: 6px;
    }

    .horimeter-card strong {
        font-size: 20px;
    }

    .checklist-card {
        min-height: 220px;
        padding: 14px;
        align-content: space-between;
    }

    .checklist-card h3 {
        margin: 10px 0 5px;
        color: #101828;
        font-size: 15px;
    }

    .checklist-status-row {
        gap: 5px;
    }

    .checklist-meta {
        padding-top: 10px;
        gap: 4px;
    }

    .checklist-card .actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .checklist-card .actions .btn,
    .checklist-card .actions form {
        flex: 1 1 auto;
    }

    .history-empty,
    .file-empty {
        border-radius: 8px;
        background: #fcfcfd;
        color: #667085;
    }

    @media (max-width: 900px) {
        .machine-tabs {
            top: 104px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .machine-resource-grid {
            grid-template-columns: 1fr;
        }

        .management-item.wide {
            grid-column: auto;
        }
    }

    @media (max-width: 620px) {
        .machine-tabs {
            position: static;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            overflow: visible;
            gap: 6px;
        }

        .machine-tabs button {
            width: 100%;
            min-height: 38px;
            white-space: normal;
        }

        .qr-card,
        .tracking-log-row,
        .horimeter-row,
        .file-row,
        .history-row,
        .alert-row {
            grid-template-columns: 1fr;
        }

        .qr-preview {
            width: min(170px, 100%);
        }

        .section-head,
        .panel .top {
            display: grid;
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .section-head .btn,
        .panel .top .btn,
        .tracking-log-tools,
        .tracking-log-filter,
        .tracking-log-filter select,
        .horimeter-filter,
        .horimeter-filter label,
        .horimeter-filter .actions,
        .horimeter-filter .actions .btn {
            width: 100%;
        }

        .grid.grid-4 {
            grid-template-columns: 1fr;
        }

        .checklist-card .actions {
            display: grid;
            grid-template-columns: 1fr;
        }
    }

    .maintenance-modal[hidden] {
        display: none !important;
    }

    .maintenance-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: grid;
        place-items: center;
        padding: 18px;
        background: rgba(15, 23, 42, 0.42);
    }

    .maintenance-modal-card {
        width: min(100%, 720px);
        max-height: min(86vh, 720px);
        overflow: auto;
        padding: 18px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.2);
    }

    .maintenance-modal-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .maintenance-modal-head h2 {
        margin: 0;
        font-size: 18px;
    }

    .maintenance-modal-head p {
        margin: 4px 0 0;
        color: var(--text-dim);
        font-size: 13px;
    }

    .maintenance-options {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .maintenance-option-card {
        display: grid;
        gap: 10px;
        align-content: space-between;
        min-height: 150px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .maintenance-option-card strong {
        display: block;
        margin-bottom: 5px;
    }

    .maintenance-option-card span {
        color: var(--text-dim);
        font-size: 12px;
        line-height: 1.4;
    }

    @media (max-width: 760px) {
        .maintenance-options {
            grid-template-columns: 1fr;
        }

        .maintenance-modal-card {
            padding: 14px;
        }
    }

</style>

<div class="top">
    <div>
        <h1>{{ $maquina->nome }}</h1>
        <p>{{ $maquina->codigo_interno }} @if($maquina->setor) - {{ $maquina->setor }} @endif</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.maquinas.index') }}">Voltar</a>
        @unless($isComercial ?? false)
            <button class="btn primary" type="button" data-maintenance-open>Iniciar manutenção</button>
            <a class="btn primary" href="{{ route('admin.maquinas.edit', $maquina) }}">Editar máquina</a>
            <form class="action-form" method="post" action="{{ route('admin.maquinas.destroy', $maquina) }}" data-confirm="Excluir esta máquina? Os históricos vinculados podem ser afetados.">
                @csrf
                @method('delete')
                <button class="btn danger" type="submit">Excluir</button>
            </form>
        @endunless
    </div>
</div>

@unless($isComercial ?? false)
    <div class="maintenance-modal" data-maintenance-modal hidden>
        <div class="maintenance-modal-card" role="dialog" aria-modal="true" aria-labelledby="maintenance-modal-title">
            <div class="maintenance-modal-head">
                <div>
                    <h2 id="maintenance-modal-title">Selecionar manutenção</h2>
                    <p>{{ $maquina->nome }} - escolha o tipo de manutenção para continuar.</p>
                </div>
                <button class="btn small" type="button" data-maintenance-close>Fechar</button>
            </div>
            <div class="maintenance-options">
                @foreach(\App\Services\FormularioService::TIPOS_MANUTENCAO as $valor => $label)
                    <article class="maintenance-option-card">
                        <div>
                            <strong>Manutenção {{ $label }}</strong>
                            <span>
                                @switch($valor)
                                    @case('preventiva')
                                        Intervenção programada para reduzir falhas e manter a máquina disponível.
                                        @break
                                    @case('preditiva')
                                        Análise por sinais, condição observada ou comportamento antes da falha.
                                        @break
                                    @case('corretiva')
                                        Correção de falha, defeito ou problema identificado na operação.
                                        @break
                                @endswitch
                            </span>
                        </div>
                        <a class="btn primary" href="{{ route('admin.maquinas.manutencao.create', [$maquina, 'tipo_manutencao' => $valor]) }}">Selecionar</a>
                    </article>
                @endforeach
                <article class="maintenance-option-card">
                    <div>
                        <strong>Relatório da máquina</strong>
                        <span>Gera um PDF com o histórico de manutenções desta máquina, incluindo data, hora e itens registrados nas intervenções.</span>
                    </div>
                    <a class="btn" href="{{ route('admin.relatorios.export', ['tipo' => 'manutencoes', 'maquina_id' => $maquina->id]) }}">Gerar PDF</a>
                </article>
            </div>
        </div>
    </div>
@endunless

<nav class="machine-tabs" aria-label="Seções da máquina">
    <button class="is-active" type="button" data-machine-tab="resumo">Vis&atilde;o geral</button>
    <button type="button" data-machine-tab="gestao">Loca&ccedil;&atilde;o</button>
    @if($rastreamentoHabilitado && ! ($isComercial ?? false))
        <button type="button" data-machine-tab="rastreamento">Rastreamento</button>
    @endif
    @unless($isComercial ?? false)
        <button type="button" data-machine-tab="alertas">Alertas</button>
        <button type="button" data-machine-tab="checklists">Checklists</button>
    @endunless
    @if($rastreamentoHabilitado && ! ($isComercial ?? false))
        <button type="button" data-machine-tab="horimetro">Hor&iacute;metro</button>
    @endif
    @unless($isComercial ?? false)
        <button type="button" data-machine-tab="historico">Registros</button>
    @endunless
</nav>

<div class="machine-resource-grid" data-machine-panel="resumo">
    @if($isComercial ?? false)
        <section class="panel">
            <h2>Resumo comercial</h2>
            @php
                $cobrancaFinanceiraLabel = $maquina->cobrancaFinanceiraStatusLabel();
                $cobrancaFinanceiraClasse = $maquina->cobrancaFinanceiraStatusClasse();
            @endphp
            <div class="management-grid">
                <div class="management-item">
                    <span>Máquina</span>
                    <strong>{{ $maquina->nome }}</strong>
                    <small>{{ $maquina->codigo_interno ?: 'Sem código' }} @if($maquina->modelo) · {{ $maquina->modelo }} @endif</small>
                </div>
                <div class="management-item">
                    <span>Situação</span>
                    <strong><span class="badge {{ $maquina->locacaoStatusClasse() }}">{{ $maquina->locacaoStatusLabel() }}</span></strong>
                    <small>{{ ucfirst($maquina->status) }}</small>
                </div>
                <div class="management-item">
                    <span>Cliente atual</span>
                    <strong>{{ ($maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente) ?: 'Sem cliente informado' }}</strong>
                    <small>{{ $maquina->localOperacionalLabel(false) }}</small>
                </div>
                <div class="management-item">
                    <span>Contrato</span>
                    <strong>{{ $maquina->contadorContrato() }}</strong>
                    <small>{{ $maquina->contrato_inicio?->format('d/m/Y') ?? 'Sem início' }} até {{ $maquina->contrato_fim?->format('d/m/Y') ?? 'sem término' }}</small>
                </div>
                <div class="management-item">
                    <span>Financeiro</span>
                    <strong><span class="badge {{ $cobrancaFinanceiraClasse }}">{{ $cobrancaFinanceiraLabel }}</span></strong>
                    <small>{{ $maquina->cobrancaFinanceiraResumo() }}</small>
                </div>
            </div>
        </section>
    @endif
    @unless($isComercial ?? false)
    <section class="panel qr-card" id="qr-code">
        <div class="qr-preview" aria-label="QR Code da máquina">
            {!! $qrCodeSvg !!}
        </div>
        <div>
            <h2>QR Code</h2>
            <p>Acesso do operador para esta máquina.</p>
            <span class="qr-link-box" id="qr-link">{{ $qrUrl }}</span>
            <div class="qr-link-box" style="margin-top:10px">
                <strong>Login checklist:</strong> {{ $maquina->loginQrPadrao() }}<br>
                <strong>Senha checklist:</strong> {{ $maquina->loginQrPadrao() }}
            </div>
            <div class="actions">
                <button class="btn primary" type="button" data-copy-target="qr-link">Copiar link</button>
                <a class="btn" href="{{ route('admin.maquinas.qr-code', $maquina) }}">Baixar QR</a>
            </div>
        </div>
    </section>

    <section class="panel" id="arquivos">
        <div class="top">
            <div>
                <h2>Manuais e arquivos</h2>
                <p>Imagens e PDFs vinculados a esta máquina.</p>
            </div>
            <a class="btn primary" href="{{ route('admin.maquinas.edit', $maquina) }}">Adicionar</a>
        </div>

        <div class="file-list">
            @forelse($maquina->arquivos as $arquivo)
                <div class="file-row">
                    <div>
                        <strong>{{ $arquivo->nome_original }}</strong>
                        <small>{{ $arquivo->categoria_label }} - {{ $arquivo->tipo }} - {{ number_format($arquivo->tamanho / 1024 / 1024, 2, ',', '.') }} MB</small>
                    </div>
                    <div class="table-actions">
                        <a class="btn small primary" href="{{ route('admin.maquinas.arquivos.show', [$maquina, $arquivo]) }}" target="_blank">Abrir</a>
                        <form class="action-form" method="post" action="{{ route('admin.maquinas.arquivos.destroy', [$maquina, $arquivo]) }}" data-confirm="Excluir este arquivo da máquina?">
                            @csrf
                            @method('delete')
                            <button class="btn small danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="file-empty">Nenhum manual, imagem ou PDF cadastrado.</div>
            @endforelse
        </div>
    </section>
    @endunless
</div>

<section class="panel stack" id="gestao-locacao" data-machine-panel="gestao" style="margin-top:16px">
    @php
        $cobrancaFinanceiraLabel = $maquina->cobrancaFinanceiraStatusLabel();
        $cobrancaFinanceiraClasse = $maquina->cobrancaFinanceiraStatusClasse();
    @endphp
    <div class="section-head">
        <div>
            <h2>Gestão de locação e contrato</h2>
            <p>Acompanhamento comercial e logístico desta máquina.</p>
        </div>
        @unless($isComercial ?? false)
            <a class="btn primary" href="{{ route('admin.maquinas.locacao.edit', $maquina) }}">Atualizar locação</a>
        @endunless
    </div>
    <div class="management-grid">
        <div class="management-item">
            <span>Status</span>
            <strong><span class="badge {{ $maquina->locacaoStatusClasse() }}">{{ $maquina->locacaoStatusLabel() }}</span></strong>
            <small>Cliente atual: {{ ($maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente) ?: 'Sem cliente informado' }}</small>
        </div>
        <div class="management-item">
            <span>Localização</span>
            <strong>{{ ($maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente) ?: 'Sem cliente informado' }}</strong>
            <small>{{ $maquina->localOperacionalLabel($rastreamentoHabilitado) }}</small>
        </div>
        <div class="management-item">
            <span>Contrato</span>
            <strong>{{ $maquina->contadorContrato() }}</strong>
            <small>{{ $maquina->contrato_inicio?->format('d/m/Y') ?? 'Sem início' }} até {{ $maquina->contrato_fim?->format('d/m/Y') ?? 'sem término' }}</small>
            @if($maquina->contrato_atual)
                <a class="btn small" href="{{ route('admin.maquinas.arquivos.show', [$maquina, $maquina->contrato_atual]) }}" target="_blank">Abrir contrato</a>
            @endif
        </div>
        <div class="management-item">
            <span>Financeiro</span>
            <strong><span class="badge {{ $cobrancaFinanceiraClasse }}">{{ $cobrancaFinanceiraLabel }}</span></strong>
            <small>{{ $maquina->cobrancaFinanceiraResumo() }}</small>
        </div>
        <div class="management-item">
            <span>Devolução</span>
            <strong>{{ $maquina->devolucao_realizada ? 'Devolvida' : 'Pendente' }}</strong>
            <small>Prevista: {{ $maquina->devolucao_prevista?->format('d/m/Y') ?? 'não informada' }}</small>
            <small>Realizada: {{ $maquina->devolucao_realizada?->format('d/m/Y') ?? 'não realizada' }}</small>
        </div>
        <div class="management-item wide">
            <span>Frete</span>
            <strong>{{ $maquina->frete_tipo ?: 'Sistemática não informada' }}</strong>
            <small>Responsável: {{ $maquina->frete_responsavel ?: 'não informado' }}</small>
        </div>
        <div class="management-item wide">
            <span>Observações logísticas</span>
            <strong>{{ $maquina->frete_observacao ?: 'Nenhuma observação cadastrada.' }}</strong>
        </div>
    </div>

    <div class="machine-billing">
        <div class="section-head">
            <div>
                <h2>Cobranças da máquina</h2>
                <p>Status financeiro vinculado ao cliente atual e a esta máquina.</p>
            </div>
            <span class="badge {{ $cobrancaFinanceiraClasse }}">{{ $cobrancaFinanceiraLabel }}</span>
        </div>

        @unless($isComercial ?? false)
            <form method="post" action="{{ route('admin.maquinas.cobrancas.store', $maquina) }}" class="machine-billing-form">
                @csrf
                <input type="hidden" name="cliente_nome" value="{{ ($maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente) ?: $maquina->empresa?->nome }}">
                <label>Referência
                    <input name="referencia_mes" type="month" value="{{ now()->format('Y-m') }}">
                </label>
                <label>Vencimento
                    <input name="vencimento" type="date" value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                </label>
                <label>Valor
                    <input name="valor" type="number" min="0" step="0.01" placeholder="R$ 0,00" required>
                </label>
                <label>Status
                    <select name="status" required>
                        <option value="aberta">Aberta</option>
                        <option value="paga">Paga</option>
                        <option value="vencida">Vencida</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </label>
                <label>Forma de pagamento
                    <input name="forma_pagamento" placeholder="Boleto, Pix, cartão">
                </label>
                <button class="btn primary" type="submit">Adicionar</button>
                <label style="grid-column:1 / -1">Observações
                    <input name="observacoes" placeholder="Informações internas da cobrança">
                </label>
            </form>
        @endunless

        <div class="machine-billing-list">
            @forelse(($cobrancasMaquina ?? collect()) as $cobranca)
                <div class="machine-billing-row">
                    <div>
                        <strong>{{ $cobranca->descricao ?: 'Cobrança de locação' }}</strong>
                        <small>
                            {{ $cobranca->cliente_nome }}
                            · {{ $cobranca->referencia_mes?->format('m/Y') ?? 'sem referência' }}
                            · vencimento {{ $cobranca->vencimento?->format('d/m/Y') ?? 'não informado' }}
                            · R$ {{ number_format((float) $cobranca->valor, 2, ',', '.') }}
                        </small>
                    </div>
                    <div class="machine-billing-actions">
                        <span class="badge {{ $cobranca->statusClasse() }}">{{ $cobranca->statusLabel() }}</span>
                        @unless($isComercial ?? false)
                            <form method="post" action="{{ route('admin.maquinas.cobrancas.update', [$maquina, $cobranca]) }}" data-unsaved-ignore="1">
                                @csrf
                                @method('patch')
                                <select name="status" onchange="this.form.submit()">
                                    @foreach(\App\Models\SistemaCobranca::STATUS as $statusValor => $statusLabel)
                                        <option value="{{ $statusValor }}" @selected($cobranca->status === $statusValor)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="post" action="{{ route('admin.maquinas.cobrancas.destroy', [$maquina, $cobranca]) }}" data-confirm="Remover esta cobrança?">
                                @csrf
                                @method('delete')
                                <button class="btn small danger" type="submit">Excluir</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="history-empty">Nenhuma cobrança vinculada a esta máquina.</div>
            @endforelse
        </div>
    </div>
</section>

@if($rastreamentoHabilitado && ! ($isComercial ?? false))
    <section class="panel stack" id="rastreamento" data-machine-panel="rastreamento" style="margin-top:16px">
        <div class="section-head">
            <div>
                <h2>Rastreamento</h2>
                <p>Telemetria informativa do GV50CG via Flespi: localização, último sinal e status da ignição.</p>
            </div>
            @if($podeGerenciarRastreamento ?? false)
                <a class="btn primary" href="{{ route('admin.maquinas.edit', $maquina) }}">Configurar rastreador</a>
            @endif
        </div>

        @if($maquina->rastreador)
            <div class="management-grid">
                <div class="management-item">
                    <span>Status</span>
                    <strong><span class="badge {{ $maquina->rastreador->statusClasse() }}">{{ $maquina->rastreador->statusLabel() }}</span></strong>
                    <small>IMEI: {{ $maquina->rastreador->imei ?: 'Não informado' }}</small>
                </div>
                <div class="management-item">
                    <span>Último sinal</span>
                    <strong>{{ $maquina->rastreador->ultimoSinalLocalLabel() ?? 'Aguardando comunicação' }}</strong>
                    <small>Provedor: {{ strtoupper($maquina->rastreador->provedor) }}</small>
                </div>
                <div class="management-item">
                    <span>Última posição</span>
                    <strong>{{ $maquina->rastreador->localizacaoLabel() }}</strong>
                    @if($maquina->rastreador->ultima_latitude !== null && $maquina->rastreador->ultima_longitude !== null)
                        <a class="btn small" href="{{ route('admin.rastreamento.mapa', ['maquina' => $maquina->id]) }}">Ver no mapa</a>
                    @else
                        <small>O mapa aparece depois do primeiro pacote da Flespi.</small>
                    @endif
                </div>
                <div class="management-item">
                    <span>Operação</span>
                    <strong data-horimeter-status>{{ $maquina->rastreador->operacaoLabel() }}</strong>
                    <small>{{ $maquina->rastreador->alimentacaoLabel() }}</small>
                </div>
                <div class="management-item">
                    <span>Horas trabalhadas</span>
                    <strong data-tracking-hours-today>{{ $maquina->rastreador->horasTrabalhadasHojeLabel() }}</strong>
                    <small>Estimativa do dia por ignição e posições recebidas.</small>
                </div>
            </div>

            <div class="tracking-log-panel">
                <div class="section-head">
                    <div>
                        <h3>Últimos sinais</h3>
                        <p>Resumo compacto das posições recentes recebidas pela Flespi.</p>
                    </div>
                    <div class="tracking-log-tools">
                        <form class="tracking-log-filter" method="get" action="{{ route('admin.maquinas.show', $maquina) }}#rastreamento">
                            <select name="ignicao_log" aria-label="Filtrar ignição" onchange="this.form.submit()">
                                <option value="">Todos</option>
                                <option value="ligada" @selected(($rastreamentoFiltros['ignicao_log'] ?? '') === 'ligada')>Ligada</option>
                                <option value="desligada" @selected(($rastreamentoFiltros['ignicao_log'] ?? '') === 'desligada')>Desligada</option>
                            </select>
                            @if(! empty($rastreamentoFiltros['ignicao_log']))
                                <a class="btn small" href="{{ route('admin.maquinas.show', $maquina) }}#rastreamento">Limpar</a>
                            @endif
                        </form>
                        @if($maquina->rastreador->ultima_latitude !== null && $maquina->rastreador->ultima_longitude !== null)
                            <a class="btn small primary" href="{{ route('admin.rastreamento.mapa', ['maquina' => $maquina->id]) }}">Direcionar no mapa</a>
                        @endif
                    </div>
                </div>
                <div class="tracking-log-list">
                    @forelse($ultimasPosicoes as $posicao)
                        @php
                            $sinalPosicao = $posicao->sinal ?? $maquina->rastreador->sinal;
                            $tensaoPosicao = $posicao->tensao_externa ?? $maquina->rastreador->tensao_externa;
                        @endphp
                        <div class="tracking-log-row">
                            <div>
                                <strong>{{ $posicao->momentoLocalLabel() }}</strong>
                                <small>{{ $posicao->ignicao === null ? 'Ignição sem leitura' : ($posicao->ignicao ? 'Ligada' : 'Desligada') }}</small>
                            </div>
                            <div>
                                <strong>{{ $posicao->localizacaoLabel() }}</strong>
                                <small>Velocidade: {{ $posicao->velocidade ?? '0' }} km/h</small>
                            </div>
                            <div class="history-meta">
                                <span class="badge">Sinal: {{ $sinalPosicao ?? 'sem leitura' }}</span>
                                <span class="badge {{ $tensaoPosicao !== null && (float) $tensaoPosicao <= \App\Models\RastreadorDispositivo::EXTERNAL_POWER_OFF_MAX_VOLTAGE ? 'warning' : '' }}">
                                    Tensão: {{ $tensaoPosicao !== null ? $tensaoPosicao.' V' : 'sem leitura' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="history-empty">Nenhuma posição recebida ainda. Depois do primeiro envio da Flespi, o histórico aparece aqui.</div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="history-empty">Nenhum rastreador vinculado. Cadastre o IMEI nesta maquina quando o GV50CG chegar.</div>
        @endif
    </section>
@endif

@unless($isComercial ?? false)
<section class="panel" id="historico-locacao" data-machine-panel="gestao" style="margin-top:16px">
    <div class="section-head">
        <div>
            <h2>Histórico de locações</h2>
            <p>Registro das mudanças de cliente, status, local, contrato e devolução.</p>
        </div>
        <a class="btn" href="{{ route('admin.maquinas.locacao.edit', $maquina) }}">Atualizar locação</a>
    </div>
    <div class="history-list">
        @forelse($locacaoHistoricos as $historico)
            <div class="history-row">
                <div class="history-main">
                    <strong>#{{ $historico->id }} - {{ $historico->cliente_anterior ?: 'Sem cliente' }} &rarr; {{ $historico->cliente_novo ?: 'Sem cliente' }}</strong>
                    <small>{{ $historico->created_at->format('d/m/Y H:i') }} por {{ $historico->admin?->nome ?? 'Administrador' }}</small>
                    <div class="history-meta">
                        <span class="badge">{{ $historico->status_anterior ?: 'sem status' }} &rarr; {{ $historico->status_novo ?: 'sem status' }}</span>
                        <span class="badge">{{ $historico->local_novo ?: 'Sem local' }}</span>
                        <span class="badge">Contrato: {{ $historico->contrato_inicio?->format('d/m/Y') ?? 'sem início' }} a {{ $historico->contrato_fim?->format('d/m/Y') ?? 'sem término' }}</span>
                    </div>
                </div>
                <span class="badge">{{ $historico->observacao ?: 'Atualização' }}</span>
            </div>
        @empty
            <div class="history-empty">Nenhuma mudança de locação registrada.</div>
        @endforelse
    </div>
</section>
@endunless

@unless($isComercial ?? false)
<section class="panel" id="alertas-problemas" data-machine-panel="alertas" style="margin-top:16px">
    @php
        $statusLabels = [
            'pendente' => 'Pendente',
            'em_analise' => 'Em análise',
            'resolvido' => 'Resolvido',
            'falso_positivo' => 'Ignorar',
        ];
    @endphp
    <div class="top">
        <div>
            <h2>Alertas e problemas</h2>
            <p>Ocorrências abertas, relatos do operador e pendências vinculadas a esta máquina.</p>
        </div>
        <a class="btn" href="{{ route('admin.alertas.index', ['maquina_id' => $maquina->id]) }}">Ver todos</a>
    </div>

    <div class="alert-list">
        @forelse($alertasMaquina as $alerta)
            @php
                $isRelatoProblema = $alerta->registro?->tipo === 'problema' || ! $alerta->formulario_item_id;
            @endphp
            <div class="alert-row alert-card-status-{{ str_replace('_', '-', $alerta->status) }}">
                <div>
                    <strong>#{{ $alerta->id }} - {{ $isRelatoProblema ? 'Relato de problema' : ($alerta->item?->pergunta ?? 'Item removido') }}</strong>
                    <small>{{ $alerta->created_at->format('d/m/Y H:i') }} por {{ $alerta->operador?->nome ?? 'Operador removido' }}</small>
                    <div class="history-meta">
                        <span class="badge alert-status-{{ str_replace('_', '-', $alerta->status) }}">{{ $statusLabels[$alerta->status] ?? ucfirst(str_replace('_', ' ', $alerta->status)) }}</span>
                        <span class="badge">{{ $isRelatoProblema ? 'Problema' : 'Checklist' }}</span>
                    </div>
                </div>
                <a class="btn small primary" href="{{ route('admin.alertas.show', ['alerta' => $alerta, 'from_maquina' => $maquina->id]) }}">Abrir</a>
            </div>
        @empty
            <div class="history-empty">Nenhum alerta aberto para esta máquina.</div>
        @endforelse
    </div>


    <div class="section-head" style="margin-top:18px">
        <div>
            <h3>&Uacute;ltimos alertas finalizados</h3>
            <p>Hist&oacute;rico recente de alertas resolvidos ou ignorados nesta m&aacute;quina.</p>
        </div>
    </div>
    <div class="alert-list">
        @forelse($alertasFinalizadosMaquina as $alerta)
            @php
                $isRelatoProblema = $alerta->registro?->tipo === 'problema' || ! $alerta->formulario_item_id;
            @endphp
            <div class="alert-row alert-card-status-{{ str_replace('_', '-', $alerta->status) }}">
                <div>
                    <strong>#{{ $alerta->id }} - {{ $isRelatoProblema ? 'Relato de problema' : ($alerta->item?->pergunta ?? 'Item removido') }}</strong>
                    <small>Finalizado em {{ $alerta->updated_at->format('d/m/Y H:i') }}</small>
                    <div class="history-meta">
                        <span class="badge alert-status-{{ str_replace('_', '-', $alerta->status) }}">{{ $statusLabels[$alerta->status] ?? ucfirst(str_replace('_', ' ', $alerta->status)) }}</span>
                        <span class="badge">{{ $isRelatoProblema ? 'Problema' : 'Checklist' }}</span>
                    </div>
                </div>
                <a class="btn small" href="{{ route('admin.alertas.show', ['alerta' => $alerta, 'from_maquina' => $maquina->id]) }}">Ver</a>
            </div>
        @empty
            <div class="history-empty">Nenhum alerta finalizado para esta m&aacute;quina.</div>
        @endforelse
    </div>
</section>

<section class="panel stack" id="checklists" data-machine-panel="checklists" style="margin-top:16px">
    <div class="section-head">
        <div>
            <h2>Checklist</h2>
            <p>Os quatro checklists principais vêm dos checklists padrão. Extras podem ser adicionados conforme a necessidade da máquina.</p>
        </div>
        <button class="btn primary" type="button" data-toggle-extra-checklist>Adicionar novo checklist</button>
    </div>

    <div class="extra-checklist-panel" data-extra-checklist-form hidden>
        <form class="grid grid-2" method="post" action="{{ route('admin.maquinas.checklists-extra.store', $maquina) }}">
            @csrf
            @method('patch')
            <label>Modelo do novo checklist
                <select name="modelo_id" required>
                    <option value="">Selecione um modelo pré-cadastrado</option>
                    @foreach($modelosChecklist as $modelo)
                        <option value="{{ $modelo->id }}">{{ $modelo->nome }} ({{ $modelo->itens_count }} itens)</option>
                    @endforeach
                </select>
            </label>
            <label>Periodicidade
                <select name="periodicidade_extra" required>
                    <option value="">Selecione</option>
                    @foreach($periodicidadesAdicionar as $valor => $label)
                        <option value="{{ $valor }}">{{ ucfirst($label) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="actions" style="align-self:end">
                <button class="btn" type="button" data-cancel-extra-checklist>Cancelar</button>
                <button class="btn primary" type="submit" @disabled($modelosChecklist->isEmpty())>Adicionar checklist</button>
            </div>
        </form>
    </div>

    <div class="grid grid-4">
        @foreach ($checklists as $checklist)
            @php
                $registro = $checklist['ultimo_registro'];
            @endphp
            <article class="checklist-card">
                <div>
                    <div class="checklist-status-row">
                        <span class="badge">{{ $checklist['label'] }}</span>
                        <span class="badge status-{{ $checklist['status_dia'] }}">{{ $checklist['status_label'] }}</span>
                        <span class="badge status-prazo">{{ $checklist['prazo_label'] }}</span>
                    </div>
                    <h3>{{ $checklist['formulario']?->nome ?? 'Checklist não configurado' }}</h3>
                    <p>{{ $checklist['itens_count'] }} itens configurados</p>
                    <small>{{ $checklist['status_hint'] }}</small>
                </div>

                <div class="checklist-meta">
                    <span>Último registro</span>
                    @if ($registro)
                        <strong>{{ $registro->data_hora->format('d/m/Y H:i') }}</strong>
                        <small>{{ $registro->status_geral }} por {{ $registro->operador?->nome ?? $registro->admin?->nome ?? 'responsável' }}</small>
                    @else
                        <strong>Nenhum</strong>
                        <small>Ainda não preenchido</small>
                    @endif
                </div>

                <div class="actions">
                    @if ($checklist['formulario'])
                        <a class="btn primary" href="{{ route('admin.formularios-maquina.edit', [$maquina, $checklist['periodicidade']]) }}">Editar</a>
                        <form class="action-form" method="post" action="{{ route('admin.formularios-maquina.destroy', [$maquina, $checklist['periodicidade']]) }}" data-confirm="Excluir este checklist desta máquina? Os registros antigos serão mantidos no histórico.">
                            @csrf
                            @method('delete')
                            <button class="btn danger" type="submit">Excluir</button>
                        </form>
                    @endif

                    @if (in_array($checklist['periodicidade'], ['trimestral', 'anual'], true))
                        @if ($checklist['registro_ciclo'])
                            <a class="btn disabled" aria-disabled="true" href="#">Já registrado</a>
                        @else
                            <a class="btn" href="{{ route('admin.maquinas.checklists.create', [$maquina, $checklist['periodicidade']]) }}">Registrar</a>
                        @endif
                    @endif
                    @if ($checklist['registro_ciclo'])
                        <form class="action-form" method="post" action="{{ route('admin.maquinas.checklists.reset', [$maquina, $checklist['periodicidade']]) }}" data-confirm="Resetar este checklist no ciclo atual? O histórico será mantido, mas um novo envio será liberado.">
                            @csrf
                            @method('patch')
                            <button class="btn danger" type="submit">Resetar ciclo</button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if($checklistsExtras->isNotEmpty())
        <div class="stack" style="margin-top:16px">
            <div>
                <h3>Checklists extras</h3>
                <p>Checklists adicionais disponíveis ao operador quando necessário.</p>
            </div>
            <div class="grid grid-4">
                @foreach($checklistsExtras as $extra)
                    @php
                        $formulario = $extra['formulario'];
                        $registro = $extra['ultimo_registro'];
                    @endphp
                    <article class="checklist-card">
                        <div>
                            <div class="checklist-status-row">
                                <span class="badge">Extra</span>
                                <span class="badge">{{ ucfirst($extra['periodicidade_label']) }}</span>
                                <span class="badge status-{{ $extra['status_dia'] }}">{{ $extra['status_label'] }}</span>
                                <span class="badge status-prazo">{{ $extra['prazo_label'] }}</span>
                            </div>
                            <h3>{{ $formulario->nome }}</h3>
                            <p>{{ $extra['itens_count'] }} itens configurados</p>
                            <small>Renova por ciclo de {{ $extra['periodicidade_label'] }}.</small>
                        </div>

                        <div class="checklist-meta">
                            <span>Último registro</span>
                            @if ($registro)
                                <strong>{{ $registro->data_hora->format('d/m/Y H:i') }}</strong>
                                <small>{{ $registro->status_geral }} por {{ $registro->operador?->nome ?? $registro->admin?->nome ?? 'responsável' }}</small>
                            @else
                                <strong>Nenhum</strong>
                                <small>Ainda não preenchido</small>
                            @endif
                        </div>

                        <div class="actions">
                            <a class="btn primary" href="{{ route('admin.formularios-maquina.extra.edit', [$maquina, $formulario]) }}">Editar</a>
                            <form class="action-form" method="post" action="{{ route('admin.formularios-maquina.extra.destroy', [$maquina, $formulario]) }}" data-confirm="Excluir este checklist extra? Os registros antigos serão mantidos no histórico.">
                                @csrf
                                @method('delete')
                                <button class="btn danger" type="submit">Excluir</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endunless

@if($rastreamentoHabilitado && ! ($isComercial ?? false))
    <section class="panel stack" id="horimetro" data-machine-panel="horimetro" style="margin-top:16px">
        <div class="section-head">
            <div>
                <h2>Horímetro</h2>
                <p>Horas trabalhadas calculadas pelos períodos em que a ignição esteve ligada.</p>
            </div>
            <a class="btn small primary" href="{{ route('admin.maquinas.horimetro.pdf', array_filter([
                'maquina' => $maquina->id,
                'inicio' => $horimetroResumo['inicio']?->toDateString() ?? null,
                'fim' => $horimetroResumo['fim']?->toDateString() ?? null,
            ], fn ($valor) => $valor !== null && $valor !== '')) }}">Gerar PDF</a>
        </div>

        <div class="horimeter-toolbar">
            <form class="horimeter-filter" method="get" action="{{ route('admin.maquinas.show', $maquina) }}#horimetro">
                <label>Início
                    <input type="date" name="inicio" value="{{ $horimetroResumo['inicio']?->toDateString() ?? '' }}">
                </label>
                <label>Fim
                    <input type="date" name="fim" value="{{ $horimetroResumo['fim']?->toDateString() ?? '' }}">
                </label>
                <div class="actions">
                    <button class="btn small primary" type="submit">Filtrar</button>
                    <a class="btn small" href="{{ route('admin.maquinas.show', $maquina) }}#horimetro">Limpar</a>
                </div>
            </form>

        </div>

        <div class="horimeter-cards">
            <div class="horimeter-card">
                <span>Horímetro inicial</span>
                <strong data-horimeter-initial>{{ $horimetroResumo['horimetro_inicial_label'] ?? '0 min' }}</strong>
                <small>Horas anteriores ao início do rastreamento.</small>
            </div>
            <div class="horimeter-card">
                <span>Total no período</span>
                <strong data-horimeter-total>{{ $horimetroResumo['total_label'] ?? '0 min' }}</strong>
                <small data-horimeter-period>{{ $horimetroResumo['inicio']?->format('d/m/Y') ?? '-' }} até {{ $horimetroResumo['fim']?->format('d/m/Y') ?? '-' }}</small>
            </div>
            <div class="horimeter-card">
                <span>Total geral estimado</span>
                <strong data-horimeter-grand-total>{{ $horimetroResumo['total_geral_label'] ?? '0 min' }}</strong>
                <small data-horimeter-tracked-total>Inicial + {{ $horimetroResumo['total_rastreado_label'] ?? '0 min' }} rastreados.</small>
            </div>
            <div class="horimeter-card">
                <span>Dias com uso</span>
                <strong data-horimeter-days>{{ $horimetroResumo['dias']->count() ?? 0 }}</strong>
                <small>Dias com sessões registradas.</small>
            </div>
            <div class="horimeter-card">
                <span>Status atual</span>
                <strong data-horimeter-status>{{ $maquina->rastreador?->operacaoLabel() ?? 'Sem rastreador' }}</strong>
                <small data-horimeter-signal>{{ $maquina->rastreador?->ultimoSinalLocalLabel() ?? 'Sem sinal' }}</small>
            </div>
        </div>

        <div class="horimeter-list" data-horimeter-list data-horimeter-url="{{ route('admin.maquinas.horimetro.dados', array_filter([
            'maquina' => $maquina->id,
            'inicio' => $horimetroResumo['inicio']?->toDateString() ?? null,
            'fim' => $horimetroResumo['fim']?->toDateString() ?? null,
        ], fn ($valor) => $valor !== null && $valor !== '')) }}">
            @forelse(($horimetroResumo['dias'] ?? collect()) as $dia)
                <div class="horimeter-row">
                    <div>
                        <strong>{{ $dia['dia']->format('d/m/Y') }}</strong>
                        <small>{{ count($dia['eventos']) === 1 ? '1 sessão' : count($dia['eventos']).' sessões' }}</small>
                    </div>
                    <div>
                        <strong>{{ $dia['label'] }}</strong>
                        <small>Sessões de ignição ligada</small>
                        <div class="horimeter-sessions">
                            @foreach(collect($dia['eventos'])->take(8) as $evento)
                                <span class="horimeter-session">{{ $evento['inicio']->format('H:i') }} até {{ $evento['fim']->format('H:i') }} · {{ $evento['label'] }}</span>
                            @endforeach
                            @if(count($dia['eventos']) > 8)
                                <span class="horimeter-session">+{{ count($dia['eventos']) - 8 }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="badge {{ $dia['minutos'] > 0 ? 'status-conforme' : '' }}">Uso registrado</span>
                </div>
            @empty
                <div class="history-empty">Nenhum período com ignição ligada nos filtros selecionados.</div>
            @endforelse
        </div>
    </section>
@endif

@unless($isComercial ?? false)
<div class="grid grid-2" id="historico" data-machine-panel="historico" style="margin-top:16px">
    <section class="panel history-panel">
        <h2>Checklist diário, mensal e extras do operador</h2>
        <div class="history-list">
            @forelse ($registrosOperador as $registro)
                <div class="history-row">
                    <div class="history-main">
                        <strong>{{ $registro->data_hora->format('d/m/Y H:i') }}</strong>
                        <small>{{ $registro->operador?->nome ?? 'Operador' }}</small>
                        <div class="history-meta">
                            <span class="badge">{{ $registro->periodicidade }}</span>
                            <span class="badge">{{ $registro->status_geral }}</span>
                        </div>
                    </div>
                    <a class="btn small primary" href="{{ route('admin.historico.show', ['registro' => $registro, 'from_maquina' => $maquina->id]) }}">Visualizar</a>
                </div>
            @empty
                <div class="history-empty">Nenhum checklist do operador registrado.</div>
            @endforelse
        </div>
    </section>

    <section class="panel history-panel">
        <h2>Checklist trimestral e anual do dono</h2>
        <div class="history-list">
            @forelse ($registrosTecnicos as $registro)
                <div class="history-row">
                    <div class="history-main">
                        <strong>{{ $registro->data_hora->format('d/m/Y H:i') }}</strong>
                        <small>{{ $registro->admin?->nome ?? 'Administrador' }}</small>
                        <div class="history-meta">
                            <span class="badge">{{ $registro->periodicidade }}</span>
                            <span class="badge">{{ $registro->status_geral }}</span>
                        </div>
                    </div>
                    <a class="btn small primary" href="{{ route('admin.historico.show', ['registro' => $registro, 'from_maquina' => $maquina->id]) }}">Visualizar</a>
                </div>
            @empty
                <div class="history-empty">Nenhum checklist trimestral ou anual registrado.</div>
            @endforelse
        </div>
    </section>
</div>
@endunless

<script>
document.querySelectorAll('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.copyTarget);
        const text = target?.textContent?.trim();

        if (!text) {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }

        const original = button.textContent;
        button.textContent = 'Copiado';
        setTimeout(() => {
            button.textContent = original;
        }, 1600);
    });
});

const extraButton = document.querySelector('[data-toggle-extra-checklist]');
const extraForm = document.querySelector('[data-extra-checklist-form]');
const extraCancel = document.querySelector('[data-cancel-extra-checklist]');

const maintenanceModal = document.querySelector('[data-maintenance-modal]');
const maintenanceOpen = document.querySelector('[data-maintenance-open]');
const maintenanceClose = document.querySelector('[data-maintenance-close]');

if (maintenanceModal && maintenanceOpen) {
    const setMaintenanceModal = (open) => {
        maintenanceModal.hidden = !open;
        document.body.style.overflow = open ? 'hidden' : '';
    };

    maintenanceOpen.addEventListener('click', () => setMaintenanceModal(true));
    maintenanceClose?.addEventListener('click', () => setMaintenanceModal(false));
    maintenanceModal.addEventListener('click', (event) => {
        if (event.target === maintenanceModal) {
            setMaintenanceModal(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !maintenanceModal.hidden) {
            setMaintenanceModal(false);
        }
    });
}

if (extraButton && extraForm) {
    const toggleExtraForm = (forceOpen = null) => {
        const shouldOpen = forceOpen ?? extraForm.hidden;
        extraForm.hidden = !shouldOpen;
        extraButton.textContent = shouldOpen ? 'Fechar cadastro' : 'Adicionar novo checklist';
    };

    extraButton.addEventListener('click', () => toggleExtraForm());
    extraCancel?.addEventListener('click', () => toggleExtraForm(false));
}

const machineTabs = Array.from(document.querySelectorAll('[data-machine-tab]'));
const machinePanels = Array.from(document.querySelectorAll('[data-machine-panel]'));

if (machineTabs.length && machinePanels.length) {
    const panelByHash = {
        '#qr-code': 'resumo',
        '#arquivos': 'resumo',
        '#gestao-locacao': 'gestao',
        '#historico-locacao': 'gestao',
        '#rastreamento': 'rastreamento',
        '#alertas-problemas': 'alertas',
        '#checklists': 'checklists',
        '#horimetro': 'horimetro',
        '#historico': 'historico',
    };

    const activateMachineTab = (tab) => {
        machineTabs.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.machineTab === tab);
        });

        machinePanels.forEach((panel) => {
            panel.hidden = panel.dataset.machinePanel !== tab;
        });
    };

    const initialTab = panelByHash[window.location.hash] || machineTabs[0].dataset.machineTab;
    activateMachineTab(initialTab);

    machineTabs.forEach((button) => {
        button.addEventListener('click', () => activateMachineTab(button.dataset.machineTab));
    });
}

const horimeterList = document.querySelector('[data-horimeter-list]');

if (horimeterList?.dataset.horimeterUrl) {
    const setText = (selector, value) => {
        document.querySelectorAll(selector).forEach((element) => {
            element.textContent = value;
        });
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderHorimeterRows = (dias) => {
        if (!dias?.length) {
            horimeterList.innerHTML = '<div class="history-empty">Nenhum período com ignição ligada nos filtros selecionados.</div>';
            return;
        }

        horimeterList.innerHTML = dias.map((dia) => {
            const sessoesLabel = dia.sessoes_count === 1 ? '1 sessão' : `${dia.sessoes_count} sessões`;
            const eventos = (dia.eventos || [])
                .map((evento) => `<span class="horimeter-session">${escapeHtml(evento.label)}</span>`)
                .join('');
            const restante = dia.eventos_restantes > 0
                ? `<span class="horimeter-session">+${dia.eventos_restantes}</span>`
                : '';

            return `
                <div class="horimeter-row">
                    <div>
                        <strong>${escapeHtml(dia.dia_label)}</strong>
                        <small>${escapeHtml(sessoesLabel)}</small>
                    </div>
                    <div>
                        <strong>${escapeHtml(dia.label)}</strong>
                        <small>Sessões de ignição ligada</small>
                        <div class="horimeter-sessions">${eventos}${restante}</div>
                    </div>
                    <span class="badge ${dia.minutos > 0 ? 'status-conforme' : ''}">Uso registrado</span>
                </div>
            `;
        }).join('');
    };

    const atualizarHorimetro = async () => {
        try {
            const response = await fetch(horimeterList.dataset.horimeterUrl, {
                headers: {'Accept': 'application/json'},
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            setText('[data-horimeter-total]', data.total_label || '0 min');
            setText('[data-horimeter-initial]', data.horimetro_inicial_label || '0 min');
            setText('[data-horimeter-grand-total]', data.total_geral_label || '0 min');
            setText('[data-horimeter-tracked-total]', `Inicial + ${data.total_rastreado_label || '0 min'} rastreados.`);
            setText('[data-horimeter-days]', data.dias_count ?? 0);
            setText('[data-horimeter-period]', data.periodo_label || '');
            setText('[data-horimeter-status]', data.status_atual || 'Desligada');
            setText('[data-horimeter-signal]', data.ultimo_sinal || 'Sem sinal');
            setText('[data-tracking-hours-today]', data.horimetro_hoje || '0 min hoje');
            renderHorimeterRows(data.dias || []);
        } catch (error) {
            // Mantém os dados atuais se houver falha temporária de rede.
        }
    };

    atualizarHorimetro();
    setInterval(atualizarHorimetro, 60000);
}
</script>
@endsection
