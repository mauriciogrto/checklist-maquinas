<?php

namespace App\Services;

use App\Models\Maquina;

class HorimetroPdfService
{
    use PdfBranding;

    private array $lines = [];
    private float $y = 790;

    public function render(Maquina $maquina, array $resumo): string
    {
        $this->lines = [];
        $this->y = 790;
        $this->loadBrandImage($maquina->empresa?->logoAbsolutePath());
        $this->drawLetterhead();

        $this->rect(42, 756, 511, 54, 0.92, 0.98, 0.96, true);
        if ($this->brandImage) {
            $this->drawBrandMark(58, 770, 92, 30, 1);
            $this->text('RELATÓRIO DE HORÍMETRO', 162, 790, 15, true);
            $this->text($this->limit($maquina->nome, 48), 162, 770, 11, true);
        } else {
            $this->text('RELATÓRIO DE HORÍMETRO', 58, 790, 16, true);
            $this->text($this->limit($maquina->nome, 62), 58, 770, 11, true);
        }
        $this->text('Gerado em: '.now()->format('d/m/Y H:i'), 390, 790, 9);
        $this->text('Máquina #'.$maquina->id, 390, 774, 9);

        $this->y = 724;
        $this->section('Dados do equipamento');
        $this->infoBox(42, $this->y - 50, 166, 50, 'Código', $maquina->codigo_interno ?: 'Não informado', '');
        $this->infoBox(216, $this->y - 50, 166, 50, 'Cliente', $maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente ?: 'Não informado', '');
        $this->infoBox(390, $this->y - 50, 163, 50, 'IMEI', $maquina->rastreador?->imei ?: 'Não vinculado', '');
        $this->y -= 70;

        $this->section('Período analisado');
        $this->infoBox(42, $this->y - 50, 166, 50, 'Início', $resumo['inicio']->format('d/m/Y'), '');
        $this->infoBox(216, $this->y - 50, 166, 50, 'Fim', $resumo['fim']->format('d/m/Y'), '');
        $this->infoBox(390, $this->y - 50, 163, 50, 'Uso no período', $resumo['total_label'], '');
        $this->y -= 58;
        $this->infoBox(42, $this->y - 50, 166, 50, 'Horímetro inicial', $resumo['horimetro_inicial_label'] ?? '0 min', '');
        $this->infoBox(216, $this->y - 50, 166, 50, 'Uso rastreado', $resumo['total_rastreado_label'] ?? '0 min', '');
        $this->infoBox(390, $this->y - 50, 163, 50, 'Total geral estimado', $resumo['total_geral_label'] ?? '0 min', '');
        $this->y -= 72;

        $this->section('Resumo diário');
        $this->tableHeader(['Data', 'Horas', 'Sessões'], [50, 280, 410]);

        if ($resumo['dias']->isEmpty()) {
            $this->tableRow(['Sem registros no período', '0 min', '-'], [50, 280, 410]);
        } else {
            foreach ($resumo['dias']->take(16) as $dia) {
                $this->tableRow([
                    $dia['dia']->format('d/m/Y'),
                    $dia['label'],
                    (string) count($dia['eventos']),
                ], [50, 280, 410]);
            }
        }

        $this->y -= 8;
        $this->section('Últimas sessões');
        $this->tableHeader(['Início', 'Fim', 'Duração'], [50, 215, 390]);

        $eventos = $resumo['eventos']->sortByDesc(fn (array $evento) => $evento['inicio']->timestamp)->take(12);

        if ($eventos->isEmpty()) {
            $this->tableRow(['Sem sessões com ignição ligada', '-', '-'], [50, 215, 390]);
        } else {
            foreach ($eventos as $evento) {
                $this->tableRow([
                    $evento['inicio']->format('d/m/Y H:i'),
                    $evento['fim']->format('d/m/Y H:i'),
                    $evento['label'],
                ], [50, 215, 390]);
            }
        }

        $this->paragraph('Observação: o horímetro considera os intervalos em que o rastreador informou ignição ligada. Intervalos longos sem comunicação são limitados para evitar a contagem indevida de horas por perda de sinal.');

        $this->text('Gestão de Máquinas, Telemetria e Rastreamento', 42, 28, 7);

        return $this->buildPdf();
    }

    public function filename(Maquina $maquina): string
    {
        $nome = preg_replace('/[^A-Za-z0-9_-]+/', '-', $maquina->codigo_interno ?: $maquina->nome ?: 'maquina-'.$maquina->id);

        return strtolower(trim('horimetro-'.$nome, '-')).'.pdf';
    }

    private function section(string $title): void
    {
        $this->y -= 8;
        $this->text($title, 42, $this->y, 13, true);
        $this->line(42, $this->y - 5, 553, $this->y - 5);
        $this->y -= 18;
    }

    private function infoBox(float $x, float $y, float $w, float $h, string $label, string $value, string $detail): void
    {
        $this->rect($x, $y, $w, $h, 0.97, 0.98, 0.99, true);
        $this->rect($x, $y, $w, $h, 0.82, 0.86, 0.90, false);
        $this->text($label, $x + 10, $y + $h - 15, 8, true);
        $this->text($this->limit($value, 28), $x + 10, $y + $h - 31, 9, true);

        if ($detail !== '') {
            $this->text($this->limit($detail, 30), $x + 10, $y + $h - 43, 8);
        }
    }

    private function tableHeader(array $labels, array $columns): void
    {
        $this->rect(42, $this->y - 18, 511, 20, 0.92, 0.98, 0.96, true);

        foreach ($labels as $index => $label) {
            $this->text($label, $columns[$index], $this->y - 12, 8, true);
        }

        $this->y -= 24;
    }

    private function tableRow(array $values, array $columns): void
    {
        if ($this->y < 72) {
            return;
        }

        $this->rect(42, $this->y - 20, 511, 22, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - 20, 511, 22, 0.88, 0.90, 0.93, false);

        foreach ($values as $index => $value) {
            $this->text($this->limit($value, 32), $columns[$index], $this->y - 12, 8);
        }

        $this->y -= 23;
    }

    private function paragraph(string $text): void
    {
        $this->y -= 10;

        foreach ($this->wrap($text, 94) as $line) {
            if ($this->y < 56) {
                return;
            }

            $this->text($line, 42, $this->y, 9);
            $this->y -= 13;
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
        $content = implode("\n", $this->lines);
        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            '',
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >> endobj\n",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >> endobj\n",
        ];
        $nextId = 6;
        $resourceExtra = '';
        $extGStates = '';
        $this->pdfImageObjects($nextId, $objects, $resourceExtra, $extGStates);
        $contentId = $nextId++;
        $objects[2] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >>".$resourceExtra.$extGStates." >> /Contents ".$contentId." 0 R >> endobj\n";
        $objects[] = $contentId." 0 obj << /Length ".strlen($content)." >> stream\n".$content."\nendstream endobj\n";

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
}
