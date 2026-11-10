<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormularioItem extends Model
{
    use SoftDeletes;

    protected $table = 'formulario_itens';

    protected $fillable = [
        'formulario_id',
        'pergunta',
        'tipo_resposta',
        'ordem',
        'dispara_alerta',
        'obrigatorio',
    ];

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(RegistroResposta::class);
    }

    protected function casts(): array
    {
        return [
            'dispara_alerta' => 'boolean',
            'obrigatorio' => 'boolean',
        ];
    }
}
