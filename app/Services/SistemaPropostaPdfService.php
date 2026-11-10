<?php

namespace App\Services;

use App\Models\SistemaProposta;

class SistemaPropostaPdfService
{
    use PdfBranding;

    private const FOOTER = 'Gestão de Máquinas, Telemetria e Rastreamento';

    private array $lines = [];
    private float $y = 790;

    public function render(SistemaProposta|array $data): string
    {
        $data = $this->normalizeData($data);
        $this->lines = [];
        $this->y = 790;
        $this->loadBrandImage($this->brandLogoPath());
        $this->drawLetterhead(128, 238, 340, 250);

        $quantidade = (int) ($data['quantidade_rastreadores'] ?? 0);
        $valorSistema = (float) ($data['valor_sistema_mensal'] ?? 0);
        $valorComodato = (float) ($data['valor_comodato_mensal'] ?? 0);
        $valorRastreador = (float) ($data['valor_rastreador'] ?? 0);
        $valorTreinamento = (float) ($data['valor_treinamento'] ?? 0);
        $valorInstalacao = (float) ($data['valor_instalacao'] ?? 0);
        $valorDeslocamento = (float) ($data['valor_deslocamento'] ?? 0);
        $totalComodato = $quantidade * $valorComodato;
        $totalMensal = $valorSistema + $totalComodato;
        $totalImplantacao = $valorTreinamento + $valorInstalacao + $valorDeslocamento;
        $totalReferencial = $quantidade * $valorRastreador;
        $validade = now()->addDays((int) ($data['validade_dias'] ?? 10));

        $this->rect(42, 744, 511, 66, 0.92, 0.98, 0.96, true);
        $this->rect(42, 744, 511, 66, 0.74, 0.82, 0.84, false);
        if ($this->brandImage) {
            $this->drawBrandMark(58, 760, 96, 36, 1);
            $this->text('PROPOSTA COMERCIAL', 170, 788, 16, true);
            $this->text('Gestão de Máquinas + Telemetria e Rastreamento', 170, 768, 11, true);
        } else {
            $this->text('PROPOSTA COMERCIAL', 58, 788, 16, true);
            $this->text('Gestão de Máquinas + Telemetria e Rastreamento', 58, 768, 11, true);
        }
        $this->text('Emissão: '.now()->format('d/m/Y'), 420, 788, 9);
        $this->text('Validade: '.$validade->format('d/m/Y'), 420, 772, 9);
        $this->y = 716;

        $this->section('Cliente');
        $this->infoBox(42, $this->y - 58, 245, 58, 'Empresa', $data['cliente_nome'], $data['cliente_documento'] ?: 'Documento não informado');
        $contato = trim(implode(' | ', array_filter([
            $data['contato_nome'] ?? null,
            $data['contato_telefone'] ?? null,
            $data['contato_email'] ?? null,
        ])));
        $this->infoBox(308, $this->y - 58, 245, 58, 'Contato', $contato ?: 'Não informado', '');
        $this->y -= 78;

        $this->section('Escopo da solução');
        $this->paragraph('Implantação do Gestão de Máquinas para controle de máquinas, checklists, alertas, documentos, orçamentos, contratos, telemetria e rastreamento. A proposta considera uso do sistema em ambiente online, com rastreadores em comodato durante a vigência contratual.');

        $this->section('Investimento mensal');
        $this->tableHeader(['Item', 'Qtd.', 'Valor unitário', 'Total mensal'], [58, 312, 388, 484]);
        $this->tableRow(['Software Gestão de Máquinas', '1', $this->money($valorSistema), $this->money($valorSistema)], [58, 312, 388, 484]);
        $this->tableRow(['Comodato do rastreador', (string) $quantidade, $this->money($valorComodato), $this->money($totalComodato)], [58, 312, 388, 484]);
        $this->highlightTotal('TOTAL MENSAL ESTIMADO', $this->money($totalMensal));

        $this->section('Implantação e equipamentos');
        $this->tableHeader(['Item', 'Qtd.', 'Valor', 'Total'], [58, 312, 388, 484]);
        $this->tableRow(['Treinamento operacional', '1', $this->money($valorTreinamento), $this->money($valorTreinamento)], [58, 312, 388, 484]);
        $this->tableRow(['Instalação', '1', $this->money($valorInstalacao), $this->money($valorInstalacao)], [58, 312, 388, 484]);
        $this->tableRow(['Deslocamento', '1', $this->money($valorDeslocamento), $this->money($valorDeslocamento)], [58, 312, 388, 484]);
        $this->tableRow(['Valor referencial do rastreador', (string) $quantidade, $this->money($valorRastreador), $this->money($totalReferencial)], [58, 312, 388, 484]);
        $this->highlightTotal('TOTAL DE IMPLANTAÇÃO', $this->money($totalImplantacao));

        $this->section('Condições comerciais');
        $prazo = (int) ($data['prazo_contrato_meses'] ?? 12);
        $dia = (int) ($data['vencimento_mensal_dia'] ?? 10);
        $condicoes = $data['condicoes_pagamento'] ?: 'Boleto ou PIX';
        $this->bullet('Contrato mínimo sugerido de '.$prazo.' meses.');
        $this->bullet('Mensalidade com vencimento todo dia '.$dia.' de cada mês.');
        $this->bullet('Forma de pagamento: '.$condicoes.'.');
        $this->bullet('Rastreadores em comodato permanecem vinculados ao contrato e devem ser devolvidos ao final da contratação.');
        $this->bullet('Instalação, deslocamento, chip de dados e condições especiais podem ser bonificados ou ajustados conforme volume contratado.');

        if (! empty($data['observacoes'])) {
            $this->section('Observações');
            $this->paragraph($data['observacoes']);
        }

        $this->footer();

        return $this->buildPdf();
    }

