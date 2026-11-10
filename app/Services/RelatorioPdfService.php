<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RelatorioPdfService
{
    private const FOOTER = 'Gestão de Máquinas, Telemetria e Rastreamento';

    private array $lines = [];
    private float $y = 790;

    public function render(array $data): string
    {
        $this->lines = [];
        $this->y = 790;

        $tipo = $data['tipo_label'] ?? 'Relatório geral';
        $periodo = $this->periodoLabel($data['filtros'] ?? []);
        $isManutencoes = ($data['filtros']['tipo'] ?? null) === 'manutencoes';

        $this->cover($tipo, $periodo, $data['filtros_labels'] ?? []);

        if (! $isManutencoes) {
            $this->section('Resumo executivo');
            $this->summaryGrid($data['resumo'] ?? []);

            $this->section('Indicadores principais');
            $this->metricRows($data['indicadores'] ?? []);
        }

        foreach (($data['secoes'] ?? []) as $secao) {
            $this->section($secao['titulo']);

            if ($isManutencoes && ($secao['titulo'] ?? '') === 'Manutenções') {
                $this->maintenanceCards($secao['linhas'] ?? []);
                continue;
            }

            if (! empty($secao['descricao'])) {
                $this->paragraph($secao['descricao']);
            }

            $this->table($secao['colunas'] ?? [], $secao['linhas'] ?? []);
        }

        if (! $isManutencoes && ! empty($data['observacoes'])) {
            $this->section('Observações da análise');
            $this->paragraph($data['observacoes']);
        }

        $this->footer();

        return $this->buildPdf();
    }

    public function filename(array $filtros): string
    {
        $tipo = preg_replace('/[^A-Za-z0-9_-]+/', '-', $filtros['tipo'] ?? 'geral');
        $data = now('America/Sao_Paulo')->format('Ymd-His');

        return strtolower('relatorio-'.$tipo.'-'.$data.'.pdf');
    }

    private function cover(string $tipo, string $periodo, array $filtros): void
    {
        $this->rect(42, 724, 511, 86, 0.92, 0.98, 0.96, true);
        $this->rect(42, 724, 511, 86, 0.74, 0.82, 0.84, false);
        $this->text('RELATÓRIO DE ANÁLISE', 58, 788, 17, true);
        $this->text($tipo, 58, 766, 12, true);
        $this->text('Período: '.$periodo, 58, 748, 10);
        $this->text('Gerado em '.now('America/Sao_Paulo')->format('d/m/Y H:i'), 390, 788, 9);
        $this->y = 694;

        if ($filtros !== []) {
            $this->section('Filtros aplicados');
            foreach ($filtros as $label => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $this->bullet($label.': '.$value);
            }

            $this->y -= 14;
        }
    }

    private function summaryGrid(array $items): void
    {
        if ($items === []) {
            $this->paragraph('Nenhum indicador encontrado para os filtros informados.');
            return;
        }

        $x = 42;
        $count = 0;

        foreach ($items as $label => $value) {
            $this->ensureSpace(68);
            $this->rect($x, $this->y - 52, 159, 54, 0.98, 0.99, 0.99, true);
            $this->rect($x, $this->y - 52, 159, 54, 0.84, 0.88, 0.90, false);
            $this->text(Str::limit((string) $label, 24, ''), $x + 10, $this->y - 16, 8, true);
            $this->text(Str::limit((string) $value, 18, ''), $x + 10, $this->y - 36, 15, true);

            $count++;
            $x += 176;

            if ($count % 3 === 0) {
                $x = 42;
                $this->y -= 66;
            }
        }

        if ($count % 3 !== 0) {
            $this->y -= 66;
        }
    }

    private function metricRows(array $items): void
    {
        if ($items === []) {
            $this->paragraph('Sem indicadores complementares para este relatório.');
            return;
        }

        foreach ($items as $label => $value) {
            $this->ensureSpace(25);
            $this->rect(42, $this->y - 19, 511, 22, 0.99, 0.99, 0.99, true);
            $this->rect(42, $this->y - 19, 511, 22, 0.90, 0.92, 0.94, false);
            $this->text(Str::limit((string) $label, 62, ''), 54, $this->y - 12, 8, true);
            $this->text(Str::limit((string) $value, 42, ''), 365, $this->y - 12, 8);
            $this->y -= 23;
        }

        $this->y -= 16;
    }

    private function table(array $columns, array $rows): void
    {
        if ($columns === []) {
            return;
        }

        $width = 511 / max(count($columns), 1);
        $xPositions = [];
        $widths = [];

        foreach ($columns as $index => $column) {
            $xPositions[$index] = 48 + ($index * $width);
            $widths[$index] = max($width - 10, 42);
        }

        $this->ensureSpace(38);
        $this->rect(42, $this->y - 18, 511, 20, 0.92, 0.98, 0.96, true);

        foreach ($columns as $index => $column) {
            $this->text(Str::limit((string) $column, max((int) floor($width / 5.3), 8), ''), $xPositions[$index], $this->y - 12, 7, true);
        }

        $this->y -= 24;

        if ($rows === []) {
            $this->tableRow(['Nenhum registro encontrado.'], [48], [501], 1);
            return;
        }

        foreach (array_slice($rows, 0, 38) as $row) {
            $this->tableRow($row, $xPositions, $widths, count($columns));
        }

        $this->y -= 14;
    }

    private function maintenanceCards(array $rows): void
    {
        if ($rows === []) {
            $this->paragraph('Nenhum registro de manutenção encontrado para os filtros informados.');
            return;
        }

        foreach (array_slice($rows, 0, 30) as $row) {
            $data = $this->maintenanceRowData($row);
            $description = $data['descricao'] !== '-' ? $data['descricao'] : 'Descrição não informada.';
            $technical = trim('Técnico/empresa: '.$data['tecnico']."\n".'Assinatura: '.$data['assinatura']);
            $descriptionLines = $this->wrap($description, 62);
            $technicalLines = $this->wrap($technical, 28);
            $leftLines = count($descriptionLines);
            $bodyLines = max($leftLines, count($technicalLines));
            $height = max(92, 48 + ($bodyLines * 9));

            $this->ensureSpace($height + 14);
            $top = $this->y;

            $this->rect(42, $top - $height, 511, $height, 0.99, 0.99, 0.99, true);
            $this->rect(42, $top - $height, 511, $height, 0.86, 0.90, 0.92, false);
            $this->rect(42, $top - 28, 511, 28, 0.92, 0.98, 0.96, true);

            $this->text(Str::limit($data['maquina'].' / '.$data['tipo'], 58, ''), 54, $top - 11, 9, true);
            $this->text('Data: '.$data['data_hora'], 390, $top - 10, 7, true);
            $this->text('Horímetro: '.$data['horimetro'], 390, $top - 22, 7);

            $leftX = 54;
            $rightX = 392;
            $bodyY = $top - 45;

            $this->text('Descrição da intervenção', $leftX, $bodyY, 8, true);
            $lineY = $bodyY - 12;

            foreach (array_slice($descriptionLines, 0, 8) as $line) {
                $this->text($line, $leftX, $lineY, 7);
                $lineY -= 9;
            }

            $this->text('Responsável técnico', $rightX, $bodyY, 8, true);
            $rightY = $bodyY - 12;

            foreach (array_slice($technicalLines, 0, 8) as $line) {
                $this->text($line, $rightX, $rightY, 7);
                $rightY -= 9;
            }

            $this->y -= $height + 10;
        }
    }

    private function maintenanceRowData(array $row): array
    {
        if (array_key_exists('data_hora', $row)) {
            return [
                'data_hora' => (string) ($row['data_hora'] ?? '-'),
                'maquina' => (string) ($row['maquina'] ?? '-'),
                'tipo' => (string) ($row['tipo'] ?? '-'),
                'horimetro' => (string) ($row['horimetro'] ?? '-'),
                'descricao' => (string) ($row['descricao'] ?? '-'),
                'tecnico' => (string) ($row['tecnico'] ?? '-'),
                'assinatura' => (string) ($row['assinatura'] ?? '-'),
                'itens' => (string) ($row['itens'] ?? '-'),
            ];
        }

        return [
            'data_hora' => (string) ($row[0] ?? '-'),
            'maquina' => (string) ($row[1] ?? '-'),
            'tipo' => '-',
            'horimetro' => (string) ($row[2] ?? '-'),
            'descricao' => (string) ($row[3] ?? '-'),
            'tecnico' => (string) ($row[4] ?? '-'),
            'assinatura' => (string) ($row[5] ?? '-'),
            'itens' => '-',
        ];
    }

    private function tableRow(array $values, array $columns, array $widths, int $columnCount): void
    {
        $wrapped = [];
        $maxLines = 1;

        foreach (array_values($values) as $index => $value) {
            if ($index >= $columnCount) {
                break;
            }

            $limit = max((int) floor(($widths[$index] ?? 80) / 4.8), 8);
            $lineLimit = $columnCount <= 4 ? 5 : 3;
            $lines = array_slice($this->wrap((string) $value, $limit), 0, $lineLimit);
            $wrapped[$index] = $lines;
            $maxLines = max($maxLines, count($lines));
        }

        $height = max(24, 12 + ($maxLines * 9));
        $this->ensureSpace($height + 8);
        $this->rect(42, $this->y - $height + 4, 511, $height, 0.99, 0.99, 0.99, true);
        $this->rect(42, $this->y - $height + 4, 511, $height, 0.90, 0.92, 0.94, false);

        foreach ($wrapped as $index => $lines) {
            foreach ($lines as $lineIndex => $line) {
                $this->text($line, $columns[$index] ?? 48, $this->y - 11 - ($lineIndex * 9), 7);
            }
        }

        $this->y -= $height + 2;
    }

    private function section(string $title): void
    {
        $this->ensureSpace(52);
        $this->y -= 6;
        $this->text($title, 42, $this->y, 12, true);
        $this->line(42, $this->y - 5, 553, $this->y - 5);
        $this->y -= 22;
    }

    private function paragraph(string $text): void
    {
        foreach ($this->wrap($text, 96) as $line) {
            $this->ensureSpace(16);
            $this->text($line, 42, $this->y, 8);
            $this->y -= 11;
        }

        $this->y -= 5;
    }

    private function bullet(string $text): void
    {
        foreach ($this->wrap($text, 94) as $index => $line) {
            $this->ensureSpace(15);
            $this->text($index === 0 ? '- '.$line : '  '.$line, 48, $this->y, 8);
            $this->y -= 11;
        }
    }

    private function periodoLabel(array $filtros): string
    {
        $inicio = $filtros['inicio'] ?? null;
        $fim = $filtros['fim'] ?? null;

        if ($inicio && $fim) {
            return Carbon::parse($inicio)->format('d/m/Y').' a '.Carbon::parse($fim)->format('d/m/Y');
        }

        if ($inicio) {
            return 'A partir de '.Carbon::parse($inicio)->format('d/m/Y');
        }

        if ($fim) {
            return 'Até '.Carbon::parse($fim)->format('d/m/Y');
        }

        return 'Todo o período';
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->y - $needed < 62) {
            $this->footer();
            $this->lines[] = '%%PAGEBREAK';
            $this->y = 790;
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

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];

        $next = 5;
        $pageIds = [];

        foreach ($pages as $index => $pageLines) {
            $contentId = $next++;
            $pageId = $next++;
            $pageIds[] = $pageId.' 0 R';
            $stream = implode("\n", $pageLines);
            $objects[$contentId] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentId.' 0 R >>';
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageIds).'] /Count '.count($pages).' >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(max(array_keys($objects)) + 1)."\n0000000000 65535 f \n";

        for ($i = 1; $i <= max(array_keys($objects)); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size ".(max(array_keys($objects)) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function wrap(string $text, int $limit): array
    {
        return explode("\n", wordwrap(trim($text), $limit, "\n", true));
    }

    private function escape(string $text): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted === false ? '' : $converted);
    }
}
