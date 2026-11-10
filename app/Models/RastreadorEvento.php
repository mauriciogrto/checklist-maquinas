<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RastreadorEvento extends Model
{
    protected $table = 'rastreador_eventos';

    protected $fillable = [
        'rastreador_dispositivo_id',
        'empresa_id',
        'maquina_id',
        'tipo',
        'descricao',
        'evento_em',
        'recebido_em',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'evento_em' => 'datetime',
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
}