    public function filename(SistemaProposta|array $data): string
    {
        $data = $this->normalizeData($data);
        $cliente = preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['cliente_nome'] ?? 'cliente');

        return strtolower(trim('proposta-'.$cliente, '-')).'.pdf';
    }

    private function normalizeData(SistemaProposta|array $data): array
    {
        if ($data instanceof SistemaProposta) {
            return $data->toArray();
        }

        return $data;
    }

    private function brandLogoPath(): ?string
    {
        foreach ([
            public_path('assets/logo.png'),
            public_path('assets/logo-header.png'),
            dirname(base_path()).'/public_html/sistema/assets/logo.png',
            dirname(base_path()).'/public_html/sistema/assets/logo-header.png',
            base_path('logo.png'),
        ] as $path) {
            if ($path && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function section(string $title): void
    {
        $this->ensureSpace(38);
        $this->text($title, 42, $this->y, 12, true);
        $this->line(42, $this->y - 5, 553, $this->y - 5);
        $this->y -= 22;
    }

    private function infoBox(float $x, float $y, float $w, float $h, string $label, string $value, string $detail): void
    {
        $this->rect($x, $y, $w, $h, 0.97, 0.98, 0.99, true);
        $this->rect($x, $y, $w, $h, 0.82, 0.86, 0.90, false);
        $this->text($label, $x + 10, $y + $h - 15, 8, true);
        $this->text($this->limit($value, 36), $x + 10, $y + $h - 31, 9, true);

        if ($detail !== '') {
            foreach (array_slice($this->wrap($detail, 36), 0, 2) as $index => $line) {
                $this->text($line, $x + 10, $y + $h - 43 - ($index * 9), 7);
            }
        }
    }

    private function tableHeader(array $labels, array $columns): void
    {
        $this->ensureSpace(38);
        $this->rect(42, $this->y - 18, 511, 20, 0.92, 0.98, 0.96, true);

        foreach ($labels as $index => $label) {
            $this->text($label, $columns[$index], $this->y - 12, 8, true);
        }

        $this->y -= 24;
    }

    private function tableRow(array $values, array $columns): void
    {
        $this->ensureSpace(30);
        $this->rect(42, $this->y - 20, 511, 22, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - 20, 511, 22, 0.88, 0.90, 0.93, false);

        foreach ($values as $index => $value) {
            $this->text($this->limit((string) $value, $index === 0 ? 42 : 18), $columns[$index], $this->y - 12, 8);
        }

        $this->y -= 23;
    }

    private function highlightTotal(string $label, string $value): void
    {
        $this->ensureSpace(40);
        $this->rect(42, $this->y - 24, 511, 30, 0.92, 0.98, 0.96, true);
        $this->rect(42, $this->y - 24, 511, 30, 0.74, 0.82, 0.84, false);
        $this->text($label, 58, $this->y - 14, 10, true);
        $this->text($value, 438, $this->y - 14, 12, true);
        $this->y -= 44;
    }

    private function paragraph(string $text): void
    {
        foreach ($this->wrap($text, 96) as $line) {
            $this->ensureSpace(17);
            $this->text($line, 42, $this->y, 9);
            $this->y -= 12;
        }

        $this->y -= 6;
    }

    private function bullet(string $text): void
    {
        foreach ($this->wrap($text, 94) as $index => $line) {
            $this->ensureSpace(16);
            $this->text($index === 0 ? '- '.$line : '  '.$line, 48, $this->y, 9);
            $this->y -= 12;
        }
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->y - $needed < 62) {
            $this->footer();
            $this->lines[] = '%%PAGEBREAK';
            $this->y = 790;
            $this->drawLetterhead(128, 238, 340, 250);
        }
    }

    private function footer(): void
    {
        $this->text(self::FOOTER, 42, 22, 7);
    }

    private function text(string $text, float $x, float $y, int $size = 10, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->lines[] = sprintf('BT /%s %d Tf %.2F %.2F Td (%s) Tj ET', $font, $size, $x, $y, $this->escape($text));
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->lines[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
    }

    private function rect(float $x, float $y, float $w, float $h, float $r, float $g, float $b, bool $fill): void
    {
        $operator = $fill ? 'rg' : 'RG';
        $paint = $fill ? 'f' : 'S';
        $this->lines[] = sprintf('q %.3F %.3F %.3F %s %.2F %.2F %.2F %.2F re %s Q', $r, $g, $b, $operator, $x, $y, $w, $h, $paint);
    }

    private function buildPdf(): string
    {
        $pages = [];
        $current = [];

        foreach ($this->lines as $line) {
            if ($line === '%%PAGEBREAK') {
                $pages[] = $current;
                $current = [];
                continue;
            }

            $current[] = $line;
        }

        if ($current !== []) {
            $pages[] = $current;
        }

        foreach ($pages as $index => &$pageLines) {
            $pageLines[] = sprintf('BT /F1 7 Tf %.2F %.2F Td (%s) Tj ET', 510, 22, $this->escape('Página '.($index + 1)));
        }
        unset($pageLines);

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            '',
            "3 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >> endobj\n",
        ];

        $kids = [];
        $nextId = 5;
        $resourceExtra = '';
        $extGStates = '';
        $this->pdfImageObjects($nextId, $objects, $resourceExtra, $extGStates);

        foreach ($pages as $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $content = implode("\n", $pageLines);
            $kids[] = $pageId.' 0 R';
            $objects[] = $pageId." 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>".$resourceExtra.$extGStates." >> /Contents ".$contentId." 0 R >> endobj\n";
            $objects[] = $contentId." 0 obj << /Length ".strlen($content)." >> stream\n".$content."\nendstream endobj\n";
        }

        $objects[1] = "2 0 obj << /Type /Pages /Kids [".implode(' ', $kids)."] /Count ".count($kids)." >> endobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function wrap(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text === '' ? [''] : explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalize($text));
    }

    private function limit(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) <= $length ? $text : mb_substr($text, 0, max($length - 3, 1)).'...';
        }

        return strlen($text) <= $length ? $text : substr($text, 0, max($length - 3, 1)).'...';
    }

    private function normalize(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
    }

    private function money(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }
}
