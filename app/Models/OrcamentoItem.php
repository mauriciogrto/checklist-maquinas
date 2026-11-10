<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model
{
    protected $table = 'orcamento_itens';

    protected $fillable = [
        'orcamento_id',
        'maquina_id',
        'descricao',
        'contrato_inicio',
        'contrato_fim',
        'quantidade',
        'valor_unitario',
    ];

    protected function casts(): array
    {
        return [
            'contrato_inicio' => 'date',
            'contrato_fim' => 'date',
            'quantidade' => 'decimal:2',
            'valor_unitario' => 'decimal:2',
        ];
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function valorTotal(): float
    {
        return (float) $this->quantidade * (float) $this->valor_unitario;
    }

    public function periodoFormatado(): string
    {
        if (! $this->contrato_inicio && ! $this->contrato_fim) {
            $dias = max((int) $this->quantidade, 1);

            return number_format($dias, 0, ',', '.').' dia'.($dias === 1 ? '' : 's');
        }

        $inicio = $this->contrato_inicio?->format('d/m/Y') ?? 'sem início';
        $fim = $this->contrato_fim?->format('d/m/Y') ?? 'sem término';
        $dias = max((int) $this->quantidade, 1);

        return $inicio.' a '.$fim.' ('.$dias.' dia'.($dias === 1 ? '' : 's').')';
    }

    public function periodoCurtoFormatado(): string
    {
        $dias = max((int) $this->quantidade, 1);

        if (! $this->contrato_inicio && ! $this->contrato_fim) {
            return $dias.' d';
        }

        $inicio = $this->contrato_inicio?->format('d/m') ?? '--';
        $fim = $this->contrato_fim?->format('d/m') ?? '--';

        return $inicio.' a '.$fim.' ('.$dias.' d)';
    }
}
