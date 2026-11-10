<?php

namespace App\Services;

use App\Http\Controllers\Admin\ContratoController;
use App\Models\Orcamento;
use App\Models\SistemaConfiguracao;

class OrcamentoContratoPdfService
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

        $this->coverHeader();
        $this->parties();
        $this->rentalItems();
        $this->commercialConditions();
        $this->fixedClauses();
        $this->signatures();
        $this->finishPage();

        return $this->buildPdf();
    }

    public function filename(Orcamento $orcamento): string
    {
        $numero = preg_replace('/[^A-Za-z0-9_-]+/', '-', $orcamento->numero ?: 'orcamento-'.$orcamento->id);

        return strtolower(trim('contrato-'.$numero, '-')).'.pdf';
    }

    private function coverHeader(): void
    {
        $orcamento = $this->orcamento;
        $numero = '#'.$orcamento->id;

        $this->text('CONTRATO DE LOCAÇÃO', 206, 806, 16, true);
        $this->rect(42, 736, 511, 56, 0.92, 0.98, 0.96, true);
        $this->rect(42, 736, 511, 56, 0.74, 0.82, 0.84, false);
        if ($this->brandImage) {
            $this->drawBrandMark(58, 752, 92, 34, 1);
            $this->text($this->limit($this->locadorNome(), 50), 162, 774, 10, true);
            $this->text('Contrato nº '.$numero, 162, 756, 14, true);
        } else {
            $this->text($this->limit($this->locadorNome(), 62), 58, 774, 11, true);
            $this->text('Contrato nº '.$numero, 58, 756, 14, true);
        }
        $this->text('Emissão: '.now()->format('d/m/Y').' às '.now()->format('H:i'), 388, 774, 9);
        $this->text('Orçamento aprovado', 388, 758, 9, true);
        $this->y = 708;
    }

    private function parties(): void
    {
        $orcamento = $this->orcamento;
        $cliente = $orcamento->cliente;

        $this->section('Locadora');
        $this->ensureSpace(76);
        $this->infoBox(42, $this->y - 64, 250, 64, 'Razão social', $this->locadorNome(), 'Documento: '.$this->locadorDocumento());
        $this->infoBox(304, $this->y - 64, 249, 64, 'Sistema', 'Gestão de Máquinas', 'Gestão, telemetria e rastreamento.');
        $this->y -= 82;

        $contato = trim(implode(' | ', array_filter([
            $orcamento->contato_nome ?: $cliente?->nome,
            $cliente?->telefone ?: $orcamento->contato_telefone,
            $cliente?->email ?: $orcamento->contato_email,
            $cliente?->endereco,
        ])));

        $this->section('Locatária');
        $this->ensureSpace(84);
        $this->infoBox(42, $this->y - 72, 250, 72, 'Cliente', $cliente?->nome ?: 'Não informado', 'Documento: '.($cliente?->documento ?: 'Não informado'));
        $this->infoBox(304, $this->y - 72, 249, 72, 'Contato e endereço', $contato ?: 'Não informado', '');
        $this->y -= 90;
    }

    private function rentalItems(): void
    {
        $orcamento = $this->orcamento;

        $this->section('Equipamentos locados');
        $this->tableHeader(['Qtd.', 'Descrição / máquina', 'Período', 'Valor diário', 'Total'], [50, 86, 316, 394, 480]);

        if ($orcamento->itens->isEmpty()) {
            $this->tableRow([
                '1',
                $orcamento->maquina?->nome ?: 'Equipamento não informado',
                '1 dia',
                $this->money((float) $orcamento->valor_locacao),
                $this->money((float) $orcamento->valor_locacao),
            ], [50, 86, 316, 394, 480]);

            return;
        }

        foreach ($orcamento->itens as $item) {
            $descricao = $item->descricao ?: $item->maquina?->nome ?: 'Equipamento';
            $maquina = $item->maquina?->nome && $item->maquina->nome !== $descricao ? ' - '.$item->maquina->nome : '';

            $this->tableRow([
                '1',
                $descricao.$maquina,
                $item->periodoCurtoFormatado(),
                $this->money((float) $item->valor_unitario),
                $this->money($item->valorTotal()),
            ], [50, 86, 316, 394, 480]);
        }
    }

    private function commercialConditions(): void
    {
        $orcamento = $this->orcamento;

        $this->y -= 8;
        $this->section('Condições comerciais');
        $this->ensureSpace(66);
        $this->infoBox(42, $this->y - 54, 166, 54, 'Período previsto', $this->date($orcamento->contrato_inicio).' a '.$this->date($orcamento->contrato_fim), 'Dias definidos por item.');
        $this->infoBox(216, $this->y - 54, 166, 54, 'Faturamento', $this->date($orcamento->data_faturamento), 'Data prevista de faturamento.');
        $this->infoBox(390, $this->y - 54, 163, 54, 'Frete', $this->freteLabel($orcamento->frete_tipo), $this->money((float) $orcamento->valor_frete));
        $this->y -= 66;
        $this->ensureSpace(66);
        $this->infoBox(42, $this->y - 54, 511, 54, 'Condições de pagamento', $this->condicoesPagamentoLabel($orcamento), 'Vencimento conforme faturamento e condições comerciais aprovadas.');
        $this->y -= 72;

        $this->totalLine('Locação', $this->money($orcamento->valorItens()));
        $this->totalLine('Frete / complementos', $this->money((float) $orcamento->valor_frete));
        $this->totalLine('Desconto ('.number_format((float) $orcamento->desconto_percentual, 2, ',', '.').'%)', $this->money($orcamento->valorDescontoPercentual()));
        $this->totalLine('Imposto ('.number_format((float) $orcamento->imposto_percentual, 2, ',', '.').'%)', $this->money($orcamento->valorImposto()));
        $this->ensureSpace(42);
        $this->rect(42, $this->y - 24, 511, 32, 0.92, 0.98, 0.96, true);
        $this->rect(42, $this->y - 24, 511, 32, 0.74, 0.82, 0.84, false);
        $this->text('VALOR TOTAL DO CONTRATO', 56, $this->y - 14, 11, true);
        $this->text($this->money($orcamento->valorTotal()), 398, $this->y - 14, 13, true);
        $this->y -= 52;

        if ($orcamento->observacoes) {
            $this->section('Observações comerciais');
            $this->paragraph($orcamento->observacoes);
        }
    }

    private function fixedClauses(): void
    {
        $modelo = SistemaConfiguracao::valor('modelo_contrato_locacao', ContratoController::modeloPadrao());
        $texto = $this->preencherModelo($modelo);

        $this->section('Condições gerais do contrato');

        foreach (preg_split('/\R{2,}/', trim($texto)) as $bloco) {
            $this->paragraph($bloco);
        }
    }

    private function signatures(): void
    {
        $this->ensureSpace(136);
        $this->y -= 14;
        $this->text('E, por estarem de acordo, as partes assinam o presente contrato.', 42, $this->y, 10);
        $this->y -= 44;

        $this->line(58, $this->y, 250, $this->y);
        $this->line(340, $this->y, 532, $this->y);
        $this->text('LOCADORA', 112, $this->y - 16, 9, true);
        $this->text('LOCATÁRIA', 394, $this->y - 16, 9, true);
        $this->text($this->limit($this->locadorNome(), 36), 58, $this->y - 30, 8);
        $this->text($this->limit($this->orcamento->cliente?->nome ?: 'Cliente', 36), 340, $this->y - 30, 8);
        $this->y -= 72;

        $this->line(58, $this->y, 250, $this->y);
        $this->line(340, $this->y, 532, $this->y);
        $this->text('Testemunha 1', 58, $this->y - 16, 8);
        $this->text('Testemunha 2', 340, $this->y - 16, 8);
    }

    private function preencherModelo(string $modelo): string
    {
        $orcamento = $this->orcamento;
        $cliente = $orcamento->cliente;
        $itens = $orcamento->itens->isNotEmpty()
            ? $orcamento->itens->map(function ($item) {
                $descricao = $item->descricao ?: $item->maquina?->nome ?: 'Item';
                return '- '.$descricao.' | '.$item->periodoFormatado().' | '.$this->money($item->valorTotal());
            })->implode("\n")
            : '- Sem itens informados';

        return strtr($modelo, [
            '{{locador_nome}}' => $this->locadorNome(),
            '{{locador_documento}}' => $this->locadorDocumento(),
            '{{cliente_nome}}' => $cliente?->nome ?: 'Cliente não informado',
            '{{cliente_documento}}' => $cliente?->documento ?: 'Não informado',
            '{{orcamento_numero}}' => $orcamento->numero ?: '#'.$orcamento->id,
            '{{emissao_data}}' => now()->format('d/m/Y'),
            '{{emissao_hora}}' => now()->format('H:i'),
            '{{data_faturamento}}' => $this->date($orcamento->data_faturamento),
            '{{contrato_inicio}}' => $this->date($orcamento->contrato_inicio),
            '{{contrato_fim}}' => $this->date($orcamento->contrato_fim),
            '{{itens}}' => $itens,
            '{{valor_total}}' => $this->money($orcamento->valorTotal()),
            '{{condicoes_pagamento}}' => $this->condicoesPagamentoLabel($orcamento),
            '{{frete_tipo}}' => $this->freteLabel($orcamento->frete_tipo),
            '{{observacoes}}' => $orcamento->observacoes ?: 'Sem observações comerciais.',
        ]);
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
            $numero = '#'.$this->orcamento->id;
            $this->text($this->limit($this->locadorNome(), 58), 42, 812, 8, true);
            $this->text('Contrato nº '.$numero, 420, 812, 8, true);
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
        $this->text($title, 42, $this->y, 12, true);
        $this->line(42, $this->y - 5, 553, $this->y - 5);
        $this->y -= 22;
    }

    private function infoBox(float $x, float $y, float $w, float $h, string $label, string $value, string $detail): void
    {
        $this->rect($x, $y, $w, $h, 0.97, 0.98, 0.99, true);
        $this->rect($x, $y, $w, $h, 0.82, 0.86, 0.90, false);
        $this->text($label, $x + 10, $y + $h - 15, 8, true);

        $lineY = $y + $h - 30;
        foreach (array_slice($this->wrap($value, max((int) floor($w / 5.6), 18)), 0, 2) as $line) {
            $this->text($line, $x + 10, $lineY, 8, true);
            $lineY -= 11;
        }

        if ($detail !== '') {
            foreach (array_slice($this->wrap($detail, max((int) floor($w / 6), 18)), 0, 1) as $line) {
                $this->text($line, $x + 10, $lineY, 7);
            }
        }
    }

    private function tableHeader(array $labels, array $columns): void
    {
        $this->ensureSpace(40);
        $this->rect(42, $this->y - 18, 511, 20, 0.92, 0.98, 0.96, true);

        foreach ($labels as $index => $label) {
            $this->text($label, $columns[$index], $this->y - 12, 8, true);
        }

        $this->y -= 24;
    }

    private function tableRow(array $values, array $columns): void
    {
        $descriptionLines = array_slice($this->wrap((string) $values[1], 38), 0, 3);
        $height = max(32, 18 + (count($descriptionLines) * 10));
        $this->ensureSpace($height + 8);
        $this->rect(42, $this->y - $height + 4, 511, $height, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - $height + 4, 511, $height, 0.88, 0.90, 0.93, false);

        $this->text($this->limit((string) $values[0], 8), $columns[0], $this->y - 12, 8);
        foreach ($descriptionLines as $index => $line) {
            $this->text($line, $columns[1], $this->y - 12 - ($index * 10), 8);
        }
        $this->text($this->limit((string) $values[2], 16), $columns[2], $this->y - 12, 8);
        $this->text($this->limit((string) $values[3], 18), $columns[3], $this->y - 12, 8);
        $this->text($this->limit((string) $values[4], 18), $columns[4], $this->y - 12, 8);
        $this->y -= $height + 1;
    }

    private function totalLine(string $label, string $value): void
    {
        $this->ensureSpace(26);
        $this->rect(42, $this->y - 8, 511, 18, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - 8, 511, 18, 0.88, 0.90, 0.93, false);
        $this->text($this->limit($label, 44), 56, $this->y - 2, 9);
        $this->text($this->limit($value, 32), 400, $this->y - 2, 9, true);
        $this->y -= 20;
    }

    private function paragraph(string $text): void
    {
        $lines = [];

        foreach (explode("\n", trim($text)) as $line) {
            array_push($lines, ...$this->wrap($line, 92));
        }

        foreach ($lines as $line) {
            $this->ensureSpace(22);
            $bold = preg_match('/^\d+\. [A-ZÁÀÂÃÉÊÍÓÔÕÚÇ ]+$/u', $line) === 1;
            $this->text($line, 42, $this->y, $bold ? 10 : 9, $bold);
            $this->y -= $bold ? 17 : 14;
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
        $text = str_replace(['–', '—', 'º'], ['-', '-', 'º'], $text);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
    }

    private function locadorNome(): string
    {
        return $this->orcamento->empresa?->nome ?: 'Empresa Demo';
    }

    private function locadorDocumento(): string
    {
        return $this->orcamento->empresa?->documento ?: 'Não informado';
    }

    private function date($date): string
    {
        return $date ? $date->format('d/m/Y') : 'Não informada';
    }

    private function money(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function freteLabel(?string $tipo): string
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
