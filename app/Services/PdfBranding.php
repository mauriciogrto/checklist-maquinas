<?php

namespace App\Services;

trait PdfBranding
{
    private ?array $brandImage = null;

    private function loadBrandImage(?string $path): void
    {
        $this->brandImage = null;

        if (! $path || ! is_file($path)) {
            return;
        }

        $image = $this->imageToJpegData($path);

        if ($image) {
            $this->brandImage = $image;
        }
    }

    private function drawBrandMark(float $x, float $y, float $maxW, float $maxH, float $opacity = 1.0): void
    {
        if (! $this->brandImage) {
            return;
        }

        [$w, $h] = $this->fitImage($this->brandImage['width'], $this->brandImage['height'], $maxW, $maxH);
        $alpha = max(0.05, min($opacity, 1.0));
        $this->lines[] = sprintf('q %.3F %.3F %.3F rg %.3F %.3F %.3F RG %.2F %.2F %.2F %.2F re f Q', 1, 1, 1, 1, 1, 1, $x, $y, $w, $h);
        $this->lines[] = sprintf('q /GS%d gs %.2F 0 0 %.2F %.2F %.2F cm /ImBrand Do Q', (int) round($alpha * 100), $w, $h, $x, $y);
    }

    private function drawLetterhead(float $x = 118, float $y = 252, float $maxW = 360, float $maxH = 260): void
    {
        if (! $this->brandImage) {
            return;
        }

        [$w, $h] = $this->fitImage($this->brandImage['width'], $this->brandImage['height'], $maxW, $maxH);
        $this->lines[] = sprintf('q /GS8 gs %.2F 0 0 %.2F %.2F %.2F cm /ImBrand Do Q', $w, $h, $x + (($maxW - $w) / 2), $y + (($maxH - $h) / 2));
    }

    private function imageToJpegData(string $path): ?array
    {
        $info = @getimagesize($path);

        if (! $info) {
            return null;
        }

        [$width, $height, $type] = $info;

        if ($type === IMAGETYPE_JPEG) {
            return [
                'data' => file_get_contents($path),
                'width' => $width,
                'height' => $height,
            ];
        }

        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $source = match ($type) {
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };

        if (! $source) {
            return null;
        }

        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 88);
        $data = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $data ? compact('data', 'width', 'height') : null;
    }

    private function fitImage(int $sourceW, int $sourceH, float $maxW, float $maxH): array
    {
        $scale = min($maxW / max($sourceW, 1), $maxH / max($sourceH, 1));

        return [$sourceW * $scale, $sourceH * $scale];
    }

    private function pdfImageObjects(int &$nextId, array &$objects, string &$resourceExtra, string &$extGStates): void
    {
        if (! $this->brandImage) {
            return;
        }

        $imageId = $nextId++;
        $data = $this->brandImage['data'];
        $objects[] = $imageId.' 0 obj << /Type /XObject /Subtype /Image /Width '.$this->brandImage['width'].' /Height '.$this->brandImage['height'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($data)." >> stream\n".$data."\nendstream endobj\n";
        $resourceExtra .= ' /XObject << /ImBrand '.$imageId.' 0 R >>';
        $extGStates .= ' /ExtGState << /GS8 << /Type /ExtGState /ca 0.08 /CA 0.08 >> /GS18 << /Type /ExtGState /ca 0.18 /CA 0.18 >> /GS100 << /Type /ExtGState /ca 1 /CA 1 >> >>';
    }
}
