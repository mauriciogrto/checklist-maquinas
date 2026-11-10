<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orcamento extends Model
{
    protected $table = 'orcamentos';

    protected $fillable = [
        'empresa_id',
        'locacao_cliente_id',
        'maquina_id',
        'numero',
        'titulo',
        'descricao',
        'contato_nome',
        'contato_email',
        'contato_telefone',
        'data_orcamento',
        'validade',
        'data_faturamento',
        'contrato_inicio',
        'contrato_fim',
        'tempo_contrato',
        'frete_tipo',
        'valor_locacao',
        'valor_frete',
        'desconto',
        'desconto_percentual',
        'imposto_percentual',
        'condicoes_pagamento',
        'prazo_faturamento',
        'observacoes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data_orcamento' => 'date',
            'validade' => 'date',
            'data_faturamento' => 'date',
            'contrato_inicio' => 'date',
            'contrato_fim' => 'date',
            'valor_locacao' => 'decimal:2',
            'valor_frete' => 'decimal:2',
            'desconto' => 'decimal:2',
            'desconto_percentual' => 'decimal:2',
            'imposto_percentual' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(LocacaoCliente::class, 'locacao_cliente_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('id');
    }

    public function valorTotal(): float
    {
        return $this->valorSubtotal() + $this->valorImposto();
    }

    public function valorSubtotal(): float
    {
        return max($this->valorBase() - $this->valorDescontoTotal(), 0);
    }

    public function valorImposto(): float
    {
        return $this->valorSubtotal() * ((float) $this->imposto_percentual / 100);
    }

    public function valorItens(): float
    {
        if ($this->relationLoaded('itens') && $this->itens->isNotEmpty()) {
            return $this->itens->sum(fn (OrcamentoItem $item) => $item->valorTotal());
        }

        if ($this->exists && $this->itens()->exists()) {
            return $this->itens()->get()->sum(fn (OrcamentoItem $item) => $item->valorTotal());
        }

        return (float) $this->valor_locacao;
    }

    public function valorBase(): float
    {
        return $this->valorItens() + (float) $this->valor_frete;
    }

    public function valorDescontoPercentual(): float
    {
        return $this->valorBase() * ((float) $this->desconto_percentual / 100);
    }

    public function valorDescontoTotal(): float
    {
        return $this->valorDescontoPercentual();
    }
}
