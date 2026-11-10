<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Gestão de Máquinas' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}?v=20260807-transp">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #f7f8fb;
            --panel: #ffffff;
            --panel-strong: #ffffff;
            --panel-soft: #f7f8fb;
            --border: #e7eaf0;
            --border-strong: #cfd6e3;
            --orange: #10131a;
            --orange-2: #2d3444;
            --text: #10131a;
            --text-dim: #667085;
            --text-faint: #98a2b3;
            --green: #059669;
            --green-soft: #dcfce7;
            --red: #dc2626;
            --red-soft: #fee2e2;
            --radius-lg: 10px;
            --radius-md: 10px;
            --radius-sm: 8px;
            --shadow: 0 10px 28px rgba(16, 19, 26, 0.06);
            --accent: #baff5a;
            --accent-soft: #eefbd8;
            --glow: 0 16px 30px rgba(186, 255, 90, 0.22);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 18% 12%, rgba(186, 255, 90, 0.18), transparent 24%),
                radial-gradient(circle at 88% 0%, rgba(79, 70, 229, 0.12), transparent 28%),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 15px;
            line-height: 1.5;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
            font-weight: 500;
            transition: 0.18s ease;
        }

        a:hover { color: var(--orange); }

        h1 {
            margin: 0;
            color: var(--text);
            font-size: 24px;
            line-height: 1.15;
            letter-spacing: 0;
            font-weight: 600;
        }

        h2 {
            color: var(--text);
            font-size: 18px;
            margin: 0 0 14px;
            letter-spacing: 0;
            font-weight: 600;
        }

        h3 {
            color: var(--text);
            font-size: 16px;
            margin: 14px 0 10px;
            font-weight: 600;
        }

        p {
            color: var(--text-dim);
            line-height: 1.55;
            margin: 5px 0 0;
        }

        .shell {
            min-height: 100vh;
            display: block;
            background: var(--bg);
        }

        .admin-header {
            position: sticky;
            top: 0;
            z-index: 2000;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            min-height: 58px;
            padding: 6px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #080a12;
            box-shadow: 0 14px 34px rgba(16, 19, 26, 0.14);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            min-height: 40px;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.15;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .brand img {
            width: 62px;
            height: 40px;
            flex: 0 0 auto;
            object-fit: contain;
        }

        .nav {
            display: flex;
            gap: 6px;
            align-items: center;
            min-width: 0;
            overflow: visible;
            padding: 4px;
            scrollbar-width: thin;
        }

        .nav a,
        .nav-menu summary,
        .logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            min-height: 36px;
            padding: 7px 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-sm);
            color: rgba(255, 255, 255, 0.66);
            background: rgba(255, 255, 255, 0.03);
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.25;
            text-align: center;
            white-space: nowrap;
            cursor: pointer;
            transition: 0.18s ease;
        }

        .nav a:hover,
        .nav a.active,
        .nav-menu.is-active summary,
        .nav-menu[open] summary,
        .nav-menu summary:hover {
            background: rgba(186, 255, 90, 0.12);
            color: var(--accent);
        }

        .nav-menu {
            position: relative;
            flex: 0 0 auto;
        }

        .nav-menu summary {
            list-style: none;
            gap: 7px;
        }

        .nav-menu summary::-webkit-details-marker {
            display: none;
        }

        .nav-menu summary::after {
            content: "";
            width: 6px;
            height: 6px;
            border-right: 1.5px solid currentColor;
            border-bottom: 1.5px solid currentColor;
            transform: rotate(45deg) translateY(-1px);
            opacity: 0.8;
        }

        .nav-menu[open] summary::after {
            transform: rotate(225deg) translate(-1px, -1px);
        }

        .nav-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            z-index: 2100;
            display: grid;
            gap: 5px;
            min-width: 188px;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            background: #0f131d;
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.24);
        }

        .nav-dropdown a {
            justify-content: flex-start;
            width: 100%;
            min-height: 34px;
            background: transparent;
        }

        .nav-dropdown a:hover,
        .nav-dropdown a.active {
            background: rgba(186, 255, 90, 0.12);
        }

        .nav-dropdown-right {
            right: 0;
            left: auto;
        }

        .nav form { margin: 0; flex: 0 0 auto; }

        .logout {
            width: auto;
            color: rgba(255, 255, 255, 0.72);
        }

        .logout:hover {
            color: var(--red);
            border-color: rgba(254, 202, 202, 0.28);
            background: rgba(220, 38, 38, 0.14);
        }

        .side-user {
            display: grid;
            min-width: 0;
            max-width: 170px;
            padding: 7px 9px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            color: #ffffff;
            font-size: 12px;
        }

        .side-user strong {
            display: block;
            max-width: 100%;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .main {
            min-width: 0;
            padding: 18px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .main > .top {
            min-height: auto;
            border: 1px solid rgba(16, 19, 26, 0.08);
            border-radius: var(--radius-lg);
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
        }

        .top.top-filter {
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 12px;
        }

        .title-copy {
            display: grid;
            gap: 3px;
            min-width: min(100%, 170px);
        }

        .title-copy .btn {
            width: fit-content;
            min-height: 30px;
            padding: 5px 9px;
            font-size: 12px;
        }

        .top-filter-form {
            flex: 1 1 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: end;
            justify-content: flex-end;
            min-width: 0;
            margin: 0;
            overflow: visible;
            padding-bottom: 2px;
            scrollbar-width: thin;
        }

        .top-filter-form label {
            flex: 0 0 auto;
            gap: 2px;
            font-size: 10px;
            line-height: 1.15;
            color: var(--muted);
        }

        .top-filter-form input,
        .top-filter-form select {
            width: 112px;
            min-height: 28px;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 12px;
        }

        .top-filter-form input[type="date"] {
            width: 118px;
        }

        .top-filter-form input[type="search"],
        .top-filter-form input[name="busca"],
        .top-filter-form input[name="q"] {
            width: 160px;
        }

        .top-filter-actions {
            flex: 0 0 auto;
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .top-filter-actions .btn {
            min-height: 30px;
            padding: 4px 8px;
            font-size: 12px;
            white-space: nowrap;
        }

        .panel,
        .card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
            box-shadow: 0 8px 22px rgba(16, 19, 26, 0.045);
        }

        .card {
            min-width: 0;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 7px;
            color: var(--text);
        }

        .card strong {
            display: block;
            font-size: 30px;
            line-height: 1;
            color: var(--text);
            letter-spacing: 0;
            font-weight: 650;
        }

        .card span {
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 400;
            line-height: 1.35;
        }

        .dashboard-grid .card:first-child {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-color: var(--border);
        }

        .stack { display: grid; gap: 16px; }

        .grid {
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .dashboard-grid { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); align-items: stretch; margin-bottom: 16px; }
        .filter-panel { grid-column: span 2; align-content: start; }
        .filter-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }

        .compact-filter {
            margin-bottom: 14px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(16, 19, 26, 0.05);
        }

        .compact-filter .filter-grid {
            grid-template-columns: repeat(auto-fit, minmax(128px, 1fr));
            gap: 10px;
        }

        .compact-filter label {
            gap: 4px;
            font-size: 12px;
        }

        .compact-filter input,
        .compact-filter select {
            min-height: 36px;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            font-size: 13px;
        }

        .compact-filter .actions {
            margin-top: 2px;
        }

        .tabs {
            display: flex;
            gap: 6px;
            margin: 0 0 16px;
            padding: 6px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #ffffff;
            width: fit-content;
            max-width: 100%;
            overflow-x: auto;
        }

        .tabs a {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 8px 14px;
            border-radius: 8px;
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }

        .tabs a.active,
        .tabs a:hover {
            background: #10131a;
            color: #ffffff;
            text-decoration: none;
        }

        .section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .section-head p {
            margin-top: 4px;
            max-width: 760px;
            color: var(--text-dim);
        }

        .checklist-card {
            min-width: 0;
            min-height: 240px;
            display: grid;
            gap: 16px;
            align-content: space-between;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: #ffffff;
            box-shadow: 0 8px 22px rgba(16, 19, 26, 0.045);
        }

        .checklist-card h3 {
            margin: 12px 0 6px;
            font-size: 17px;
            line-height: 1.25;
        }

        .checklist-card p,
        .checklist-card small {
            color: var(--text-dim);
        }

        .checklist-status-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .checklist-meta {
            display: grid;
            gap: 4px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .checklist-meta span {
            color: var(--text-faint);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .form-row {
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 14px;
            display: grid;
            grid-template-columns: minmax(260px, 1.45fr) minmax(150px, 0.7fr) minmax(96px, 0.35fr) minmax(180px, 0.7fr);
            gap: 14px;
            align-items: end;
        }

        .table-wrap {
            overflow-x: auto;
            overflow-y: visible;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: transparent;
            border-radius: var(--radius-md);
            min-width: 760px;
        }

        th,
        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
            color: var(--text-dim);
            font-size: 14px;
        }

        td strong,
        td a:not(.btn) { color: var(--text); }

        th {
            color: #4b5563;
            font-size: 12px;
            background: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: #f7f8fb; }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-md);
            font: inherit;
            color: var(--text);
            background: #ffffff;
            outline: none;
            transition: 0.18s ease;
        }

        input::placeholder,
        textarea::placeholder { color: var(--text-faint); }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #10131a;
            box-shadow: 0 0 0 4px rgba(186, 255, 90, 0.26);
            background: #ffffff;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        label {
            display: grid;
            gap: 6px;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
        }

        label span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dim);
            font-weight: 500;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .actions.end { justify-content: flex-end; }
        .actions.spread { justify-content: space-between; width: 100%; }

        .action-form {
            display: inline-flex;
            margin: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 9px 14px;
            border: 1px solid rgba(16, 19, 26, 0.14);
            border-radius: var(--radius-sm);
            background: #ffffff;
            color: #10131a;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.25;
            text-align: center;
            cursor: pointer;
            transition: 0.18s ease;
            box-shadow: none;
            text-decoration: none;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .btn.small {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .btn:hover {
            background: #f8fafc;
            border-color: #cfd6e3;
            color: #10131a;
        }

        .btn.primary {
            background: rgba(20, 184, 166, .12);
            border-color: rgba(20, 184, 166, .28);
            color: #0f766e;
            box-shadow: none;
        }

        .btn.primary:hover {
            background: #0f766e;
            border-color: #0f766e;
            color: #ffffff;
        }

        .btn.danger {
            background: rgba(239, 68, 68, .10);
            border-color: rgba(239, 68, 68, .28);
            color: #b91c1c;
        }

        .btn.danger:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
        }

        .btn:disabled {
            opacity: 0.4;
            box-shadow: none;
            transform: none;
            cursor: not-allowed;
        }

        .table-actions {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
            min-width: 0;
        }

        .table-actions .btn,
        .table-actions .action-form,
        td > .btn {
            flex: 0 0 auto;
            white-space: nowrap;
            overflow-wrap: normal;
        }

        .table-actions .btn.small {
            min-height: 32px;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
        }

        td:last-child {
            width: 1%;
            white-space: nowrap;
        }

        .form-footer {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 4px;
            padding-top: 6px;
        }

        .form-footer .actions { justify-content: flex-end; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.25;
            white-space: normal;
            text-align: left;
        }

        .badge:empty { display: none; }

        .badge.status-concluido {
            border-color: #bbf7d0;
            background: rgba(220, 252, 231, .75);
            color: #166534;
        }

        .badge.status-aberto,
        .badge.danger {
            border-color: #fecaca;
            background: rgba(254, 226, 226, .78);
            color: #991b1b;
        }

        .badge.warning {
            border-color: #fde68a;
            background: rgba(254, 243, 199, .78);
            color: #92400e;
        }

        .badge.status-prazo {
            border-color: var(--border);
            background: #f8fafc;
            color: #4b5563;
        }

        .badge.alert-status-pendente,
        .badge.status-com-pendencia,
        .badge.status-com_pendencia {
            border-color: #fecaca;
            background: rgba(254, 226, 226, .78);
            color: #991b1b;
        }

        .badge.alert-status-em-analise,
        .badge.alert-status-em_analise {
            border-color: #fde68a;
            background: rgba(254, 243, 199, .78);
            color: #92400e;
        }

        .badge.alert-status-resolvido,
        .badge.status-conforme {
            border-color: #bbf7d0;
            background: rgba(220, 252, 231, .75);
            color: #166534;
        }

        .badge.alert-status-falso-positivo,
        .badge.alert-status-falso_positivo {
            border-color: #d1d5db;
            background: #f8fafc;
            color: #4b5563;
        }

        .alert-card-status-pendente {
            border-color: #fca5a5 !important;
            background: linear-gradient(90deg, rgba(254, 226, 226, .92), rgba(255, 255, 255, .96)) !important;
            box-shadow: inset 4px 0 0 #ef4444;
        }

        .alert-card-status-em-analise,
        .alert-card-status-em_analise {
            border-color: #fcd34d !important;
            background: linear-gradient(90deg, rgba(254, 243, 199, .92), rgba(255, 255, 255, .96)) !important;
            box-shadow: inset 4px 0 0 #f59e0b;
        }

        .alert-card-status-resolvido {
            border-color: #86efac !important;
            background: linear-gradient(90deg, rgba(220, 252, 231, .92), rgba(255, 255, 255, .96)) !important;
            box-shadow: inset 4px 0 0 #22c55e;
        }

        .alert-card-status-falso-positivo,
        .alert-card-status-falso_positivo {
            border-color: #cbd5e1 !important;
            background: linear-gradient(90deg, rgba(241, 245, 249, .95), rgba(255, 255, 255, .96)) !important;
            box-shadow: inset 4px 0 0 #64748b;
        }

        .alert-table tr[class*="alert-card-status"] td {
            background: transparent;
        }

        .alert-table tr.alert-card-status-pendente td {
            background: rgba(254, 226, 226, .92);
        }

        .alert-table tr.alert-card-status-em-analise td,
        .alert-table tr.alert-card-status-em_analise td {
            background: rgba(254, 243, 199, .92);
        }

        .alert-table tr.alert-card-status-resolvido td {
            background: rgba(220, 252, 231, .92);
        }

        .alert-table tr.alert-card-status-falso-positivo td,
        .alert-table tr.alert-card-status-falso_positivo td {
            background: rgba(241, 245, 249, .95);
        }

        .alert-table tr[class*="alert-card-status"] td:first-child {
            box-shadow: inset 4px 0 0 currentColor;
        }

        .alert-table tr.alert-card-status-pendente td:first-child {
            color: #dc2626;
        }

        .alert-table tr.alert-card-status-em-analise td:first-child,
        .alert-table tr.alert-card-status-em_analise td:first-child {
            color: #d97706;
        }

        .alert-table tr.alert-card-status-resolvido td:first-child {
            color: #16a34a;
        }

        .alert-table tr.alert-card-status-falso-positivo td:first-child,
        .alert-table tr.alert-card-status-falso_positivo td:first-child {
            color: #475569;
        }

        .info-list,
        .metric-list {
            display: grid;
            gap: 6px;
        }

        .status-line {
            display: grid;
            grid-template-columns: 9px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            min-width: 0;
            color: var(--text);
            font-size: 13px;
            line-height: 1.35;
        }

        .status-line span {
            overflow-wrap: anywhere;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 99px;
            background: #94a3b8;
        }

        .status-dot.ok,
        .status-dot.status-conforme,
        .status-dot.status-concluido,
        .status-dot.alert-status-resolvido {
            background: #14b8a6;
        }

        .status-dot.warning,
        .status-dot.alert-status-em-analise,
        .status-dot.alert-status-em_analise {
            background: #f59e0b;
        }

        .status-dot.danger,
        .status-dot.status-aberto,
        .status-dot.alert-status-pendente,
        .status-dot.status-com-pendencia,
        .status-dot.status-com_pendencia {
            background: #ef4444;
        }

        .metric-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: baseline;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(226, 232, 240, .85);
            color: var(--text-dim);
            font-size: 13px;
            line-height: 1.35;
        }

        .metric-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .metric-row strong {
            color: var(--text);
            font-weight: 700;
        }

        .notice,
        .errors {
            position: relative;
            margin-bottom: 16px;
            padding: 14px 16px 14px 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: #ffffff;
            color: var(--text);
            font-weight: 600;
            box-shadow: 0 8px 22px rgba(16, 19, 26, 0.045);
        }

        .notice::before,
        .errors::before {
            content: "";
            position: absolute;
            left: 8px;
            top: 14px;
            bottom: 14px;
            width: 3px;
            border-radius: 99px;
        }

        .notice {
            border-color: rgba(20, 184, 166, .28);
        }

        .notice.danger,
        .errors {
            border-color: rgba(239, 68, 68, .28);
        }

        .notice::before {
            background: #14b8a6;
        }

        .notice.danger::before,
        .errors::before {
            background: #ef4444;
        }

        .system-confirm-backdrop {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(8, 10, 18, 0.58);
        }

        .system-confirm-backdrop[hidden] {
            display: none;
        }

        .system-confirm-box {
            width: min(100%, 440px);
            display: grid;
            gap: 14px;
            padding: 22px;
            border: 1px solid rgba(16, 19, 26, 0.10);
            border-radius: var(--radius-lg);
            background: #ffffff;
            box-shadow: 0 30px 80px rgba(8, 10, 18, 0.28);
        }

        .system-confirm-box h2 {
            margin: 0;
        }

        .system-confirm-box p {
            margin: 0;
        }

        .pagination,
        nav[role="navigation"] {
            margin-top: 16px;
            color: var(--text-dim);
        }

        nav[role="navigation"] a,
        nav[role="navigation"] span {
            color: var(--text-dim);
        }

        nav[role="navigation"] svg {
            width: 18px;
            height: 18px;
        }

        @media (max-width: 1220px) {
            .admin-header {
                grid-template-columns: auto minmax(0, 1fr);
                align-items: start;
            }

            .nav {
                grid-column: 1 / -1;
                order: 3;
            }

            .side-user {
                justify-self: end;
            }

            .dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-panel { grid-column: span 2; }
            .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .form-row { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 780px) {
            body {
                font-size: 14px;
            }

            .main {
                padding: 12px;
            }

            .admin-header {
                position: sticky;
                grid-template-columns: minmax(0, 1fr);
                gap: 8px;
                min-height: auto;
                padding: 8px 10px;
            }

            .brand {
                min-height: 36px;
                font-size: 15px;
            }

            .brand img {
                width: 58px;
                height: 36px;
            }

            .nav {
                display: flex;
                gap: 6px;
                margin: 0;
                padding: 0;
                overflow-x: auto;
            }

            .nav a,
            .logout {
                width: auto;
                min-height: 34px;
                padding: 7px 10px;
                border-radius: var(--radius-sm);
                justify-content: center;
                text-align: center;
                white-space: nowrap;
            }

            .nav form {
                width: auto;
                flex: 0 0 auto;
            }

            .side-user {
                display: grid;
                justify-self: stretch;
                max-width: none;
            }

            .grid-2,
            .grid-4,
            .dashboard-grid,
            .filter-grid,
            .form-row { grid-template-columns: 1fr; }

            .top-filter-form {
                flex-basis: auto;
                min-width: 0;
                width: auto;
                justify-content: flex-start;
            }

            .top-filter-form input,
            .top-filter-form select,
            .top-filter-form input[type="date"],
            .top-filter-form input[type="search"],
            .top-filter-form input[name="busca"],
            .top-filter-form input[name="q"] {
                width: 118px;
            }

            .top-filter-form input[type="search"],
            .top-filter-form input[name="busca"],
            .top-filter-form input[name="q"] {
                width: 160px;
            }

            .top-filter-actions {
                justify-content: flex-end;
            }

            .filter-panel { grid-column: auto; }
            .top,
            .section-head {
                align-items: stretch;
                flex-direction: column;
                gap: 12px;
            }

            .top.top-filter {
                align-items: center;
                flex-direction: row;
                flex-wrap: nowrap;
            }

            .main > .top {
                padding: 16px;
                border-radius: var(--radius-md);
            }

            h1 { font-size: 23px; }
            h2 { font-size: 17px; }

            .panel,
            .card {
                padding: 14px;
                border-radius: var(--radius-md);
            }

            .card {
                min-height: 104px;
            }

            .card strong {
                font-size: 30px;
            }

            .actions,
            .form-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .table-actions {
                align-items: center;
                flex-direction: row;
            }

            .actions.end {
                justify-content: stretch;
            }

            .actions .btn,
            .actions .action-form,
            .actions form,
            .form-footer .btn,
            .form-footer .action-form {
                width: 100%;
            }

            .table-actions .btn,
            .table-actions .action-form,
            .table-actions form,
            .table-wrap td > .btn {
                width: auto;
                min-width: max-content;
            }

            .action-form {
                display: flex;
            }

            .btn {
                width: 100%;
                min-height: 42px;
                white-space: normal;
            }

            .btn.small {
                min-height: 38px;
            }

            .compact-filter {
                padding: 12px;
            }

            th,
            td {
                padding: 11px 10px;
                font-size: 13px;
            }

            .table-wrap {
                margin-inline: -2px;
                border-radius: var(--radius-md);
            }
        }

        @media (max-width: 520px) {
            .main {
                padding: 8px;
            }

            .nav {
                margin: 0;
                padding-inline: 0;
                gap: 5px;
                overflow-x: auto;
                overflow-y: visible;
                scrollbar-width: thin;
            }

            .nav-menu {
                position: static;
            }

            .nav-dropdown {
                position: fixed;
                top: 56px;
                left: 10px;
                right: 10px;
                min-width: 0;
            }

            .top-filter-form {
                flex: 1 1 auto;
                width: 100%;
                justify-content: flex-start;
            }

            .top-filter-actions {
                display: flex;
            }

            .main > .top,
            .panel,
            .card {
                padding: 12px;
            }

            input,
            select,
            textarea {
                min-height: 44px;
                font-size: 16px;
            }

            .top-filter-form input,
            .top-filter-form select {
                width: 100%;
                min-height: 30px;
                font-size: 12px;
                padding: 4px 8px;
            }

            .badge {
                max-width: 100%;
                white-space: normal;
                line-height: 1.25;
            }
        }

        /* TailAdmin-inspired visual standardization: visual layer only. */
        :root {
            --bg: #f9fafb;
            --panel: #ffffff;
            --panel-strong: #ffffff;
            --panel-soft: #f8fafc;
            --border: #e4e7ec;
            --border-strong: #d0d5dd;
            --text: #101828;
            --text-dim: #475467;
            --text-faint: #98a2b3;
            --brand: #14b8a6;
            --brand-strong: #0f766e;
            --brand-soft: #ecfdf3;
            --info: #0ba5ec;
            --info-soft: #f0f9ff;
            --warning: #f79009;
            --warning-soft: #fffaeb;
            --danger: #f04438;
            --danger-soft: #fef3f2;
            --success: #12b76a;
            --success-soft: #ecfdf3;
            --radius-lg: 8px;
            --radius-md: 8px;
            --radius-sm: 6px;
            --shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
            --shadow-md: 0 4px 8px -2px rgba(16, 24, 40, 0.10), 0 2px 4px -2px rgba(16, 24, 40, 0.06);
            --focus-ring: 0 0 0 4px rgba(20, 184, 166, 0.14);
            --accent: #14b8a6;
            --accent-soft: #ecfdf3;
            --glow: 0 10px 20px rgba(20, 184, 166, 0.12);
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.45;
        }

        h1 {
            font-size: 22px;
            line-height: 1.25;
            color: var(--text);
            font-weight: 650;
        }

        h2 {
            font-size: 16px;
            line-height: 1.3;
            margin-bottom: 10px;
            color: var(--text);
            font-weight: 650;
        }

        h3 {
            font-size: 14px;
            line-height: 1.35;
            color: var(--text);
            font-weight: 650;
        }

        p,
        small {
            color: var(--text-dim);
        }

        .main {
            padding: 16px;
            max-width: 1580px;
            margin: 0 auto;
            width: 100%;
        }

        .admin-header {
            min-height: 54px;
            padding: 7px 14px;
            background: #101828;
            border-bottom-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 10px rgba(16, 24, 40, 0.16);
        }

        .brand img {
            width: 58px;
            height: 34px;
        }

        .nav a,
        .nav-menu summary,
        .logout {
            min-height: 34px;
            padding: 7px 10px;
            border-radius: 7px;
            border-color: transparent;
            background: transparent;
            color: rgba(255, 255, 255, 0.72);
            font-size: 13px;
            font-weight: 600;
        }

        .nav a:hover,
        .nav a.active,
        .nav-menu.is-active summary,
        .nav-menu[open] summary,
        .nav-menu summary:hover,
        .logout:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .nav-dropdown {
            min-width: 196px;
            padding: 8px;
            border-radius: 8px;
            background: #ffffff;
            border-color: var(--border);
            box-shadow: var(--shadow-md);
        }

        .nav-dropdown a {
            min-height: 34px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--text-dim);
            font-weight: 600;
        }

        .nav-dropdown a:hover,
        .nav-dropdown a.active {
            background: var(--brand-soft);
            color: var(--brand-strong);
        }

        .side-user {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.84);
        }

        .main > .top,
        .panel,
        .card,
        .checklist-card,
        .form-row,
        .notice,
        .errors {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .main > .top {
            padding: 14px 16px;
            margin-bottom: 14px;
            align-items: flex-start;
        }

        .top.top-filter {
            gap: 12px;
        }

        .title-copy {
            gap: 6px;
        }

        .title-copy p,
        .section-head p {
            margin-top: 2px;
            font-size: 13px;
        }

        .panel {
            padding: 16px;
        }

        .card {
            min-height: 92px;
            padding: 16px;
            justify-content: flex-start;
            gap: 8px;
        }

        .card strong {
            font-size: 26px;
            color: var(--text);
            font-weight: 700;
        }

        .card span {
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 500;
        }

        .dashboard-grid,
        .grid,
        .stack {
            gap: 14px;
        }

        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            margin-bottom: 14px;
        }

        .section-head {
            align-items: center;
            gap: 12px;
            padding-bottom: 2px;
        }

        .top-filter-form,
        .compact-filter .filter-grid,
        .filter-grid,
        .reports-filter-grid {
            gap: 8px;
        }

        .top-filter-form {
            align-items: end;
            justify-content: flex-end;
        }

        .top-filter-form label,
        .compact-filter label,
        .reports-filter-grid label,
        label {
            color: #344054;
            font-size: 12px;
            font-weight: 600;
            gap: 5px;
        }

        .top-filter-form input,
        .top-filter-form select {
            width: 128px;
            min-height: 34px;
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 13px;
        }

        .top-filter-form input[type="date"] {
            width: 132px;
        }

        .top-filter-form input[type="search"],
        .top-filter-form input[name="busca"],
        .top-filter-form input[name="q"],
        .top-filter-form input[name="maquina"] {
            width: 180px;
        }

        .compact-filter {
            padding: 12px;
            margin-bottom: 14px;
        }

        .compact-filter .filter-grid {
            grid-template-columns: repeat(auto-fit, minmax(142px, 1fr));
        }

        input,
        select,
        textarea,
        .compact-filter input,
        .compact-filter select,
        .reports-filter-grid input,
        .reports-filter-grid select {
            border-color: var(--border-strong);
            border-radius: 6px;
            background: #ffffff;
            color: var(--text);
            min-height: 38px;
            padding: 8px 10px;
            font-size: 13px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
        }

        textarea {
            min-height: 96px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--brand);
            box-shadow: var(--focus-ring);
        }

        .btn,
        .logout {
            min-height: 38px;
            padding: 8px 12px;
            border-radius: 6px;
            border-color: var(--border-strong);
            background: #ffffff;
            color: #344054;
            font-size: 13px;
            font-weight: 650;
            line-height: 1.2;
            box-shadow: var(--shadow);
            white-space: nowrap;
        }

        .btn.small,
        .top-filter-actions .btn,
        .table-actions .btn.small {
            min-height: 32px;
            padding: 7px 10px;
            font-size: 12px;
        }

        .btn:hover {
            background: #f9fafb;
            border-color: #98a2b3;
            color: var(--text);
        }

        .btn.primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08);
        }

        .btn.primary:hover {
            background: var(--brand-strong);
            border-color: var(--brand-strong);
            color: #ffffff;
        }

        .btn.danger {
            background: #ffffff;
            border-color: #fecdca;
            color: #b42318;
        }

        .btn.danger:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: #ffffff;
        }

        .actions {
            gap: 8px;
        }

        .table-actions {
            gap: 6px;
            justify-content: flex-start;
        }

        .table-wrap {
            border-radius: 8px;
        }

        table {
            border-radius: 8px;
            min-width: 720px;
        }

        th,
        td {
            padding: 11px 13px;
            border-bottom-color: var(--border);
            font-size: 13px;
        }

        th {
            background: #f9fafb;
            color: #667085;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        tbody tr:hover td {
            background: #fcfcfd;
        }

        td strong,
        td a:not(.btn) {
            color: var(--text);
            font-weight: 650;
        }

        .badge {
            min-height: 24px;
            padding: 3px 8px;
            border-radius: 6px;
            border-color: var(--border);
            background: #f9fafb;
            color: #475467;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
            white-space: nowrap;
        }

        .badge.status-concluido,
        .badge.alert-status-resolvido,
        .badge.status-conforme {
            border-color: #a6f4c5;
            background: var(--success-soft);
            color: #027a48;
        }

        .badge.status-aberto,
        .badge.danger,
        .badge.alert-status-pendente,
        .badge.status-com-pendencia,
        .badge.status-com_pendencia {
            border-color: #fecdca;
            background: var(--danger-soft);
            color: #b42318;
        }

        .badge.warning,
        .badge.alert-status-em-analise,
        .badge.alert-status-em_analise {
            border-color: #fedf89;
            background: var(--warning-soft);
            color: #b54708;
        }

        .badge.status-prazo,
        .badge.alert-status-falso-positivo,
        .badge.alert-status-falso_positivo {
            border-color: var(--border);
            background: #f9fafb;
            color: #475467;
        }

        .status-dot,
        .machine-dot {
            width: 8px;
            height: 8px;
        }

        .machine-status-line,
        .status-line {
            color: var(--text-dim);
            font-size: 13px;
        }

        .machine-actions .btn.small,
        .machine-meta .btn.small {
            min-height: 32px;
            border-radius: 6px;
        }

        .machine-actions .btn.small.primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #ffffff;
        }

        .notice {
            border-color: #b9e6fe;
            background: var(--info-soft);
            color: #026aa2;
            padding: 12px 14px;
        }

        .errors {
            border-color: #fecdca;
            background: var(--danger-soft);
            color: #b42318;
            padding: 12px 14px;
        }

        .tabs {
            padding: 4px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .tabs a {
            min-height: 32px;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 12px;
        }

        .tabs a.active,
        .tabs a:hover {
            background: var(--brand);
            color: #ffffff;
        }

        .system-confirm-box {
            border-radius: 8px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 980px) {
            .main {
                padding: 12px;
            }

            .admin-header {
                grid-template-columns: minmax(0, 1fr) auto;
            }

            .nav {
                grid-column: 1 / -1;
                overflow-x: auto;
                padding-bottom: 3px;
            }

            .side-user {
                justify-self: end;
            }

            .main > .top,
            .top.top-filter {
                align-items: stretch;
            }

            .top-filter-form {
                justify-content: flex-start;
            }
        }

        @media (max-width: 720px) {
            h1 {
                font-size: 20px;
            }

            .main > .top,
            .panel,
            .card,
            .checklist-card {
                padding: 12px;
            }

            .top-filter-form,
            .top-filter-form label,
            .top-filter-form input,
            .top-filter-form select,
            .top-filter-form input[type="date"],
            .top-filter-form input[type="search"],
            .top-filter-form input[name="busca"],
            .top-filter-form input[name="q"],
            .top-filter-form input[name="maquina"] {
                width: 100%;
            }

            .top-filter-actions,
            .top-filter-actions .btn,
            .actions .btn,
            .form-footer .btn {
                width: 100%;
            }

            .dashboard-grid,
            .grid-2,
            .grid-4 {
                grid-template-columns: 1fr;
            }

            th,
            td {
                padding: 10px;
            }
        }

        @media (max-width: 720px) {
            .main > .top.top-filter {
                display: grid;
                gap: 8px;
                overflow: hidden;
            }

            .top.top-filter .title-copy {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                min-width: 0;
            }

            .top.top-filter .title-copy .btn {
                flex: 0 0 auto;
                width: auto;
                min-height: 32px;
                padding: 7px 10px;
                white-space: nowrap;
            }

            .top-filter-form {
                display: flex;
                flex-wrap: nowrap;
                align-items: end;
                justify-content: flex-start;
                gap: 6px;
                width: 100%;
                margin: 0;
                padding: 1px 2px 8px;
                overflow-x: auto;
                overflow-y: hidden;
                scrollbar-width: thin;
                -webkit-overflow-scrolling: touch;
                scroll-snap-type: x proximity;
            }

            .top-filter-form label {
                flex: 0 0 auto;
                width: auto !important;
                min-width: 106px;
                max-width: 174px;
                scroll-snap-align: start;
                font-size: 10px;
                line-height: 1.15;
            }

            .top-filter-form input,
            .top-filter-form select,
            .top-filter-form input[type="date"],
            .top-filter-form input[type="search"],
            .top-filter-form input[name="busca"],
            .top-filter-form input[name="q"],
            .top-filter-form input[name="maquina"] {
                width: 128px !important;
                min-height: 32px;
                padding: 5px 8px;
                font-size: 12px;
            }

            .top-filter-form input[type="date"] {
                width: 126px !important;
            }

            .top-filter-form input[type="search"],
            .top-filter-form input[name="busca"],
            .top-filter-form input[name="q"],
            .top-filter-form input[name="maquina"] {
                width: 164px !important;
            }

            .top-filter-actions {
                flex: 0 0 auto;
                width: auto !important;
                display: flex;
                align-items: end;
                gap: 5px;
                padding-bottom: 0;
            }

            .top-filter-actions .btn {
                width: auto !important;
                min-height: 32px;
                padding: 6px 9px;
                white-space: nowrap;
            }

            .compact-filter,
            .reports-filter-card {
                overflow: hidden;
                padding: 12px;
            }

            .compact-filter .filter-grid,
            .filter-panel .filter-grid,
            .reports-filter-grid {
                display: flex !important;
                flex-wrap: nowrap;
                gap: 8px;
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 8px;
                scrollbar-width: thin;
                -webkit-overflow-scrolling: touch;
                scroll-snap-type: x proximity;
            }

            .compact-filter label,
            .filter-panel .filter-grid label,
            .reports-filter-grid label {
                flex: 0 0 auto;
                width: auto !important;
                min-width: 132px;
                max-width: 186px;
                scroll-snap-align: start;
                font-size: 11px;
            }

            .compact-filter input,
            .compact-filter select,
            .filter-panel .filter-grid input,
            .filter-panel .filter-grid select,
            .reports-filter-grid input,
            .reports-filter-grid select {
                width: 150px !important;
                min-height: 34px;
                padding: 6px 9px;
                font-size: 12px;
            }

            .reports-actions {
                flex-wrap: nowrap;
                justify-content: flex-start;
                gap: 6px;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .reports-actions .btn {
                flex: 0 0 auto;
                width: auto !important;
                min-height: 34px;
                padding: 7px 10px;
                white-space: nowrap;
            }
        }

    </style>
</head>
<body>
<div class="shell">
    <header class="admin-header">
        <a class="brand" href="{{ route('admin.inicio') }}">
            <img src="{{ asset('assets/logo.png') }}?v=20260807-transp" alt="Empresa Demo">
            Gestão de Máquinas
        </a>
        <nav class="nav">
            @php
                $navEmpresaId = session('empresa_ativa_id') ?: auth('admin')->user()?->empresa_id;
                $usuarioAdmin = auth('admin')->user();
                $isComercial = (bool) $usuarioAdmin?->isComercial();
                $navRastreamento = $navEmpresaId
                    ? \App\Models\Empresa::query()->whereKey($navEmpresaId)->value('modulo_rastreamento')
                    : false;
                $financeiroAtivo = request()->routeIs('admin.clientes.*') || request()->routeIs('admin.orcamentos.*') || request()->routeIs('admin.contratos.*');
                $controleAtivo = request()->routeIs('admin.alertas.*') || request()->routeIs('admin.historico.*') || request()->routeIs('admin.relatorios.*') || request()->routeIs('admin.formularios-modelo.*');
                $rastreamentoAtivo = request()->routeIs('admin.rastreamento.*');
            @endphp
            <a class="{{ request()->routeIs('admin.inicio') ? 'active' : '' }}" href="{{ route('admin.inicio') }}">Início</a>
            @unless($isComercial)
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            @endunless
            <a class="{{ request()->routeIs('admin.maquinas.*') ? 'active' : '' }}" href="{{ route('admin.maquinas.index') }}">Máquinas</a>

            <details class="nav-menu {{ $financeiroAtivo ? 'is-active' : '' }}">
                <summary>Financeiro</summary>
                <div class="nav-dropdown">
                    <a class="{{ request()->routeIs('admin.clientes.*') ? 'active' : '' }}" href="{{ route('admin.clientes.index') }}">Clientes</a>
                    <a class="{{ request()->routeIs('admin.orcamentos.*') ? 'active' : '' }}" href="{{ route('admin.orcamentos.index') }}">Orçamentos</a>
                    <a class="{{ request()->routeIs('admin.contratos.*') ? 'active' : '' }}" href="{{ route('admin.contratos.index') }}">Contratos</a>
                </div>
            </details>

            @unless($isComercial)
                <details class="nav-menu {{ $controleAtivo ? 'is-active' : '' }}">
                    <summary>Controle</summary>
                    <div class="nav-dropdown">
                        <a class="{{ request()->routeIs('admin.alertas.*') ? 'active' : '' }}" href="{{ route('admin.alertas.index') }}">Alertas</a>
                        <a class="{{ request()->routeIs('admin.historico.*') ? 'active' : '' }}" href="{{ route('admin.historico.index') }}">Histórico</a>
                        <a class="{{ request()->routeIs('admin.relatorios.*') ? 'active' : '' }}" href="{{ route('admin.relatorios.index') }}">Relatórios</a>
                        <a class="{{ request()->routeIs('admin.formularios-modelo.*') ? 'active' : '' }}" href="{{ route('admin.formularios-modelo.index') }}">Checklists</a>
                    </div>
                </details>
            @endunless

            @if($navRastreamento && ! $isComercial)
                <details class="nav-menu {{ $rastreamentoAtivo ? 'is-active' : '' }}">
                    <summary>Rastreamento</summary>
                    <div class="nav-dropdown">
                        <a class="{{ request()->routeIs('admin.rastreamento.*') ? 'active' : '' }}" href="{{ route('admin.rastreamento.mapa') }}">Mapa</a>
                    </div>
                </details>
            @endif

            @if(auth('admin')->user()?->is_superadmin)
                <details class="nav-menu {{ request()->routeIs('admin.sistema.*') ? 'is-active' : '' }}">
                    <summary>Sistema</summary>
                    <div class="nav-dropdown nav-dropdown-right">
                        <a class="{{ request()->routeIs('admin.sistema.*') ? 'active' : '' }}" href="{{ route('admin.sistema.index') }}">Painel</a>
                        <a href="{{ route('admin.sistema.logs') }}">Logs</a>
                        <a href="{{ route('admin.sistema.backup') }}">Backup</a>
                    </div>
                </details>
            @endif

            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout">Sair</button>
            </form>
        </nav>
        <div class="side-user">
            <strong>{{ auth('admin')->user()?->nome ?? 'Administrador' }}</strong>
        </div>
    </header>
    <main class="main">
        @if (session('superadmin_id'))
            <div class="notice actions spread">
                <span>Você está acessando como {{ auth('admin')->user()?->nome }}.</span>
                <form method="post" action="{{ route('admin.sistema.superadmin.voltar') }}">
                    @csrf
                    @method('delete')
                    <button class="btn small" type="submit">Voltar ao superadmin</button>
                </form>
            </div>
        @endif
        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>
<div class="system-confirm-backdrop" data-system-confirm-modal hidden>
    <div class="system-confirm-box" role="dialog" aria-modal="true" aria-labelledby="system-confirm-title">
        <div>
            <h2 id="system-confirm-title">Confirmar ação</h2>
            <p data-system-confirm-message>Deseja continuar?</p>
        </div>
        <div class="actions end">
            <button class="btn" type="button" data-system-confirm-cancel>Cancelar</button>
            <button class="btn danger" type="button" data-system-confirm-action>Confirmar</button>
        </div>
    </div>
</div>
<script>
(() => {
    const navMenus = Array.from(document.querySelectorAll('.nav-menu'));

    const closeNavMenus = (except = null) => {
        navMenus.forEach((menu) => {
            if (menu !== except) {
                menu.removeAttribute('open');
            }
        });
    };

    navMenus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (menu.open) {
                closeNavMenus(menu);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.nav-menu')) {
            closeNavMenus();
            return;
        }

        if (event.target.closest('.nav-dropdown a')) {
            closeNavMenus();
        }
    });

    const modal = document.querySelector('[data-system-confirm-modal]');
    const message = modal?.querySelector('[data-system-confirm-message]');
    const cancelButton = modal?.querySelector('[data-system-confirm-cancel]');
    const confirmButton = modal?.querySelector('[data-system-confirm-action]');
    let pendingForm = null;
    let pendingAction = null;
    let allowNavigation = false;
    const dirtyForms = new Set();

    if (!modal || !message || !cancelButton || !confirmButton) {
        return;
    }

    const closeModal = () => {
        modal.hidden = true;
        pendingForm = null;
        pendingAction = null;
        confirmButton.classList.add('danger');
        confirmButton.classList.remove('primary');
        confirmButton.textContent = 'Confirmar';
    };

    const showSystemConfirm = (text, actionText, action, danger = true) => {
        pendingAction = action;
        message.textContent = text || 'Deseja continuar?';
        confirmButton.textContent = actionText || 'Confirmar';
        confirmButton.classList.toggle('danger', danger);
        confirmButton.classList.toggle('primary', !danger);
        modal.hidden = false;
        cancelButton.focus();
    };

    const isTrackedForm = (form) => {
        if (!form || form.dataset.unsavedIgnore === '1') {
            return false;
        }

        if (form.dataset.unsavedWatch !== '1') {
            return false;
        }

        const method = (form.getAttribute('method') || 'get').toLowerCase();

        if (method === 'get') {
            return false;
        }

        return Boolean(form.querySelector('input:not([type="hidden"]), textarea, select'));
    };

    document.querySelectorAll('form').forEach((form) => {
        if (!isTrackedForm(form)) {
            return;
        }

        form.addEventListener('input', () => dirtyForms.add(form));
        form.addEventListener('change', () => dirtyForms.add(form));
    });

    const hasUnsavedChanges = () => dirtyForms.size > 0;

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');

        if (!form || form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingForm = form;
        showSystemConfirm(form.dataset.confirm || 'Deseja continuar?', form.dataset.confirmAction || 'Confirmar', () => {
            const form = pendingForm;

            if (!form) {
                return;
            }

            allowNavigation = true;
            dirtyForms.delete(form);
            form.dataset.confirmed = '1';
            modal.hidden = true;
            form.requestSubmit();
        });
    });

    document.addEventListener('submit', (event) => {
        if (!event.defaultPrevented && isTrackedForm(event.target)) {
            allowNavigation = true;
            dirtyForms.delete(event.target);
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link || !hasUnsavedChanges() || allowNavigation) {
            return;
        }

        if (link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const href = link.getAttribute('href') || '';

        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        event.preventDefault();
        showSystemConfirm(
            'Existem alterações não salvas. Deseja sair sem salvar?',
            'Sair sem salvar',
            () => {
                allowNavigation = true;
                window.location.href = link.href;
            }
        );
    });

    window.addEventListener('beforeunload', (event) => {
        if (!hasUnsavedChanges() || allowNavigation) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    cancelButton.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    confirmButton.addEventListener('click', () => {
        if (!pendingAction) {
            return;
        }

        const action = pendingAction;
        pendingAction = null;
        action();
    });
})();
</script>
</body>
</html>
