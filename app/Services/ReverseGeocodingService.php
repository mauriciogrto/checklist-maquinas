<?php

namespace App\Services;

use App\Models\RastreadorDispositivo;
use App\Models\RastreadorPosicao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class ReverseGeocodingService
{
    public function resolver(?float $latitude, ?float $longitude, ?RastreadorDispositivo $rastreador = null): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        if ($rastreador?->ultimo_endereco_resumido && $rastreador->ultima_latitude !== null && $rastreador->ultima_longitude !== null) {
            if ($this->distanciaMetros($latitude, $longitude, (float) $rastreador->ultima_latitude, (float) $rastreador->ultima_longitude) <= 80) {
                return $rastreador->ultimo_endereco_resumido;
            }
        }

        $cacheado = $this->buscarEnderecoProximo($latitude, $longitude, $rastreador?->id);

        if ($cacheado) {
            return $cacheado;
        }

        $cacheKey = 'reverse-geocode:'.round($latitude, 4).':'.round($longitude, 4);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($latitude, $longitude) {
            return $this->consultarNominatim($latitude, $longitude);
        });
    }

    private function buscarEnderecoProximo(float $latitude, float $longitude, ?int $rastreadorId): ?string
    {
        $margem = 0.001;

        return RastreadorPosicao::query()
            ->whereNotNull('endereco_resumido')
            ->when($rastreadorId, fn ($query) => $query->where('rastreador_dispositivo_id', $rastreadorId))
            ->whereBetween('latitude', [$latitude - $margem, $latitude + $margem])
            ->whereBetween('longitude', [$longitude - $margem, $longitude + $margem])
            ->latest('recebido_em')
            ->value('endereco_resumido');
    }

    private function consultarNominatim(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'GestaoMaquinas/1.0 contato@example.com',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                    'accept-language' => 'pt-BR',
                ]);

            if (! $response->ok()) {
                return null;
            }

            $json = $response->json();

            return $this->formatarEndereco($json['address'] ?? [], $json['display_name'] ?? null);
        } catch (Throwable) {
            return null;
        }
    }

    private function formatarEndereco(array $address, ?string $displayName): ?string
    {
        $logradouro = $address['road']
            ?? $address['pedestrian']
            ?? $address['residential']
            ?? $address['path']
            ?? $address['neighbourhood']
            ?? $address['suburb']
            ?? null;
        $numero = $address['house_number'] ?? null;
        $cidade = $address['city']
            ?? $address['town']
            ?? $address['village']
            ?? $address['municipality']
            ?? $address['county']
            ?? null;
        $uf = $address['state_code'] ?? $this->ufPorEstado($address['state'] ?? null);

        if ($logradouro || $cidade) {
            $linha = trim((string) $logradouro);

            if ($linha && $numero) {
                $linha .= ', '.$numero;
            }

            $localidade = trim((string) $cidade.($uf ? '/'.$uf : ''));

            return trim($linha.($linha && $localidade ? ' - ' : '').$localidade) ?: null;
        }

        if (! $displayName) {
            return null;
        }

        return collect(explode(',', $displayName))
            ->map(fn ($parte) => trim($parte))
            ->filter()
            ->take(3)
            ->implode(' - ');
    }

    private function ufPorEstado(?string $estado): ?string
    {
        return [
            'Acre' => 'AC',
            'Alagoas' => 'AL',
            'Amapá' => 'AP',
            'Amazonas' => 'AM',
            'Bahia' => 'BA',
            'Ceará' => 'CE',
            'Distrito Federal' => 'DF',
            'Espírito Santo' => 'ES',
            'Goiás' => 'GO',
            'Maranhão' => 'MA',
            'Mato Grosso' => 'MT',
            'Mato Grosso do Sul' => 'MS',
            'Minas Gerais' => 'MG',
            'Pará' => 'PA',
            'Paraíba' => 'PB',
            'Paraná' => 'PR',
            'Pernambuco' => 'PE',
            'Piauí' => 'PI',
            'Rio de Janeiro' => 'RJ',
            'Rio Grande do Norte' => 'RN',
            'Rio Grande do Sul' => 'RS',
            'Rondônia' => 'RO',
            'Roraima' => 'RR',
            'Santa Catarina' => 'SC',
            'São Paulo' => 'SP',
            'Sergipe' => 'SE',
            'Tocantins' => 'TO',
        ][$estado] ?? null;
    }

    private function distanciaMetros(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $raioTerra = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $raioTerra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
