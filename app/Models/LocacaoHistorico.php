<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocacaoHistorico extends Model
{
    protected $table = 'locacao_historicos';

    protected $fillable = [
        'empresa_id',
        'maquina_id',
        'locacao_cliente_id',
        'admin_id',
        'status_anterior',
        'status_novo',
        'cliente_anterior',
        'cliente_novo',
        'local_anterior',
        'local_novo',
        'contrato_inicio',
        'contrato_fim',
        'devolucao_prevista',
        'devolucao_realizada',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'contrato_inicio' => 'date',
            'contrato_fim' => 'date',
            'devolucao_prevista' => 'date',
            'devolucao_realizada' => 'date',
        ];
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(LocacaoCliente::class, 'locacao_cliente_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
