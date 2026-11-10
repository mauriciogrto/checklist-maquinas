<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function adminUsuario()
    {
        return Auth::guard('admin')->user();
    }

    protected function isSuperAdmin(): bool
    {
        return (bool) $this->adminUsuario()?->is_superadmin;
    }

    protected function empresaAtualId(): ?int
    {
        $admin = $this->adminUsuario();

        if (! $admin) {
            return null;
        }

        if (! $admin->is_superadmin) {
            return $admin->empresa_id;
        }

        $empresaId = session('empresa_ativa_id');

        if ($empresaId && Empresa::query()->whereKey($empresaId)->where('status', 'ativa')->exists()) {
            return (int) $empresaId;
        }

        $primeiraEmpresa = Empresa::query()->where('status', 'ativa')->orderBy('nome')->first();

        if ($primeiraEmpresa) {
            session(['empresa_ativa_id' => $primeiraEmpresa->id]);
        }

        return $primeiraEmpresa?->id;
    }

    protected function empresaAtual(): ?Empresa
    {
        $empresaId = $this->empresaAtualId();

        return $empresaId ? Empresa::query()->find($empresaId) : null;
    }

    protected function scopeEmpresa(Builder $query, string $column = 'empresa_id'): Builder
    {
        $empresaId = $this->empresaAtualId();

        return $empresaId ? $query->where($column, $empresaId) : $query;
    }

    protected function abortUnlessEmpresa(?int $empresaId): void
    {
        abort_unless($empresaId && $empresaId === $this->empresaAtualId(), 404);
    }
}
