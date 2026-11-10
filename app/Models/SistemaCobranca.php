<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SistemaCobranca extends Model
{
    protected $table = 'sistema_cobrancas';

    public const STATUS = [
        'aberta' => 'Aberta',
        'paga' => 'Paga',
        'vencida' => 'Vencida',
        'cancelada' => 'Cancelada',
    ];

    protected $fillable = [
        'maquina_id',
        'cliente_nome',
        'descricao',
        'referencia_mes',
        'vencimento',
        'valor',
        'status',
        'forma_pagamento',
        'observacoes',
        'pago_em',
    ];

    protected function casts(): array
    {
        return [
            'referencia_mes' => 'date',
            'vencimento' => 'date',
            'valor' => 'decimal:2',
            'pago_em' => 'date',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusClasse(): string
    {
        return match ($this->status) {
            'paga' => 'ok',
            'vencida' => 'danger',
            'cancelada' => 'info',
            default => 'warn',
        };
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }
}
