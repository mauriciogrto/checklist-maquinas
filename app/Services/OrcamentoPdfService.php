<?php

namespace App\Services;

use App\Models\Orcamento;

class OrcamentoPdfService
{
    use PdfBranding;

    private const FOOTER = 'Gestão de Máquinas, Telemetria e Rastreamento';

    private array $pages = [];
    private array $lines = [];
    private float $y = 790;
    private ?Orcamento $orcamento = null;

    public function render(Orcamento $orcamento): string
    {
        $this->pages = [];
        $this->lines = [];
        $this->orcamento = $orcamento;
        $this->loadBrandImage($orcamento->empresa?->logoAbsolutePath());
        $this->newPage();

        $numero = 'Orçamento #'.$orcamento->id;

        $this->rect(42, 756, 511, 54, 0.92, 0.98, 0.96, true);
        if ($this->brandImage) {
            $this->drawBrandMark(58, 770, 92, 30, 1);
            $this->text('ORÇAMENTO COMERCIAL', 162, 790, 15, true);
            $this->text($numero, 162, 770, 11, true);
        } else {
            $this->text('ORÇAMENTO COMERCIAL', 58, 790, 16, true);
            $this->text($numero, 58, 770, 11, true);
        }
        $this->text('Data: '.$this->date($orcamento->data_orcamento), 405, 790, 10);
        $this->text('Validade: '.$this->date($orcamento->validade), 405, 774, 10);

        $this->y = 724;
        $this->section('Dados do cliente');
        $this->ensureSpace(70);
        $this->infoBox(42, $this->y - 58, 245, 58, 'Cliente', $orcamento->cliente?->nome ?: 'Cliente não informado', $orcamento->cliente?->documento ?: 'Documento não informado');
        $this->infoBox(308, $this->y - 58, 245, 58, 'Contato', $orcamento->contato_nome ?: $orcamento->cliente?->nome ?: 'Não informado', trim(($orcamento->contato_telefone ?: $orcamento->cliente?->telefone ?: '').' '.($orcamento->contato_email ?: $orcamento->cliente?->email ?: '')));
        $this->y -= 78;

        $this->section('Condições da locação');
        $this->ensureSpace(64);
        $this->infoBox(42, $this->y - 52, 164, 52, 'Período previsto', $this->date($orcamento->contrato_inicio).' a '.$this->date($orcamento->contrato_fim), 'Dias definidos por item');
        $this->infoBox(216, $this->y - 52, 164, 52, 'Faturamento', $this->date($orcamento->data_faturamento), $orcamento->status === 'aprovado' ? 'Orçamento aprovado' : '');
        $this->infoBox(390, $this->y - 52, 163, 52, 'Frete', $this->freteShortLabel($orcamento->frete_tipo), $this->money((float) $orcamento->valor_frete));
        $this->y -= 66;
        $this->ensureSpace(60);
        $this->infoBox(42, $this->y - 48, 511, 48, 'Condições de pagamento', $this->condicoesPagamentoLabel($orcamento), '');
        $this->y -= 66;

        $this->section('Itens do orçamento');
        $this->tableHeader();
        if ($orcamento->itens->isNotEmpty()) {
            foreach ($orcamento->itens as $item) {
                $descricao = $item->descricao ?: $item->maquina?->nome ?: 'Item do orçamento';
                $this->tableRow(
                    $descricao,
                    $item->maquina?->nome ?: 'Máquina não informada',
                    $item->periodoCurtoFormatado(),
                    $this->money((float) $item->valor_unitario),
                    $this->money($item->valorTotal())
                );
            }
        } else {
            $this->tableRow($orcamento->maquina?->nome ?: 'Nenhum item informado', 'Máquina não informada', '1 dia', $this->money((float) $orcamento->valor_locacao), $this->money((float) $orcamento->valor_locacao));
        }

        $this->y -= 10;
        $this->section('Resumo financeiro');
        $this->totalLine('Itens', $this->money($orcamento->valorItens()));
        $this->totalLine('Frete', $this->money((float) $orcamento->valor_frete));
        $this->totalLine('Desconto ('.number_format((float) $orcamento->desconto_percentual, 2, ',', '.').'%)', $this->money($orcamento->valorDescontoPercentual()));
        $this->totalLine('Subtotal', $this->money($orcamento->valorSubtotal()));
        $this->totalLine('Imposto ('.number_format((float) $orcamento->imposto_percentual, 2, ',', '.').'%)', $this->money($orcamento->valorImposto()));
        $this->ensureSpace(42);
        $this->rect(42, $this->y - 22, 511, 30, 0.92, 0.98, 0.96, true);
        $this->rect(42, $this->y - 22, 511, 30, 0.74, 0.82, 0.84, false);
        $this->text('TOTAL FINAL', 56, $this->y - 13, 11, true);
        $this->text($this->money($orcamento->valorTotal()), 390, $this->y - 13, 13, true);
        $this->y -= 52;

        $this->section('Escopo e observações');
        $this->paragraph('Locação de máquinas e equipamentos conforme as condições comerciais desta proposta.');

        if ($orcamento->observacoes) {
            $this->paragraph($orcamento->observacoes);
        }

        $this->finishPage();

        return $this->buildPdf();
    }

