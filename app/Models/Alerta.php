<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alerta extends Model
{
    use SoftDeletes;

    protected $table = 'alertas';

    protected $fillable = [
        'registro_id',
        'maquina_id',
        'formulario_item_id',
        'operador_id',
        'status',
        'observacao',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(Registro::class);
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FormularioItem::class, 'formulario_item_id')->withTrashed();
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Operador::class)->withTrashed();
    }

    public function historicoStatus(): HasMany
    {
        return $this->hasMany(AlertaStatusHistorico::class)->latest('alterado_em');
    }
}
