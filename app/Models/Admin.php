<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'empresa_id',
        'is_superadmin',
        'perfil',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'is_superadmin' => 'boolean',
        ];
    }

    public function perfil(): string
    {
        if ($this->is_superadmin) {
            return 'superadmin';
        }

        return $this->perfil ?: 'admin';
    }

    public function isComercial(): bool
    {
        return ! $this->is_superadmin && $this->perfil() === 'comercial';
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
