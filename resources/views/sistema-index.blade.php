@extends('layouts.admin')

@section('content')
@php
    $paginaSistema = $paginaSistema ?? 'empresas';
@endphp
<style>
    .system-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 16px;
        padding: 4px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #ffffff;
    }

    .system-tabs .btn {
        width: auto;
        border-radius: 6px;
        background: transparent;
        border-color: transparent;
        color: var(--text-dim);
    }

    .system-tabs .btn.primary {
        background: rgba(20, 184, 166, .12);
        border-color: rgba(20, 184, 166, .24);
        color: #0f766e;
    }

    .system-form {
        display: grid;
        gap: 18px;
    }

    .system-form-section {
        display: grid;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .system-form-section h2 {
        margin: 0;
    }

    .backup-card {
        display: grid;
        gap: 16px;
        max-width: 680px;
    }

    .module-checks {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
    }

    .module-check {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
        color: var(--text);
    }

    .module-check input {
        width: 18px;
        min-height: 18px;
        margin-top: 2px;
    }

    .module-check strong {
        display: block;
        font-size: 13px;
        line-height: 1.25;
    }

    .module-check small {
        display: block;
        margin-top: 2px;
        color: var(--text-dim);
        font-size: 12px;
        line-height: 1.35;
    }

    .empresa-detail-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .empresa-detail-head h2 {
        margin: 0;
        font-size: 1.15rem;
    }

    .empresa-detail-head p {
        margin: 4px 0 0;
        color: var(--text-dim);
    }

    .empresa-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .empresa-summary-card {
        display: grid;
        gap: 4px;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .empresa-summary-card small {
        color: var(--text-dim);
        font-size: 12px;
    }

    .empresa-summary-card strong {
        font-size: 1rem;
        color: var(--text);
    }

    .empresa-inner-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 14px;
    }

    .empresa-inner-tabs .btn {
        width: auto;
        min-height: 34px;
        padding: 7px 11px;
        border-radius: 6px;
        font-size: 13px;
    }

    .system-logo-preview {
        display: grid;
        place-items: center;
        min-height: 92px;
        padding: 14px;
        border: 1px dashed var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .system-logo-preview img {
        max-width: 180px;
        max-height: 72px;
        object-fit: contain;
    }

    .system-user-card {
        display: grid;
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
    }

    .system-user-card + .system-user-card {
        margin-top: 10px;
    }

    .system-user-card h3 {
        margin: 0;
        font-size: .95rem;
    }

    .system-user-edit {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
        gap: 10px;
        align-items: end;
    }

    .system-user-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .security-log-table {
        table-layout: fixed;
        min-width: 980px;
    }

    .security-log-table td {
        vertical-align: top;
        word-break: break-word;
    }

    .security-event {
        display: grid;
        gap: 4px;
    }

    .security-risk {
        display: inline-flex;
        width: fit-content;
        padding: 3px 7px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
    }

    .security-risk.risco-medio {
        border-color: #fde68a;
        background: #fef3c7;
        color: #92400e;
    }

    .security-risk.risco-alto,
    .security-risk.risco-critico {
        border-color: #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .backup-card .btn {
        width: fit-content;
    }

    .company-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-end;
        gap: 8px;
        min-width: 88px;
    }

    .company-actions .btn,
    .company-actions form {
        width: 100%;
    }

    .company-actions .btn.small {
        justify-content: center;
        min-height: 32px;
        padding: 7px 9px;
        border-radius: 6px;
        font-weight: 600;
        white-space: normal;
    }

    .company-actions .btn.small.primary {
        background: rgba(20, 184, 166, .12);
        color: #0f766e;
        border-color: rgba(20, 184, 166, .28);
    }

    .company-actions .btn.small:not(.primary):not(.danger) {
        background: #ffffff;
        color: var(--text);
    }

    .companies-table {
        table-layout: fixed;
        min-width: 860px;
    }

    .companies-table .badge {
        max-width: 100%;
        white-space: normal;
        text-align: center;
        line-height: 1.25;
        font-weight: 500;
    }

    .companies-table td {
        vertical-align: top;
    }

    .company-name-cell {
        font-weight: 700;
        line-height: 1.35;
        word-break: break-word;
    }

    .company-stack {
        display: grid;
        justify-items: start;
        gap: 8px;
    }

    .company-stack.compact {
        gap: 6px;
    }

    .system-info-list,
    .company-usage-list {
        display: grid;
        gap: 6px;
    }

    .system-info-list .btn.small {
        width: fit-content;
        justify-content: center;
        min-height: 32px;
        padding: 7px 10px;
        border-radius: 6px;
        background: #ffffff;
        color: var(--text);
        font-weight: 600;
        white-space: normal;
    }

    .system-status-line {
        display: grid;
        grid-template-columns: 9px minmax(0, 1fr);
        gap: 8px;
        align-items: center;
        min-width: 0;
        color: var(--text);
        font-size: 13px;
        line-height: 1.35;
    }

    .system-status-line span {
        overflow-wrap: anywhere;
    }

    .system-dot {
        width: 8px;
        height: 8px;
        border-radius: 99px;
        background: #94a3b8;
    }

    .system-dot.status-conforme,
    .system-dot.ok {
        background: #14b8a6;
    }

    .system-dot.warning,
    .system-dot.alert-status-em-analise {
        background: #f59e0b;
    }

    .system-dot.danger,
    .system-dot.status-com-pendencia {
        background: #ef4444;
    }

    .billing-table {
        table-layout: fixed;
        min-width: 760px;
    }

    .billing-client-cell {
        display: grid;
        gap: 3px;
        max-width: 210px;
        min-width: 0;
    }

    .billing-client-cell strong,
    .billing-client-cell small {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .billing-actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 6px;
    }

    .billing-actions form {
        margin: 0;
    }

    .billing-actions select {
        width: 118px;
        min-height: 34px;
        padding: 6px 26px 6px 9px;
        font-size: 12px;
    }

    .billing-actions .btn {
        width: auto;
        min-height: 34px;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
    }

    .company-usage-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        align-items: baseline;
        padding-bottom: 5px;
        border-bottom: 1px solid rgba(226, 232, 240, .75);
        font-size: 13px;
        line-height: 1.3;
    }

    .company-usage-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .company-usage-row span {
        color: var(--text-dim);
    }

    .company-usage-row strong {
        font-weight: 700;
        color: var(--text);
    }

    .user-list,
    .art-machine-list {
        display: grid;
        gap: 8px;
    }

    .user-row,
    .art-machine-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #f9fafb;
    }

    .art-machine-row {
        background: #fff;
    }

    .user-row small,
    .art-machine-row small {
        color: var(--text-dim);
        word-break: break-word;
        line-height: 1.35;
    }

    .system-modal-panel {
        display: none;
    }

    body.system-modal-open {
        overflow: hidden;
    }

    body.system-modal-open::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: 90;
        background: rgba(15, 23, 42, .46);
        backdrop-filter: blur(2px);
    }

    .system-modal-panel.open {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        position: fixed;
        left: 50%;
        top: 50%;
        z-index: 100;
        width: min(720px, calc(100vw - 32px));
        max-height: min(78vh, 720px);
        transform: translate(-50%, -50%);
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
    }

    .system-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #f9fafb;
    }

    .system-modal-head h3 {
        margin: 0;
        font-size: 1rem;
        line-height: 1.25;
    }

    .system-modal-body {
        min-height: 0;
        overflow: auto;
        padding: 14px;
    }

    .system-modal-body > h3:first-child {
        margin-top: 0;
    }

    .cnpj-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        align-items: end;
    }

    @media (max-width: 780px) {
        .system-tabs,
        .system-tabs .btn,
        .backup-card .btn {
            width: 100%;
        }

        .cnpj-row,
        .art-machine-row,
        .user-row,
        .system-user-edit {
            grid-template-columns: 1fr;
        }

        .company-actions {
            justify-content: flex-start;
        }

        .empresa-detail-head {
            display: grid;
        }

        .empresa-inner-tabs .btn,
        .system-user-actions .btn,
        .system-user-actions form {
            width: 100%;
        }
    }
