<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registro extends Model
{
    protected $table = 'registros';

    protected $fillable = [
        'maquina_id',
        'formulario_id',
        'operador_id',
        'admin_id',
        'tipo',
        'periodicidade',
        'data_hora',
        'status_geral',
    ];

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Operador::class)->withTrashed();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(RegistroResposta::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(RegistroAnexo::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    protected function casts(): array
    {
        return [
            'data_hora' => 'datetime',
        ];
    }
}
