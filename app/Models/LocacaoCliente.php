<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocacaoCliente extends Model
{
    protected $table = 'locacao_clientes';

    protected $fillable = [
        'empresa_id',
        'nome',
        'documento',
        'email',
        'telefone',
        'endereco',
        'observacoes',
        'status',
    ];

    public function maquinas(): HasMany
    {
        return $this->hasMany(Maquina::class, 'locacao_cliente_id');
    }

    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'locacao_cliente_id');
    }

    public function operadores(): HasMany
    {
        return $this->hasMany(Operador::class, 'locacao_cliente_id');
    }
}