</style>

<div class="top">
    <div>
        <h1>Sistema</h1>
        <p>Empresas, administradores, backup e manutenção geral.</p>
    </div>
    <form method="post" action="{{ route('admin.sistema.manutencao') }}" class="actions">
        @csrf
        @method('patch')
        @unless($manutencaoAtiva)
            <input type="hidden" name="manutencao_geral" value="1">
        @endunless
        <button class="btn {{ $manutencaoAtiva ? 'danger' : 'primary' }}" type="submit">
            {{ $manutencaoAtiva ? 'Desativar manutenção' : 'Ativar manutenção' }}
        </button>
    </form>
</div>

@if ($errors->any())
    <div class="notice danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<nav class="system-tabs" aria-label="Subpáginas do sistema">
    <a class="btn {{ in_array($paginaSistema, ['empresas', 'empresa'], true) ? 'primary' : '' }}" href="{{ route('admin.sistema.empresas') }}">Empresas cadastradas</a>
    <a class="btn {{ $paginaSistema === 'cadastrar' ? 'primary' : '' }}" href="{{ route('admin.sistema.create') }}">Cadastrar empresa</a>
    <a class="btn {{ $paginaSistema === 'proposta' ? 'primary' : '' }}" href="{{ route('admin.sistema.proposta') }}">Proposta</a>
    <a class="btn {{ $paginaSistema === 'backup' ? 'primary' : '' }}" href="{{ route('admin.sistema.backup') }}">Backup</a>
    <a class="btn {{ $paginaSistema === 'logs' ? 'primary' : '' }}" href="{{ route('admin.sistema.logs') }}">Logs</a>
</nav>

@if($paginaSistema === 'cadastrar')
    <section class="panel">
        <form method="post" action="{{ route('admin.sistema.empresas.store') }}" class="system-form" enctype="multipart/form-data">
            @csrf
            <div class="system-form-section">
                <div>
                    <h2>Dados da empresa</h2>
                    <p>Cadastre a empresa e defina limites, plano e situação de acesso.</p>
                </div>
                <div class="cnpj-row">
                    <label>CNPJ
                        <input id="empresa-cnpj" name="documento" inputmode="numeric" placeholder="00.000.000/0000-00" value="{{ old('documento') }}">
                    </label>
                    <button class="btn" type="button" id="buscar-cnpj">Buscar CNPJ</button>
                </div>
                <label>Nome da empresa <input id="empresa-nome" name="nome" value="{{ old('nome') }}" required></label>
                <label>Logo da empresa
                    <input name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                    <small>Usado nos PDFs de orçamento, contrato e relatórios.</small>
                </label>
                <div class="grid grid-2">
                    <label>Status
                        <select name="status">
                            <option value="ativa" @selected(old('status', 'ativa') === 'ativa')>Ativa</option>
                            <option value="inativa" @selected(old('status') === 'inativa')>Inativa</option>
                        </select>
                    </label>
                    <label>Mensalidade
                        <select name="mensalidade_em_dia">
                            <option value="1" @selected(old('mensalidade_em_dia', '1') === '1')>Em dia</option>
                            <option value="0" @selected(old('mensalidade_em_dia') === '0')>Pendente / bloquear acesso</option>
                        </select>
                    </label>
                    <label>Plano
                        <select name="plano_periodicidade">
                            <option value="mensal" @selected(old('plano_periodicidade', 'mensal') === 'mensal')>Mensal</option>
                            <option value="trimestral" @selected(old('plano_periodicidade') === 'trimestral')>Trimestral</option>
                            <option value="anual" @selected(old('plano_periodicidade') === 'anual')>Anual</option>
                        </select>
                    </label>
                    <label>Vencimento do plano <input name="plano_vencimento" type="date" value="{{ old('plano_vencimento') }}"></label>
                    <label>Limite de donos <input name="limite_admins" type="number" min="1" value="{{ old('limite_admins', 1) }}" required></label>
                    <input name="limite_operadores" type="hidden" value="{{ old('limite_operadores', 999) }}">
                    <label>Limite de máquinas <input name="limite_maquinas" type="number" min="0" value="{{ old('limite_maquinas', 20) }}" required></label>
                </div>
                <div>
                    <h3>Modulos da empresa</h3>
                    <div class="module-checks">
                        <label class="module-check">
                            <input type="checkbox" name="modulos[rastreamento]" value="1" @checked(old('modulos.rastreamento') || old('modulo_rastreamento'))>
                            <span>
                                <strong>Rastreamento e mapa</strong>
                                <small>Libera mapa, telemetria e histórico de localização.</small>
                            </span>
                        </label>
                    </div>
                </div>
                <p id="cnpj-feedback"></p>
            </div>

            <div class="system-form-section">
                <div>
                    <h2>Administrador da empresa</h2>
                    <p>Este usuário será criado como dono inicial da empresa.</p>
                </div>
                <div class="grid grid-2">
                    <label>Nome do administrador <input name="admin_nome" value="{{ old('admin_nome') }}" required></label>
                    <label>E-mail <input name="admin_email" type="email" value="{{ old('admin_email') }}" required></label>
                    <label>Senha <input name="admin_senha" type="password" required></label>
                    <label>Perfil
                        <select name="admin_perfil">
                            <option value="admin" @selected(old('admin_perfil', 'admin') === 'admin')>Dono / administrador</option>
                            <option value="comercial" @selected(old('admin_perfil') === 'comercial')>Comercial</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="actions end">
                <a class="btn" href="{{ route('admin.sistema.empresas') }}">Cancelar</a>
                <button class="btn primary" type="submit">Criar empresa e administrador</button>
            </div>
        </form>
    </section>
