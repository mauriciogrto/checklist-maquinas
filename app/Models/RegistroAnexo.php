<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroAnexo extends Model
{
    protected $table = 'registro_anexos';

    protected $fillable = [
        'registro_id',
        'formulario_item_id',
        'caminho_arquivo',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(Registro::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FormularioItem::class, 'formulario_item_id')->withTrashed();
    }
}
