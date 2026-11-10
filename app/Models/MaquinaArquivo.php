<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaquinaArquivo extends Model
{
    protected $table = 'maquina_arquivos';

    protected $fillable = [
        'maquina_id',
        'categoria',
        'nome_original',
        'caminho_arquivo',
        'mime_type',
        'tamanho',
    ];

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class);
    }

    public function getTipoAttribute(): string
    {
        return str_starts_with((string) $this->mime_type, 'image/') ? 'Imagem' : 'PDF';
    }

    public function getCategoriaLabelAttribute(): string
    {
        return $this->categoria === 'contrato' ? 'Contrato' : 'Arquivo';
    }
}