@elseif($paginaSistema === 'empresa' && isset($empresa))
    @php
        $abaEmpresa = $abaEmpresa ?? 'dados';
        $logoUrl = $empresa->logo_path ? asset('storage/'.$empresa->logo_path) : null;
    @endphp
    <section class="panel">
        <div class="empresa-detail-head">
            <div>
                <h2>{{ $empresa->nome }}</h2>
                <p>Gerencie dados, módulos, logo e usuários desta empresa.</p>
            </div>
            <a class="btn" href="{{ route('admin.sistema.empresas') }}">Voltar</a>
        </div>

        <div class="empresa-summary-grid">
            <div class="empresa-summary-card">
                <small>Situação</small>
                <strong>{{ ucfirst($empresa->status) }}</strong>
            </div>
            <div class="empresa-summary-card">
                <small>Plano</small>
                <strong>{{ $empresa->planoLabel() }}</strong>
            </div>
            <div class="empresa-summary-card">
                <small>Usuários</small>
                <strong>{{ $empresa->admins_count }}/{{ $empresa->limite_admins }}</strong>
            </div>
            <div class="empresa-summary-card">
                <small>Máquinas</small>
                <strong>{{ $empresa->maquinas_count }}/{{ $empresa->limite_maquinas }}</strong>
            </div>
            <div class="empresa-summary-card">
                <small>Propostas</small>
                <strong>{{ $empresa->sistema_propostas_count ?? $empresa->sistemaPropostas->count() }}</strong>
            </div>
        </div>

        <nav class="empresa-inner-tabs" aria-label="Configurações da empresa">
            <a class="btn {{ $abaEmpresa === 'dados' ? 'primary' : '' }}" href="{{ route('admin.sistema.empresas.show', ['empresa' => $empresa, 'aba' => 'dados']) }}">Dados</a>
            <a class="btn {{ $abaEmpresa === 'modulos' ? 'primary' : '' }}" href="{{ route('admin.sistema.empresas.show', ['empresa' => $empresa, 'aba' => 'modulos']) }}">Módulos e logo</a>
            <a class="btn {{ $abaEmpresa === 'usuarios' ? 'primary' : '' }}" href="{{ route('admin.sistema.empresas.show', ['empresa' => $empresa, 'aba' => 'usuarios']) }}">Usuários</a>
            <a class="btn {{ $abaEmpresa === 'propostas' ? 'primary' : '' }}" href="{{ route('admin.sistema.empresas.show', ['empresa' => $empresa, 'aba' => 'propostas']) }}">Propostas</a>
        </nav>

        @if($abaEmpresa === 'modulos')
            <form method="post" action="{{ route('admin.sistema.empresas.update', $empresa) }}" class="system-form" enctype="multipart/form-data">
                @csrf
                @method('put')
                <input type="hidden" name="nome" value="{{ $empresa->nome }}">
                <input type="hidden" name="documento" value="{{ $empresa->documento }}">
                <input type="hidden" name="status" value="{{ $empresa->status }}">
                <input type="hidden" name="mensalidade_em_dia" value="{{ $empresa->mensalidade_em_dia ? 1 : 0 }}">
                <input type="hidden" name="plano_periodicidade" value="{{ $empresa->plano_periodicidade }}">
                <input type="hidden" name="plano_vencimento" value="{{ $empresa->plano_vencimento?->format('Y-m-d') }}">
                <input type="hidden" name="limite_admins" value="{{ max(1, $empresa->limite_admins) }}">
                <input type="hidden" name="limite_operadores" value="{{ $empresa->limite_operadores ?: 999 }}">
                <input type="hidden" name="limite_maquinas" value="{{ $empresa->limite_maquinas }}">

                <div class="system-form-section">
                    <div>
                        <h2>Módulos</h2>
                        <p>Ative ou desative funcionalidades contratadas para esta empresa.</p>
                    </div>
                    <div class="module-checks">
                        <label class="module-check">
                            <input type="checkbox" name="modulos[rastreamento]" value="1" @checked($empresa->modulo_rastreamento)>
                            <span>
                                <strong>Rastreamento e mapa</strong>
                                <small>Libera mapa, telemetria, horímetro e histórico de localização.</small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="system-form-section">
                    <div>
                        <h2>Logo e papel timbrado</h2>
                        <p>Quando cadastrada, a logo aparece nos PDFs de orçamento, contrato e relatórios.</p>
                    </div>
                    <div class="system-logo-preview">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo de {{ $empresa->nome }}">
                        @else
                            <span class="muted">Nenhuma logo cadastrada.</span>
                        @endif
                    </div>
                    @if($logoUrl)
                        <label class="module-check">
                            <input type="checkbox" name="remover_logo" value="1">
                            <span>
                                <strong>Remover logo atual</strong>
                                <small>Os PDFs voltam ao layout padrão sem timbrado.</small>
                            </span>
                        </label>
                    @endif
                    <label>Enviar nova logo
                        <input name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                        <small>JPG, PNG ou WEBP até 4 MB.</small>
                    </label>
                </div>

                <div class="actions end">
                    <button class="btn primary" type="submit">Salvar módulos e logo</button>
                </div>
            </form>
        @elseif($abaEmpresa === 'usuarios')
            <div class="system-form">
                <form class="system-form-section" method="post" action="{{ route('admin.sistema.admins.store') }}">
                    @csrf
                    <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                    <div>
                        <h2>Novo usuário</h2>
                        <p>Crie acessos administrativos ou comerciais para esta empresa.</p>
                    </div>
                    <div class="grid grid-2">
                        <label>Nome <input name="nome" value="{{ old('nome') }}" required></label>
                        <label>E-mail <input name="email" type="email" value="{{ old('email') }}" required></label>
                        <label>Senha <input name="senha" type="password" required></label>
                        <label>Perfil
                            <select name="perfil" required>
                                <option value="admin">Dono / administrador</option>
                                <option value="comercial">Comercial</option>
                            </select>
                        </label>
                    </div>
                    <div class="actions end">
                        <button class="btn primary" type="submit">Adicionar usuário</button>
                    </div>
                </form>

                <div class="system-form-section">
                    <div>
                        <h2>Usuários cadastrados</h2>
                        <p>Edite dados de acesso, altere perfil ou remova usuários individualmente.</p>
                    </div>
                    @forelse($empresa->admins as $admin)
                        <div class="system-user-card">
                            <h3>{{ $admin->nome }}</h3>
                            <form class="system-user-edit" method="post" action="{{ route('admin.sistema.admins.update', $admin) }}">
                                @csrf
                                @method('put')
                                <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                                <label>Nome <input name="nome" value="{{ $admin->nome }}" required></label>
                                <label>E-mail <input name="email" type="email" value="{{ $admin->email }}" required></label>
                                <label>Nova senha <input name="senha" type="password" placeholder="Manter atual"></label>
                                <label>Perfil
                                    <select name="perfil" required>
                                        <option value="admin" @selected($admin->perfil() === 'admin')>Dono / administrador</option>
                                        <option value="comercial" @selected($admin->perfil() === 'comercial')>Comercial</option>
                                    </select>
                                </label>
                                <button class="btn primary" type="submit">Salvar</button>
                            </form>
                            <div class="system-user-actions">
                                <form method="post" action="{{ route('admin.sistema.admins.acessar', $admin) }}">
                                    @csrf
                                    <button class="btn small" type="submit" @disabled($empresa->status !== 'ativa')>Acessar como</button>
                                </form>
                                <form method="post" action="{{ route('admin.sistema.admins.destroy', $admin) }}" data-confirm="Deseja remover este usuário?">
                                    @csrf
                                    @method('delete')
                                    <button class="btn small danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p>Nenhum usuário cadastrado nesta empresa.</p>
                    @endforelse
                </div>
            </div>
        @elseif($abaEmpresa === 'propostas')
            <div class="system-form-section">
                <div class="section-head">
                    <div>
                        <h2>Propostas da empresa</h2>
                        <p>Histórico comercial vinculado a esta empresa.</p>
                    </div>
                    <a class="btn primary" href="{{ route('admin.sistema.proposta', ['empresa_id' => $empresa->id]) }}">Nova proposta</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Proposta</th>
                                <th>Status</th>
                                <th>Mensal</th>
                                <th>Implantação</th>
                                <th>Resposta</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($empresa->sistemaPropostas as $proposta)
                                <tr>
                                    <td>
                                        <strong>#{{ $proposta->id }}</strong>
                                        <small>{{ $proposta->contato_nome ?: 'Contato não informado' }}</small>
                                    </td>
                                    <td><span class="status-line {{ $proposta->statusClasse() }}">{{ $proposta->statusLabel() }}</span></td>
                                    <td>R$ {{ number_format($proposta->totalMensal(), 2, ',', '.') }}</td>
                                    <td>R$ {{ number_format($proposta->totalImplantacao(), 2, ',', '.') }}</td>
                                    <td>
                                        @if($proposta->respondida_em)
                                            {{ $proposta->respondida_em->format('d/m/Y') }}
                                        @elseif($proposta->enviada_em)
                                            Enviada em {{ $proposta->enviada_em->format('d/m/Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="actions compact-actions">
                                            <a class="btn small" href="{{ route('admin.sistema.propostas.pdf', $proposta) }}">PDF</a>
                                            <form method="post" action="{{ route('admin.sistema.propostas.status', $proposta) }}" data-unsaved-ignore="1">
                                                @csrf
                                                @method('patch')
                                                <select name="status" onchange="this.form.submit()">
                                                    @foreach(\App\Models\SistemaProposta::STATUS as $statusValor => $statusLabel)
                                                        <option value="{{ $statusValor }}" @selected($proposta->status === $statusValor)>{{ $statusLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                            <form method="post" action="{{ route('admin.sistema.propostas.destroy', $proposta) }}" data-confirm="Remover esta proposta?">
                                                @csrf
                                                @method('delete')
                                                <button class="btn small danger" type="submit">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">Nenhuma proposta vinculada a esta empresa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <form method="post" action="{{ route('admin.sistema.empresas.update', $empresa) }}" class="system-form">
                @csrf
                @method('put')
                @if($empresa->modulo_rastreamento)
                    <input type="hidden" name="modulo_rastreamento" value="1">
                @endif
                <div class="system-form-section">
                    <div>
                        <h2>Dados da empresa</h2>
                        <p>Atualize cadastro, limites contratados, plano e situação de acesso.</p>
                    </div>
                    <div class="grid grid-2">
                        <label>Nome da empresa <input name="nome" value="{{ $empresa->nome }}" required></label>
                        <label>CNPJ <input name="documento" value="{{ $empresa->documento }}" inputmode="numeric"></label>
                        <label>Status
                            <select name="status">
                                <option value="ativa" @selected($empresa->status === 'ativa')>Ativa</option>
                                <option value="inativa" @selected($empresa->status === 'inativa')>Inativa</option>
                            </select>
                        </label>
                        <label>Mensalidade
                            <select name="mensalidade_em_dia">
                                <option value="1" @selected($empresa->mensalidade_em_dia)>Em dia</option>
                                <option value="0" @selected(! $empresa->mensalidade_em_dia)>Pendente / bloquear acesso</option>
                            </select>
                        </label>
                        <label>Plano
                            <select name="plano_periodicidade">
                                <option value="mensal" @selected($empresa->plano_periodicidade === 'mensal')>Mensal</option>
                                <option value="trimestral" @selected($empresa->plano_periodicidade === 'trimestral')>Trimestral</option>
                                <option value="anual" @selected($empresa->plano_periodicidade === 'anual')>Anual</option>
                            </select>
                        </label>
                        <label>Vencimento do plano <input name="plano_vencimento" type="date" value="{{ $empresa->plano_vencimento?->format('Y-m-d') }}"></label>
                        <label>Limite de donos <input name="limite_admins" type="number" min="1" value="{{ max(1, $empresa->limite_admins) }}" required></label>
                        <input name="limite_operadores" type="hidden" value="{{ $empresa->limite_operadores ?: 999 }}">
                        <label>Limite de máquinas <input name="limite_maquinas" type="number" min="0" value="{{ $empresa->limite_maquinas }}" required></label>
                    </div>
                </div>
                <div class="actions end">
                    <button class="btn primary" type="submit">Salvar dados</button>
                </div>
            </form>
        @endif
    </section>
@elseif($paginaSistema === 'proposta')
    @php
        $empresaPropostaId = old('empresa_id', request('empresa_id'));
        $empresaPropostaSelecionada = $empresas->firstWhere('id', (int) $empresaPropostaId);
    @endphp
    <section class="panel">
        <form method="post" action="{{ route('admin.sistema.propostas.store') }}" class="system-form">
            @csrf
            <div class="system-form-section">
                <div>
                    <h2>Proposta comercial</h2>
                    <p>Gere uma proposta para venda do sistema, rastreamento e comodato dos rastreadores.</p>
                </div>
                <div class="grid grid-2">
                    <label>Empresa vinculada
                        <select name="empresa_id" id="proposta-empresa-select" required>
                            <option value="">Selecione uma empresa</option>
                            @foreach($empresas as $empresaProposta)
                                <option
                                    value="{{ $empresaProposta->id }}"
                                    data-nome="{{ $empresaProposta->nome }}"
                                    data-documento="{{ $empresaProposta->documento }}"
                                    @selected((string) $empresaPropostaId === (string) $empresaProposta->id)
                                >
                                    {{ $empresaProposta->nome }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Cliente
                        <input id="proposta-cliente-nome" name="cliente_nome" value="{{ old('cliente_nome', $empresaPropostaSelecionada?->nome) }}" placeholder="Nome da empresa">
                    </label>
                    <label>Documento
                        <input id="proposta-cliente-documento" name="cliente_documento" value="{{ old('cliente_documento', $empresaPropostaSelecionada?->documento) }}" placeholder="CNPJ, CPF ou identificação">
                    </label>
                    <label>Contato
                        <input name="contato_nome" value="{{ old('contato_nome') }}" placeholder="Nome do responsável">
                    </label>
                    <label>E-mail
                        <input name="contato_email" type="email" value="{{ old('contato_email') }}" placeholder="contato@empresa.com.br">
                    </label>
                    <label>Telefone
                        <input name="contato_telefone" value="{{ old('contato_telefone') }}" placeholder="(00) 00000-0000">
                    </label>
                    <label>Dia de vencimento mensal
                        <input name="vencimento_mensal_dia" type="number" min="1" max="31" value="{{ old('vencimento_mensal_dia', 10) }}" required>
                    </label>
                </div>
            </div>

            <div class="system-form-section">
                <div>
                    <h2>Valores e condições</h2>
                    <p>Informe mensalidade, comodato, treinamento, instala&ccedil;&atilde;o, deslocamento e prazo comercial.</p>
                </div>
                <div class="grid grid-2">
                    <label>Quantidade de rastreadores
                        <input name="quantidade_rastreadores" type="number" min="0" step="1" value="{{ old('quantidade_rastreadores', 10) }}" required>
                    </label>
                    <label>Valor referencial do rastreador (R$)
                        <input name="valor_rastreador" type="number" min="0" step="0.01" value="{{ old('valor_rastreador', 0) }}">
                    </label>
                    <label>Software mensal (R$)
                        <input name="valor_sistema_mensal" type="number" min="0" step="0.01" value="{{ old('valor_sistema_mensal', 0) }}" required>
                    </label>
                    <label>Comodato mensal por rastreador (R$)
                        <input name="valor_comodato_mensal" type="number" min="0" step="0.01" value="{{ old('valor_comodato_mensal', 0) }}">
                    </label>
                    <label>Treinamento (R$)
                        <input name="valor_treinamento" type="number" min="0" step="0.01" value="{{ old('valor_treinamento', 0) }}">
                    </label>
                    <label>Instala&ccedil;&atilde;o (R$)
                        <input name="valor_instalacao" type="number" min="0" step="0.01" value="{{ old('valor_instalacao', 0) }}">
                    </label>
                    <label>Deslocamento (R$)
                        <input name="valor_deslocamento" type="number" min="0" step="0.01" value="{{ old('valor_deslocamento', 0) }}">
                    </label>
                    <label>Prazo do contrato (meses)
                        <input name="prazo_contrato_meses" type="number" min="1" max="120" value="{{ old('prazo_contrato_meses', 12) }}">
                    </label>
                    <label>Validade da proposta (dias)
                        <input name="validade_dias" type="number" min="1" max="90" value="{{ old('validade_dias', 10) }}">
                    </label>
                    <label>Status inicial
                        <select name="status">
                            <option value="rascunho" @selected(old('status', 'rascunho') === 'rascunho')>Rascunho</option>
                            <option value="enviada" @selected(old('status') === 'enviada')>Enviada</option>
                        </select>
                    </label>
                    <label>Condições de pagamento
                        <input name="condicoes_pagamento" value="{{ old('condicoes_pagamento', 'Boleto ou PIX') }}">
                    </label>
                </div>
                <label>Observações comerciais
                    <textarea name="observacoes" rows="4" placeholder="Ex.: instalação bonificada conforme quantidade contratada, chip de dados, deslocamento ou condições especiais.">{{ old('observacoes') }}</textarea>
                </label>
            </div>

            <div class="actions end">
                <button class="btn primary" type="submit">Salvar proposta</button>
            </div>
        </form>
    </section>
@elseif($paginaSistema === 'cobrancas')
    <section class="panel">
        <div class="section-head" style="margin-bottom:12px">
            <div>
                <h2>Cobran&ccedil;as</h2>
                <p>Controle mensal dos clientes do sistema, comodato e rastreamento.</p>
            </div>
        </div>
        <form method="post" action="{{ route('admin.sistema.cobrancas.store') }}" class="system-form" style="margin-bottom:18px">
            @csrf
            <div class="system-form-section">
                <div class="grid grid-2">
                    <label>Cliente
                        <select name="cliente_nome" required>
                            <option value="">Selecione uma empresa</option>
                            @foreach($empresas as $empresaCobranca)
                                <option value="{{ $empresaCobranca->nome }}" @selected(old('cliente_nome') === $empresaCobranca->nome)>{{ $empresaCobranca->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Descri&ccedil;&atilde;o
                        <input name="descricao" value="{{ old('descricao', 'Mensalidade Gestão de Máquinas') }}">
                    </label>
                    <label>M&ecirc;s de refer&ecirc;ncia
                        <input name="referencia_mes" type="month" value="{{ old('referencia_mes', now()->format('Y-m')) }}">
                    </label>
                    <label>Vencimento
                        <input name="vencimento" type="date" value="{{ old('vencimento') }}" required>
                    </label>
                    <label>Valor (R$)
                        <input name="valor" type="number" min="0" step="0.01" value="{{ old('valor', 0) }}" required>
                    </label>
                    <label>Status
                        <select name="status">
                            @foreach(($cobrancaStatus ?? ['aberta' => 'Aberta']) as $statusValor => $statusLabel)
                                <option value="{{ $statusValor }}" @selected(old('status', 'aberta') === $statusValor)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Forma de pagamento
                        <input name="forma_pagamento" value="{{ old('forma_pagamento', 'Boleto ou PIX') }}">
                    </label>
                </div>
                <label>Observa&ccedil;&otilde;es
                    <textarea name="observacoes" rows="3">{{ old('observacoes') }}</textarea>
                </label>
            </div>
            <div class="actions end">
                <button class="btn primary" type="submit">Salvar cobran&ccedil;a</button>
            </div>
        </form>

        <form method="get" action="{{ route('admin.sistema.cobrancas') }}" class="top-filter-form" style="justify-content:flex-start;margin-bottom:14px">
            <label>Busca
                <input name="busca" value="{{ request('busca') }}" placeholder="Cliente">
            </label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option>
                    @foreach(($cobrancaStatus ?? []) as $statusValor => $statusLabel)
                        <option value="{{ $statusValor }}" @selected(request('status') === $statusValor)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label>Refer&ecirc;ncia
                <input name="referencia_mes" type="month" value="{{ request('referencia_mes') }}">
            </label>
            <button class="btn" type="submit">Filtrar</button>
        </form>
        <div class="table-wrap">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Refer&ecirc;ncia</th>
                        <th>Vencimento</th>
                        <th>Valor (R$)</th>
                        <th>Status</th>
                        <th>A&ccedil;&otilde;es</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($cobrancas ?? collect()) as $cobranca)
                        <tr>
                            <td>
                                <div class="billing-client-cell" title="{{ $cobranca->cliente_nome }}">
                                    <strong>{{ \Illuminate\Support\Str::limit($cobranca->cliente_nome, 34) }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit($cobranca->descricao ?: 'Mensalidade', 38) }}</small>
                                </div>
                            </td>
                            <td>{{ $cobranca->referencia_mes ? $cobranca->referencia_mes->format('m/Y') : '-' }}</td>
                            <td>{{ $cobranca->vencimento?->format('d/m/Y') }}</td>
                            <td>R$ {{ number_format((float) $cobranca->valor, 2, ',', '.') }}</td>
                            <td><span class="status-line {{ $cobranca->statusClasse() }}">{{ $cobranca->statusLabel() }}</span></td>
                            <td>
                                <div class="billing-actions">
                                    <form method="post" action="{{ route('admin.sistema.cobrancas.update', $cobranca) }}" data-unsaved-ignore="1">
                                        @csrf
                                        @method('patch')
                                        <select name="status" onchange="this.form.submit()">
                                            @foreach(($cobrancaStatus ?? []) as $statusValor => $statusLabel)
                                                <option value="{{ $statusValor }}" @selected($cobranca->status === $statusValor)>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="post" action="{{ route('admin.sistema.cobrancas.destroy', $cobranca) }}" data-confirm-submit="Remover esta cobrança?">
                                        @csrf
                                        @method('delete')
                                        <button class="btn danger" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Nenhuma cobran&ccedil;a encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($cobrancas) && method_exists($cobrancas, 'links'))
            {{ $cobrancas->links() }}
        @endif
    </section>
@elseif($paginaSistema === 'backup')
    <section class="panel backup-card">
        <div>
            <h2>Backup do sistema</h2>
            <p>Baixe um arquivo ZIP com banco de dados, dados do sistema e arquivos enviados no armazenamento público.</p>
        </div>
        <a class="btn primary" href="{{ route('admin.sistema.backup.download') }}">Baixar backup</a>
    </section>
@elseif($paginaSistema === 'logs')
    <section class="panel">
        <div class="section-head" style="margin-bottom:12px">
            <div>
                <h2>Logs de seguranca</h2>
                <p>Eventos de acesso bloqueado, limite de tentativas, rotas inexistentes e entradas suspeitas.</p>
            </div>
        </div>
        <form method="get" action="{{ route('admin.sistema.logs') }}" class="top-filter-form" style="justify-content:flex-start;margin-bottom:14px">
            <label>Risco
                <select name="risco">
                    <option value="">Todos</option>
                    <option value="baixo" @selected(request('risco') === 'baixo')>Baixo</option>
                    <option value="medio" @selected(request('risco') === 'medio')>Medio</option>
                    <option value="alto" @selected(request('risco') === 'alto')>Alto</option>
                    <option value="critico" @selected(request('risco') === 'critico')>Critico</option>
                </select>
            </label>
            <label>Evento
                <select name="evento">
                    <option value="">Todos</option>
                    @foreach(($securityLogEventos ?? collect()) as $evento)
                        <option value="{{ $evento }}" @selected(request('evento') === $evento)>{{ str_replace('_', ' ', $evento) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Empresa
                <select name="empresa_id">
                    <option value="">Todas</option>
                    @foreach($empresas as $empresaFiltro)
                        <option value="{{ $empresaFiltro->id }}" @selected((string) request('empresa_id') === (string) $empresaFiltro->id)>{{ $empresaFiltro->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label>Inicio <input type="date" name="inicio" value="{{ request('inicio') }}"></label>
            <label>Fim <input type="date" name="fim" value="{{ request('fim') }}"></label>
            <div class="top-filter-actions">
                <button class="btn primary" type="submit">Filtrar</button>
                <a class="btn" href="{{ route('admin.sistema.logs') }}">Limpar</a>
            </div>
        </form>
        <div class="table-wrap">
            <table class="security-log-table">
                <colgroup>
                    <col style="width: 16%">
                    <col style="width: 13%">
                    <col style="width: 16%">
                    <col style="width: 24%">
                    <col style="width: 16%">
                    <col style="width: 15%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Quando</th>
                        <th>Risco</th>
                        <th>Evento</th>
                        <th>Rota</th>
                        <th>Usuario</th>
                        <th>Origem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($securityLogs ?? collect()) as $log)
                        <tr>
                            <td>
                                <strong>{{ $log->created_at?->format('d/m/Y H:i') }}</strong><br>
                                <small>Status {{ $log->status_code ?: '-' }}</small>
                            </td>
                            <td><span class="security-risk risco-{{ $log->risco }}">{{ $log->riscoLabel() }}</span></td>
                            <td>
                                <div class="security-event">
                                    <strong>{{ str_replace('_', ' ', $log->evento) }}</strong>
                                    <small>{{ $log->empresa?->nome ?: 'Sem empresa' }}</small>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $log->method }}</strong> {{ $log->path }}<br>
                                <small>{{ $log->route_name ?: 'Sem rota nomeada' }}</small>
                            </td>
                            <td>
                                {{ $log->admin?->nome ?: $log->operador?->nome ?: 'Nao autenticado' }}<br>
                                <small>{{ $log->admin?->email ?: $log->operador?->usuario }}</small>
                            </td>
                            <td>
                                {{ $log->ip ?: '-' }}<br>
                                <small>{{ $log->user_agent ?: '-' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Nenhum evento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(($securityLogs ?? null) && method_exists($securityLogs, 'links'))
            {{ $securityLogs->links() }}
        @endif
    </section>
@else
    <section class="panel table-wrap">
        <div class="section-head" style="margin-bottom:12px">
            <div>
                <h2>Empresas cadastradas</h2>
            </div>
            <a class="btn primary" href="{{ route('admin.sistema.create') }}">Cadastrar empresa</a>
        </div>
        <table class="companies-table">
            <colgroup>
                <col style="width: 20%">
                <col style="width: 14%">
                <col style="width: 17%">
                <col style="width: 18%">
                <col style="width: 20%">
                <col style="width: 11%">
            </colgroup>
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Situação</th>
                    <th>Plano</th>
                    <th>ART segurança</th>
                    <th>Resumo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($empresas as $empresa)
                @php
                    $adminsEmpresa = $admins->where('empresa_id', $empresa->id);
                @endphp
                <tr>
                    <td><div class="company-name-cell">{{ $empresa->nome }}</div></td>
                    <td>
                        <div class="system-info-list">
                            <span class="system-status-line">
                                <i class="system-dot {{ $empresa->status === 'ativa' ? 'status-conforme' : 'danger' }}"></i>
                                <span>{{ ucfirst($empresa->status) }}</span>
                            </span>
                            <span class="system-status-line">
                                <i class="system-dot {{ $empresa->mensalidade_em_dia ? 'status-conforme' : 'danger' }}"></i>
                                <span>{{ $empresa->mensalidade_em_dia ? 'Em dia' : 'Bloqueada' }}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="system-info-list">
                            <span class="system-status-line">
                                <i class="system-dot ok"></i>
                                <span>{{ $empresa->planoLabel() }}</span>
                            </span>
                            <span class="system-status-line">
                                <i class="system-dot {{ $empresa->contadorPlanoClasse() }}"></i>
                                <span>{{ $empresa->contadorPlano() }}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="system-info-list">
                            <span class="system-status-line">
                                <i class="system-dot {{ $empresa->contadorArtSegurancaClasse() }}"></i>
                                <span>{{ $empresa->contadorArtSeguranca() }}</span>
                            </span>
                            @if($empresa->maquinas_count > 0)
                                <button class="btn small" type="button" data-art-toggle="{{ $empresa->id }}">Ver ARTs</button>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="company-usage-list">
                            <span class="company-usage-row">
                                <span>Donos</span>
                                <strong>{{ $empresa->admins_count }}/{{ $empresa->limite_admins }}</strong>
                            </span>
                            <span class="company-usage-row">
                                <span>Máquinas</span>
                                <strong>{{ $empresa->maquinas_count }}/{{ $empresa->limite_maquinas }}</strong>
                            </span>
                            <span class="company-usage-row">
                                <span>Rastreamento</span>
                                <strong>{{ $empresa->modulo_rastreamento ? 'Ativo' : 'Inativo' }}</strong>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="company-actions">
                            <a class="btn small primary" href="{{ route('admin.sistema.empresas.show', $empresa) }}">Abrir</a>
                            <form method="post" action="{{ route('admin.sistema.empresas.destroy', $empresa) }}" data-confirm="Deseja excluir esta empresa e todos os dados vinculados? Máquinas, administradores, checklists, históricos e alertas serão apagados.">
                                @csrf
                                @method('delete')
                                <button class="btn small danger" type="submit">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <div class="system-modal-panel art-panel" id="empresa-art-{{ $empresa->id }}" role="dialog" aria-modal="true" aria-labelledby="empresa-art-title-{{ $empresa->id }}">
                            <div class="system-modal-head">
                                <h3 id="empresa-art-title-{{ $empresa->id }}">ART segurança das máquinas</h3>
                                <button class="btn small" type="button" data-system-modal-close>Fechar</button>
                            </div>
                            <div class="system-modal-body">
                                <div class="art-machine-list">
                                    @php
                                        $maquinasArtOrdenadas = $empresa->maquinas->sortBy(function ($maquinaArt) {
                                            return $maquinaArt->diasParaArtSeguranca() ?? 999999;
                                        });
                                    @endphp
                                    @forelse($maquinasArtOrdenadas as $maquinaArt)
                                        <div class="art-machine-row">
                                            <div>
                                                <strong>{{ $maquinaArt->nome }}</strong>
                                                <small>
                                                    {{ $maquinaArt->setor ?: 'Sem setor' }}
                                                    @if($maquinaArt->codigo_interno)
                                                        &middot; {{ $maquinaArt->codigo_interno }}
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="system-info-list">
                                                @if($maquinaArt->art_seguranca_vencimento)
                                                    <span class="system-status-line">
                                                        <i class="system-dot ok"></i>
                                                        <span>{{ $maquinaArt->art_seguranca_vencimento->format('d/m/Y') }}</span>
                                                    </span>
                                                @endif
                                                <span class="system-status-line">
                                                    <i class="system-dot {{ $maquinaArt->contadorArtSegurancaClasse() }}"></i>
                                                    <span>{{ $maquinaArt->contadorArtSeguranca() }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <p>Nenhuma máquina cadastrada nesta empresa.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endif

<script>
const cnpjInput = document.getElementById('empresa-cnpj');
const nomeInput = document.getElementById('empresa-nome');
const feedback = document.getElementById('cnpj-feedback');
const buscar = document.getElementById('buscar-cnpj');
const propostaEmpresaSelect = document.getElementById('proposta-empresa-select');
const propostaClienteNome = document.getElementById('proposta-cliente-nome');
const propostaClienteDocumento = document.getElementById('proposta-cliente-documento');

buscar?.addEventListener('click', async () => {
    const cnpj = (cnpjInput.value || '').replace(/\D/g, '');

    if (cnpj.length !== 14) {
        feedback.textContent = 'Informe um CNPJ com 14 dígitos.';
        return;
    }

    feedback.textContent = 'Buscando dados do CNPJ...';
    buscar.disabled = true;

    try {
        const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`);

        if (! response.ok) {
            throw new Error('CNPJ não encontrado.');
        }

        const data = await response.json();
        nomeInput.value = data.razao_social || data.nome_fantasia || nomeInput.value;
        cnpjInput.value = data.cnpj || cnpjInput.value;
        feedback.textContent = 'Dados encontrados e preenchidos.';
    } catch (error) {
        feedback.textContent = error.message || 'Não foi possível buscar o CNPJ agora.';
    } finally {
        buscar.disabled = false;
    }
});

propostaEmpresaSelect?.addEventListener('change', () => {
    const option = propostaEmpresaSelect.selectedOptions[0];

    if (! option) {
        return;
    }

    if (propostaClienteNome) {
        propostaClienteNome.value = option.dataset.nome || '';
    }

    if (propostaClienteDocumento) {
        propostaClienteDocumento.value = option.dataset.documento || '';
    }
});

function closeSystemModals() {
    document.querySelectorAll('.system-modal-panel.open').forEach((openPanel) => {
        openPanel.classList.remove('open');
    });

    document.querySelectorAll('[data-art-toggle]').forEach((button) => {
        button.textContent = 'Ver ARTs';
    });

    document.body.classList.remove('system-modal-open');
}

function openSystemModal(panel, trigger = null) {
    if (! panel) {
        return;
    }

    const willOpen = !panel.classList.contains('open');
    closeSystemModals();

    if (! willOpen) {
        return;
    }

    panel.classList.add('open');
    document.body.classList.add('system-modal-open');

    if (trigger?.matches('[data-art-toggle]')) {
        trigger.textContent = 'Ocultar ARTs';
    }

    panel.querySelector('input, select, textarea, button, a')?.focus();
}

document.querySelectorAll('[data-art-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        openSystemModal(document.getElementById(`empresa-art-${button.dataset.artToggle}`), button);
    });
});

document.querySelectorAll('[data-system-modal-close]').forEach((button) => {
    button.addEventListener('click', closeSystemModals);
});

document.addEventListener('click', (event) => {
    if (document.body.classList.contains('system-modal-open')
        && ! event.target.closest('.system-modal-panel.open')
        && ! event.target.closest('[data-art-toggle]')) {
        closeSystemModals();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSystemModals();
    }
});
</script>
@endsection
