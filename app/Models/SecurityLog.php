<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'operador_id',
        'empresa_id',
        'evento',
        'risco',
        'status_code',
        'method',
        'path',
        'route_name',
        'ip',
        'user_agent',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Operador::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function riscoLabel(): string
    {
        return match ($this->risco) {
            'critico' => 'Critico',
            'alto' => 'Alto',
            'medio' => 'Medio',
            default => 'Baixo',
        };
    }
}