    public function filename(Orcamento $orcamento): string
    {
        $numero = preg_replace('/[^A-Za-z0-9_-]+/', '-', $orcamento->numero ?: 'orcamento-'.$orcamento->id);

        return strtolower(trim($numero, '-')).'.pdf';
    }

    private function newPage(): void
    {
        if ($this->lines !== []) {
            $this->finishPage();
        }

        $this->lines = [];
        $this->y = 790;
        $this->drawLetterhead();

        if ($this->orcamento && $this->pages !== []) {
            $this->text('Orçamento #'.$this->orcamento->id, 42, 812, 8, true);
            $this->line(42, 804, 553, 804);
            $this->y = 780;
        }
    }

    private function finishPage(): void
    {
        if ($this->lines === []) {
            return;
        }

        $pageNumber = count($this->pages) + 1;
        $this->text(self::FOOTER, 42, 22, 7);
        $this->text('Página '.$pageNumber, 510, 22, 7);
        $this->pages[] = $this->lines;
        $this->lines = [];
    }

    private function section(string $title): void
    {
        $this->ensureSpace(42);
        $this->y -= 8;
        $this->text($title, 42, $this->y, 12, true);
        $this->line(42, $this->y - 5, 553, $this->y - 5);
        $this->y -= 18;
    }

    private function infoBox(float $x, float $y, float $w, float $h, string $label, string $value, string $detail): void
    {
        $this->rect($x, $y, $w, $h, 0.97, 0.98, 0.99, true);
        $this->rect($x, $y, $w, $h, 0.82, 0.86, 0.90, false);
        $this->text($label, $x + 10, $y + $h - 15, 8, true);

        $lineY = $y + $h - 30;
        foreach (array_slice($this->wrap($value, max((int) floor($w / 5.7), 18)), 0, 2) as $line) {
            $this->text($line, $x + 10, $lineY, 8, true);
            $lineY -= 11;
        }

        if ($detail !== '') {
            foreach (array_slice($this->wrap($detail, max((int) floor($w / 5.9), 18)), 0, 1) as $line) {
                $this->text($line, $x + 10, $lineY, 7);
            }
        }
    }

    private function tableHeader(): void
    {
        $this->ensureSpace(40);
        $this->rect(42, $this->y - 18, 511, 20, 0.92, 0.98, 0.96, true);
        $this->text('Descrição', 50, $this->y - 12, 8, true);
        $this->text('Máquina', 220, $this->y - 12, 8, true);
        $this->text('Período', 365, $this->y - 12, 8, true);
        $this->text('Valor diário', 420, $this->y - 12, 8, true);
        $this->text('Total', 500, $this->y - 12, 8, true);
        $this->y -= 24;
    }

    private function tableRow(string $descricao, string $maquina, string $quantidade, string $unitario, string $total): void
    {
        $descriptionLines = array_slice($this->wrap($descricao, 28), 0, 2);
        $machineLines = array_slice($this->wrap($maquina, 23), 0, 2);
        $height = max(30, 18 + (max(count($descriptionLines), count($machineLines)) * 10));
        $this->ensureSpace($height + 8);
        $this->rect(42, $this->y - $height + 4, 511, $height, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - $height + 4, 511, $height, 0.88, 0.90, 0.93, false);

        foreach ($descriptionLines as $index => $line) {
            $this->text($line, 50, $this->y - 12 - ($index * 10), 8);
        }

        foreach ($machineLines as $index => $line) {
            $this->text($line, 220, $this->y - 12 - ($index * 10), 8);
        }

        $this->text($quantidade, 365, $this->y - 12, 8);
        $this->text($unitario, 420, $this->y - 12, 8);
        $this->text($total, 500, $this->y - 12, 8);
        $this->y -= $height + 1;
    }

    private function totalLine(string $label, string $value): void
    {
        $this->ensureSpace(26);
        $this->rect(42, $this->y - 8, 511, 18, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - 8, 511, 18, 0.88, 0.90, 0.93, false);
        $this->text($this->limit($label, 44), 56, $this->y - 2, 9);
        $this->text($this->limit($value, 32), 390, $this->y - 2, 9, true);
        $this->y -= 20;
    }

    private function paragraph(string $text): void
    {
        foreach ($this->wrap($text, 88) as $line) {
            $this->ensureSpace(20);
            $this->text($line, 42, $this->y, 10);
            $this->y -= 15;
        }

        $this->y -= 8;
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->y - $needed < 58) {
            $this->newPage();
        }
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

        foreach ($this->pages as $lines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $content = implode("\n", $lines);
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

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, max($length - 3, 1)).'...';
    }

    private function normalize(string $text): string
    {
        $text = str_replace(['–', '—'], '-', $text);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
    }

    private function date($date): string
    {
        return $date ? $date->format('d/m/Y') : 'Não informada';
    }

    private function money(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function freteShortLabel(?string $tipo): string
    {
        return [
            'cif' => 'CIF',
            'fob' => 'FOB',
            'outro' => 'Outro',
        ][$tipo ?: ''] ?? 'Não informado';
    }

    private function condicoesPagamentoLabel(Orcamento $orcamento): string
    {
        $condicao = $orcamento->condicoes_pagamento ?: 'A combinar';

        if ($orcamento->condicoes_pagamento === 'Boleto' && $orcamento->prazo_faturamento) {
            return $condicao.' - '.$orcamento->prazo_faturamento;
        }

        return $condicao;
    }
}
