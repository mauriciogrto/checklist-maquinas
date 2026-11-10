<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SistemaProposta extends Model
{
    protected $table = 'sistema_propostas';

    public const STATUS = [
        'rascunho' => 'Rascunho',
        'enviada' => 'Enviada',
        'aceita' => 'Aceita',
        'recusada' => 'Recusada',
    ];

    protected $fillable = [
        'empresa_id',
        'cliente_nome',
        'cliente_documento',
        'contato_nome',
        'contato_email',
        'contato_telefone',
        'quantidade_rastreadores',
        'valor_rastreador',
        'valor_sistema_mensal',
        'valor_comodato_mensal',
        'valor_treinamento',
        'valor_instalacao',
        'valor_deslocamento',
        'condicoes_pagamento',
        'vencimento_mensal_dia',
        'prazo_contrato_meses',
        'validade_dias',
        'observacoes',
        'status',
        'enviada_em',
        'respondida_em',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_rastreadores' => 'integer',
            'valor_rastreador' => 'decimal:2',
            'valor_sistema_mensal' => 'decimal:2',
            'valor_comodato_mensal' => 'decimal:2',
            'valor_treinamento' => 'decimal:2',
            'valor_instalacao' => 'decimal:2',
            'valor_deslocamento' => 'decimal:2',
            'vencimento_mensal_dia' => 'integer',
            'prazo_contrato_meses' => 'integer',
            'validade_dias' => 'integer',
            'enviada_em' => 'datetime',
            'respondida_em' => 'datetime',
        ];
    }

    public function totalComodatoMensal(): float
    {
        return (float) $this->quantidade_rastreadores * (float) $this->valor_comodato_mensal;
    }

    public function totalMensal(): float
    {
        return (float) $this->valor_sistema_mensal + $this->totalComodatoMensal();
    }

    public function totalImplantacao(): float
    {
        return (float) $this->valor_treinamento + (float) $this->valor_instalacao + (float) $this->valor_deslocamento;
    }

    public function totalReferencialRastreadores(): float
    {
        return (float) $this->quantidade_rastreadores * (float) $this->valor_rastreador;
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusClasse(): string
    {
        return match ($this->status) {
            'aceita' => 'ok',
            'recusada' => 'danger',
            'enviada' => 'warn',
            default => 'info',
        };
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
