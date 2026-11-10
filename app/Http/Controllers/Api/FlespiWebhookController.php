<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RastreadorDispositivo;
use App\Models\RastreadorEvento;
use App\Models\RastreadorPosicao;
use App\Services\ReverseGeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FlespiWebhookController extends Controller
{
    public function store(Request $request)
    {
        $token = env('FLESPI_WEBHOOK_TOKEN');

        if (! $token) {
            abort(403, 'Webhook nao configurado.');
        }

        // Somente cabeçalho Authorization: Bearer; token na URL vaza em logs de acesso.
        if (! hash_equals($token, (string) $request->bearerToken())) {
            abort(403, 'Token invalido.');
        }

        $payload = $request->json()->all() ?: $request->all();
        $mensagens = $this->normalizarMensagens($payload);

        if (count($mensagens) > 500) {
            abort(413, 'Lote grande demais.');
        }

        $processadas = 0;

        foreach ($mensagens as $mensagem) {
            if ($this->processarMensagem($mensagem)) {
                $processadas++;
            }
        }

        return response()->json([
            'ok' => true,
            'processadas' => $processadas,
        ]);
    }

    private function normalizarMensagens(array $payload): array
    {
        if (isset($payload['messages']) && is_array($payload['messages'])) {
            return $payload['messages'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return array_is_list($payload['data']) ? $payload['data'] : [$payload['data']];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [$payload];
    }

    private function processarMensagem(array $mensagem): bool
    {
        $imei = $this->valor($mensagem, ['ident', 'device.imei', 'imei']);

        if (! $imei) {
            Log::warning('Flespi webhook sem IMEI/ident.');

            return false;
        }

        $rastreador = RastreadorDispositivo::firstOrCreate(
            ['imei' => (string) $imei],
            [
                'modelo' => (string) ($this->valor($mensagem, ['device.type.id']) ?: 'Queclink GV50CG'),
                'provedor' => 'flespi',
                'status' => 'aguardando_vinculo',
            ]
        );

        $latitude = $this->numero($this->valor($mensagem, ['position.latitude', 'latitude']));
        $longitude = $this->numero($this->valor($mensagem, ['position.longitude', 'longitude']));
        $gpsEm = $this->dataFlespi($this->valor($mensagem, ['timestamp', 'position.timestamp', 'device.timestamp']));
        $recebidoEm = $this->dataFlespi($this->valor($mensagem, ['server.timestamp'])) ?: now();
        $ignicao = $this->booleano($this->valor($mensagem, ['engine.ignition.status', 'ignition.status', 'vehicle.ignition.status', 'din.1.status']));
        $velocidade = $this->numero($this->valor($mensagem, ['position.speed', 'speed']));
        $tensao = $this->numero($this->valor($mensagem, ['external.powersource.voltage', 'external.power.voltage', 'power.external.voltage', 'external.voltage']));
        $bateria = $this->numero($this->valor($mensagem, ['battery.voltage', 'battery.level', 'battery.percentage']));
        $sinal = $this->inteiro($this->valor($mensagem, ['gsm.signal.level', 'gsm.signal.quality', 'gsm.signal.rssi', 'gsm.signal.rsrp', 'gsm.signal.dbm', 'rssi']));
        $ignicaoAtual = $ignicao ?? $rastreador->ignicao;
        $tensaoAtual = $tensao ?? $rastreador->tensao_externa;
        $bateriaAtual = $bateria ?? $rastreador->bateria;
        $sinalAtual = $sinal ?? $rastreador->sinal;
        $enderecoResumo = $this->enderecoPayload($mensagem);

        if ($latitude !== null && $longitude !== null && ! $enderecoResumo) {
            $enderecoResumo = app(ReverseGeocodingService::class)->resolver($latitude, $longitude, $rastreador);
        }

        $rastreador->update([
            'empresa_id' => $rastreador->maquina?->empresa_id,
            'status' => $rastreador->maquina_id ? 'ativo' : 'aguardando_vinculo',
            'ultimo_sinal_em' => $recebidoEm,
            'ultima_latitude' => $latitude !== null ? $latitude : $rastreador->ultima_latitude,
            'ultima_longitude' => $longitude !== null ? $longitude : $rastreador->ultima_longitude,
            'ultimo_endereco_resumido' => $enderecoResumo ?: $rastreador->ultimo_endereco_resumido,
            'ultimo_endereco_atualizado_em' => $enderecoResumo ? now() : $rastreador->ultimo_endereco_atualizado_em,
            'ultima_velocidade' => $velocidade,
            'ignicao' => $ignicaoAtual,
            'tensao_externa' => $tensaoAtual,
            'bateria' => $bateriaAtual,
            'sinal' => $sinalAtual,
            'payload_resumo' => $mensagem,
        ]);

        if ($latitude !== null && $longitude !== null) {
            RastreadorPosicao::create([
                'rastreador_dispositivo_id' => $rastreador->id,
                'empresa_id' => $rastreador->empresa_id,
                'maquina_id' => $rastreador->maquina_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'endereco_resumido' => $enderecoResumo,
                'velocidade' => $velocidade,
                'ignicao' => $ignicaoAtual,
                'tensao_externa' => $tensaoAtual,
                'bateria' => $bateriaAtual,
                'sinal' => $sinalAtual,
                'gps_em' => $gpsEm,
                'recebido_em' => $recebidoEm,
                'payload' => $mensagem,
            ]);
        }

        RastreadorEvento::create([
            'rastreador_dispositivo_id' => $rastreador->id,
            'empresa_id' => $rastreador->empresa_id,
            'maquina_id' => $rastreador->maquina_id,
            'tipo' => (string) ($this->valor($mensagem, ['event.enum', 'message.type', 'type']) ?: 'telemetria'),
            'descricao' => (string) ($this->valor($mensagem, ['event.description', 'position.valid']) ?: 'Mensagem recebida da Flespi'),
            'evento_em' => $gpsEm,
            'recebido_em' => $recebidoEm,
            'payload' => $mensagem,
        ]);

        return true;
    }

    private function valor(array $payload, array $chaves): mixed
    {
        foreach ($chaves as $chave) {
            if (array_key_exists($chave, $payload)) {
                return $payload[$chave];
            }
        }

        return null;
    }

    private function numero(mixed $valor): ?float
    {
        return is_numeric($valor) ? (float) $valor : null;
    }

    private function inteiro(mixed $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }

    private function booleano(mixed $valor): ?bool
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function enderecoPayload(array $mensagem): ?string
    {
        $endereco = $this->valor($mensagem, ['position.address', 'address', 'geocoder.address']);

        if (! is_string($endereco)) {
            return null;
        }

        $endereco = trim($endereco);

        return $endereco !== '' ? $endereco : null;
    }

    private function dataFlespi(mixed $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        if (is_numeric($valor)) {
            return Carbon::createFromTimestampUTC((int) $valor)->timezone('America/Sao_Paulo');
        }

        return Carbon::parse((string) $valor)->timezone('America/Sao_Paulo');
    }
}
