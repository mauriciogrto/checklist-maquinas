<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Operador extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $table = 'operadores';

    protected $fillable = [
        'nome',
        'usuario',
        'senha',
        'status',
        'empresa_id',
        'locacao_cliente_id',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(LocacaoCliente::class, 'locacao_cliente_id');
    }

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
        ];
    }
}
