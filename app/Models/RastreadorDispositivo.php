<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RastreadorDispositivo extends Model
{
    use SoftDeletes;

    public const ONLINE_MINUTES = 10;
    public const MAX_IGNITION_GAP_MINUTES = 15;
    public const EXTERNAL_POWER_OFF_MAX_VOLTAGE = 2.0;

    protected $table = 'rastreador_dispositivos';

    protected $fillable = [
        'empresa_id',
        'maquina_id',
        'imei',
        'modelo',
        'provedor',
        'flespi_device_id',
        'operadora',
        'chip_numero',
        'status',
        'ultimo_sinal_em',
        'ultima_latitude',
        'ultima_longitude',
        'ultimo_endereco_resumido',
        'ultimo_endereco_atualizado_em',
        'ultima_velocidade',
        'ignicao',
        'tensao_externa',
        'bateria',
        'sinal',
        'observacao',
        'payload_resumo',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_sinal_em' => 'datetime',
            'ultima_latitude' => 'decimal:7',
            'ultima_longitude' => 'decimal:7',
            'ultimo_endereco_atualizado_em' => 'datetime',
            'ultima_velocidade' => 'decimal:2',
            'ignicao' => 'boolean',
            'tensao_externa' => 'decimal:2',
            'bateria' => 'decimal:2',
            'payload_resumo' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function posicoes(): HasMany
    {
        return $this->hasMany(RastreadorPosicao::class, 'rastreador_dispositivo_id')->latest('gps_em');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(RastreadorEvento::class, 'rastreador_dispositivo_id')->latest('evento_em');
    }

    public function estaOnline(int $minutos = self::ONLINE_MINUTES): bool
    {
        return $this->ultimo_sinal_em && $this->ultimo_sinal_em->greaterThanOrEqualTo(now()->subMinutes($minutos));
    }

    public function emMovimento(): bool
    {
        return $this->estaOnline() && (bool) $this->ignicao;
    }

    public function semAlimentacaoExterna(): bool
    {
        return $this->estaOnline()
            && $this->tensao_externa !== null
            && (float) $this->tensao_externa <= self::EXTERNAL_POWER_OFF_MAX_VOLTAGE;
    }

    public function comAlimentacaoExterna(): bool
    {
        return $this->estaOnline()
            && $this->tensao_externa !== null
            && (float) $this->tensao_externa > self::EXTERNAL_POWER_OFF_MAX_VOLTAGE;
    }

    public function minutosTrabalhadosHoje(): int
    {
        $agora = now('America/Sao_Paulo');
        $inicio = $agora->copy()->startOfDay();
        $fim = $agora->copy()->addHours(3)->addMinutes(10);
        $posicoes = RastreadorPosicao::query()
            ->where('rastreador_dispositivo_id', $this->id)
            ->where(function ($query) use ($inicio, $fim) {
                $query->whereBetween('gps_em', [$inicio, $fim])
                    ->orWhereBetween('recebido_em', [$inicio, $fim]);
            })
            ->orderByRaw('COALESCE(gps_em, recebido_em) ASC')
            ->get(['ignicao', 'velocidade', 'gps_em', 'recebido_em', 'created_at']);

        $minutos = 0;
        $inicioSessao = null;
        $ultimoAtivo = null;

        $posicoes
            ->map(function ($posicao) {
                $posicao->momento_normalizado = self::momentoOperacional($posicao->gps_em, $posicao->recebido_em, $posicao->created_at);

                return $posicao;
            })
            ->filter(fn ($posicao) => $posicao->momento_normalizado)
            ->sortBy(fn ($posicao) => $posicao->momento_normalizado->timestamp)
            ->values()
            ->each(function ($posicao) use (&$minutos, &$inicioSessao, &$ultimoAtivo) {
                $momento = $posicao->momento_normalizado;

                if ((bool) $posicao->ignicao) {
                    if ($inicioSessao && $ultimoAtivo && $ultimoAtivo->diffInMinutes($momento, false) > self::MAX_IGNITION_GAP_MINUTES) {
                        $minutos += (int) floor(max($inicioSessao->diffInMinutes($ultimoAtivo, false), 0));
                        $inicioSessao = $momento;
                    }

                    $inicioSessao ??= $momento;
                    $ultimoAtivo = $momento;

                    return;
                }

                if ($inicioSessao && $ultimoAtivo) {
                    $minutos += (int) floor(max($inicioSessao->diffInMinutes($momento, false), 0));
                }

                $inicioSessao = null;
                $ultimoAtivo = null;
            });

        if ($inicioSessao && $ultimoAtivo) {
            if ((bool) $this->ignicao && $this->estaOnline(3)) {
                $minutos += (int) floor(max($inicioSessao->diffInMinutes($agora, false), 0));
            } elseif ($ultimoAtivo->greaterThanOrEqualTo($agora->copy()->subMinutes(3))) {
                $minutos += (int) floor(max($inicioSessao->diffInMinutes($agora, false), 0));
            } else {
                $minutos += (int) floor(max($inicioSessao->diffInMinutes($ultimoAtivo, false), 0));
            }
        }

        return $minutos;
    }

    public function horasTrabalhadasHojeLabel(): string
    {
        $minutos = $this->minutosTrabalhadosHoje();

        if ($minutos < 60) {
            return $minutos.' min hoje';
        }

        return number_format($minutos / 60, 1, ',', '.').' h hoje';
    }

    public function ultimoSinalLocalLabel(string $formato = 'd/m/Y H:i'): ?string
    {
        return $this->dataLocalLabel($this->ultimo_sinal_em, $formato, $this->updated_at);
    }

    public static function dataLocalLabel(?Carbon $data, string $formato = 'd/m/Y H:i', ?Carbon $referencia = null): ?string
    {
        return self::dataSaoPaulo($data, $referencia)?->format($formato);
    }

    public static function momentoOperacional(?Carbon $gpsEm, ?Carbon $recebidoEm = null, ?Carbon $referencia = null): ?Carbon
    {
        $gps = self::dataSaoPaulo($gpsEm, $referencia);
        $recebido = self::dataSaoPaulo($recebidoEm, $referencia);

        if ($gps && $recebido && $gps->diffInMinutes($recebido) > 30) {
            return $recebido;
        }

        return $gps ?? $recebido;
    }

    public static function dataSaoPaulo(?Carbon $data, ?Carbon $referencia = null): ?Carbon
    {
        if (! $data) {
            return null;
        }

        $local = $data->copy()->timezone('America/Sao_Paulo');
        $referenciaLocal = $referencia?->copy()->timezone('America/Sao_Paulo');

        if ($referenciaLocal && $referenciaLocal->diffInMinutes($local, false) > 120) {
            return $local->subHours(3);
        }

        if ($local->greaterThan(now('America/Sao_Paulo')->addMinutes(10))) {
            return $local->subHours(3);
        }

        return $local;
    }

    public function operacaoLabel(): string
    {
        if (! $this->ultimo_sinal_em) {
            return 'Aguardando sinal';
        }

        if (! $this->estaOnline()) {
            return 'Sem comunicação';
        }

        if ($this->semAlimentacaoExterna()) {
            return 'Sem alimentação externa';
        }

        if ($this->emMovimento()) {
            return 'Em movimento';
        }

        return 'Parada';
    }

    public function operacaoClasse(): string
    {
        if (! $this->ultimo_sinal_em || ! $this->estaOnline()) {
            return 'danger';
        }

        if ($this->semAlimentacaoExterna()) {
            return 'warning';
        }

        if ($this->emMovimento()) {
            return 'status-conforme';
        }

        return '';
    }

    public function statusLabel(): string
    {
        if (! $this->imei) {
            return 'Aguardando IMEI';
        }

        if (! $this->ultimo_sinal_em) {
            return 'Aguardando primeiro sinal';
        }

        return $this->estaOnline() ? 'Online' : 'Sem comunicação';
    }

    public function statusClasse(): string
    {
        if (! $this->imei || ! $this->ultimo_sinal_em) {
            return 'warning';
        }

        return $this->estaOnline() ? 'status-conforme' : 'danger';
    }

    public function ignicaoLabel(): string
    {
        return $this->ignicao === null ? 'Ignição sem leitura' : ($this->ignicao ? 'Ligada' : 'Desligada');
    }

    public function alimentacaoLabel(): string
    {
        if (! $this->estaOnline() && $this->tensao_externa !== null) {
            return 'Última alimentação: '.number_format((float) $this->tensao_externa, 1, ',', '.').' V';
        }

        if ($this->tensao_externa === null) {
            return 'Alimentação sem leitura';
        }

        if ((float) $this->tensao_externa <= self::EXTERNAL_POWER_OFF_MAX_VOLTAGE) {
            return 'Sem alimentação externa';
        }

        return 'Alimentação: '.number_format((float) $this->tensao_externa, 1, ',', '.').' V';
    }

    public function alimentacaoClasse(): string
    {
        if (! $this->estaOnline()) {
            return 'danger';
        }

        if ($this->tensao_externa === null) {
            return 'warning';
        }

        return (float) $this->tensao_externa <= self::EXTERNAL_POWER_OFF_MAX_VOLTAGE ? 'warning' : 'status-conforme';
    }

    public function localizacaoLabel(): string
    {
        if ($this->ultimo_endereco_resumido) {
            return $this->ultimo_endereco_resumido;
        }

        if ($this->ultima_latitude !== null && $this->ultima_longitude !== null) {
            return 'Localização em processamento';
        }

        return 'Sem posição recebida';
    }
}
