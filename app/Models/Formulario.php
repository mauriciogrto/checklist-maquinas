<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formulario extends Model
{
    protected $table = 'formularios';

    protected $fillable = [
        'nome',
        'empresa_id',
        'tipo',
        'periodicidade',
        'maquina_id',
        'origem_modelo_id',
    ];

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(FormularioItem::class)->orderBy('ordem');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class);
    }
}
