<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RastreadorPosicao extends Model
{
    protected $table = 'rastreador_posicoes';

    protected $fillable = [
        'rastreador_dispositivo_id',
        'empresa_id',
        'maquina_id',
        'latitude',
        'longitude',
        'endereco_resumido',
        'velocidade',
        'ignicao',
        'tensao_externa',
        'bateria',
        'sinal',
        'gps_em',
        'recebido_em',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'velocidade' => 'decimal:2',
            'ignicao' => 'boolean',
            'tensao_externa' => 'decimal:2',
            'bateria' => 'decimal:2',
            'gps_em' => 'datetime',
            'recebido_em' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function rastreador(): BelongsTo
    {
        return $this->belongsTo(RastreadorDispositivo::class, 'rastreador_dispositivo_id');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function momentoLocalLabel(string $formato = 'd/m/Y H:i'): ?string
    {
        return RastreadorDispositivo::momentoOperacional($this->gps_em, $this->recebido_em, $this->created_at)?->format($formato);
    }

    public function localizacaoLabel(): string
    {
        return $this->endereco_resumido ?: 'Localização em processamento';
    }
}
