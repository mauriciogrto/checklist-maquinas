<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $orcamento->numero ?: 'Orçamento #'.$orcamento->id }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111827;
            background: #eef2f6;
            font: 14px/1.5 Arial, Helvetica, sans-serif;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 18mm;
            background: #fff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 18px;
        }
        .brand {
            display: grid;
            gap: 4px;
        }
        .brand strong {
            font-size: 22px;
            color: #0f766e;
        }
        h1 {
            margin: 26px 0 8px;
            font-size: 28px;
            line-height: 1.15;
        }
        h2 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #0f766e;
        }
        p { margin: 0; color: #374151; }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #ccfbf1;
            color: #0f766e;
            font-weight: 700;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }
        .box {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 14px;
            break-inside: avoid;
        }
        .box span {
            display: block;
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .box strong {
            display: block;
            color: #111827;
            font-size: 15px;
        }
        .section {
            margin-top: 22px;
            break-inside: avoid;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th,
        td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
        }
        .total {
            font-size: 22px;
            color: #0f766e;
        }
        .footer {
            margin-top: 30px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 12px;
        }
        .print-actions {
            position: sticky;
            top: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: #0b1220;
        }
        .print-actions button,
        .print-actions a {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            background: #0f766e;
            color: #fff;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Salvar / imprimir PDF</button>
        <a href="{{ route('admin.orcamentos.show', $orcamento) }}">Voltar</a>
    </div>
    <main class="page">
        <header class="header">
            <div class="brand">
                <strong>Gestão de Máquinas</strong>
                <span>Sistema de Checklist e Gestão de Máquinas</span>
            </div>
            <div>
                <span class="badge">{{ ucfirst($orcamento->status) }}</span>
                <p>{{ $orcamento->numero ?: 'Orçamento #'.$orcamento->id }}</p>
                <p>{{ $orcamento->data_orcamento?->format('d/m/Y') }}</p>
            </div>
        </header>

        <h1>{{ $orcamento->numero ?: 'Orçamento #'.$orcamento->id }}</h1>
        <p>Proposta comercial para locação e acompanhamento operacional de equipamento.</p>

        <section class="grid">
            <div class="box">
                <span>Cliente</span>
                <strong>{{ $orcamento->cliente?->nome ?? 'Cliente não informado' }}</strong>
                <p>{{ $orcamento->cliente?->documento }}</p>
                <p>{{ $orcamento->cliente?->endereco }}</p>
            </div>
            <div class="box">
                <span>Contato</span>
                <strong>{{ $orcamento->contato_nome ?: 'Não informado' }}</strong>
                <p>{{ $orcamento->contato_telefone }}</p>
                <p>{{ $orcamento->contato_email }}</p>
            </div>
        </section>

        <section class="section">
            <h2>Escopo da proposta</h2>
            <div class="box">
                <p>{{ $orcamento->descricao ?: 'Locação de máquinas e equipamentos conforme as condições comerciais abaixo.' }}</p>
            </div>
        </section>

        <section class="section">
            <h2>Período e itens</h2>
            <table>
                @if($orcamento->itens->isNotEmpty())
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Máquina</th>
                            <th>Período</th>
                            <th>Valor diário (R$)</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orcamento->itens as $item)
                            <tr>
                                <td>{{ $item->descricao ?: 'Item do orçamento' }}</td>
                                <td>{{ $item->maquina?->nome ?? 'Não informada' }}</td>
                                <td>{{ $item->periodoFormatado() }}</td>
                                <td>R$ {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}</td>
                                <td>R$ {{ number_format($item->valorTotal(), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @else
                <tbody>
                    <tr>
                        <th>Máquina</th>
                        <td>{{ $orcamento->maquina?->nome ?? 'Não informada' }}</td>
                    </tr>
                </tbody>
                @endif
            </table>
            <table>
                <tbody>
                    <tr>
                        <th>Dias somados</th>
                        <td>{{ $orcamento->tempo_contrato ?: 'Não informado' }}</td>
                    </tr>
                    <tr>
                        <th>Período previsto</th>
                        <td>{{ $orcamento->contrato_inicio?->format('d/m/Y') ?? 'sem início' }} a {{ $orcamento->contrato_fim?->format('d/m/Y') ?? 'sem término' }}</td>
                    </tr>
                    <tr>
                        <th>Validade da proposta</th>
                        <td>{{ $orcamento->validade?->format('d/m/Y') ?? 'Não informada' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Valores</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Itens</th>
                        <td>R$ {{ number_format($orcamento->valorItens(), 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Frete</th>
                        <td>R$ {{ number_format((float) $orcamento->valor_frete, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Desconto</th>
                        <td>{{ number_format((float) $orcamento->desconto_percentual, 2, ',', '.') }}%</td>
                    </tr>
                    <tr>
                        <th>Imposto</th>
                        <td>{{ number_format((float) $orcamento->imposto_percentual, 2, ',', '.') }}%</td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <td><strong class="total">R$ {{ number_format($orcamento->valorTotal(), 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Condições comerciais</h2>
            <div class="box">
                <p>
                    {{ $orcamento->condicoes_pagamento ?: 'Condições de pagamento a combinar.' }}
                    @if($orcamento->condicoes_pagamento === 'Boleto' && $orcamento->prazo_faturamento)
                        - {{ $orcamento->prazo_faturamento }}
                    @endif
                </p>
                @if($orcamento->observacoes)
                    <p style="margin-top:10px">{{ $orcamento->observacoes }}</p>
                @endif
            </div>
        </section>

        <footer class="footer">
            Gestão de Máquinas, Telemetria e Rastreamento.
        </footer>
    </main>
</body>
</html>
