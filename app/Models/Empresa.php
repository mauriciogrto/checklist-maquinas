<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Empresa extends Model
{
    protected $table = 'empresas';

    public const PLANOS = [
        'mensal' => 'Mensal',
        'trimestral' => 'Trimestral',
        'anual' => 'Anual',
    ];

    protected $fillable = [
        'nome',
        'documento',
        'status',
        'limite_admins',
        'limite_operadores',
        'limite_maquinas',
        'mensalidade_em_dia',
        'plano_periodicidade',
        'plano_vencimento',
        'modulo_rastreamento',
        'logo_path',
    ];

    public function logoAbsolutePath(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $path = storage_path('app/public/'.$this->logo_path);

        return is_file($path) ? $path : null;
    }

    public function sistemaPropostas(): HasMany
    {
        return $this->hasMany(SistemaProposta::class)->latest();
    }

    public function acessoLiberado(): bool
    {
        return $this->status === 'ativa' && (bool) $this->mensalidade_em_dia;
    }

    protected function casts(): array
    {
        return [
            'limite_admins' => 'integer',
            'limite_operadores' => 'integer',
            'limite_maquinas' => 'integer',
            'mensalidade_em_dia' => 'boolean',
            'plano_vencimento' => 'date',
            'modulo_rastreamento' => 'boolean',
        ];
    }

    public function rastreamentoHabilitado(): bool
    {
        if (! Schema::hasColumn($this->getTable(), 'modulo_rastreamento')) {
            return false;
        }

        return (bool) $this->modulo_rastreamento;
    }

    public function planoLabel(): string
    {
        return self::PLANOS[$this->plano_periodicidade] ?? ucfirst((string) $this->plano_periodicidade);
    }

    public function diasParaVencimento(): ?int
    {
        if (! $this->plano_vencimento) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->plano_vencimento->copy()->startOfDay(), false);
    }

    public function contadorPlano(): string
    {
        $dias = $this->diasParaVencimento();

        if ($dias === null) {
            return 'Sem vencimento';
        }

        if ($dias === 0) {
            return 'Vence hoje';
        }

        if ($dias > 0) {
            return 'Faltam '.$dias.' dia'.($dias === 1 ? '' : 's');
        }

        $atraso = abs($dias);

        return 'Vencido há '.$atraso.' dia'.($atraso === 1 ? '' : 's');
    }

    public function contadorPlanoClasse(): string
    {
        $dias = $this->diasParaVencimento();

        if ($dias === null) {
            return '';
        }

        if ($dias < 0) {
            return 'danger';
        }

        if ($dias <= 7) {
            return 'warning';
        }

        return '';
    }

    public function diasParaArtSeguranca(): ?int
    {
        return $this->proximaMaquinaArtSeguranca()?->diasParaArtSeguranca();
    }

    public function contadorArtSeguranca(): string
    {
        if ($this->maquinas_count === 0 && ! $this->relationLoaded('maquinas')) {
            $this->loadCount('maquinas');
        }

        if ((int) ($this->maquinas_count ?? $this->maquinas()->count()) === 0) {
            return 'Sem maquinas';
        }

        $vencidas = $this->maquinasArtVencidasCount();
        $vencendo = $this->maquinasArtVencendoCount();

        if ($vencidas > 0) {
            return $vencidas.' ART vencida'.($vencidas === 1 ? '' : 's');
        }

        if ($vencendo > 0) {
            return $vencendo.' vencem em 30 dias';
        }

        if ($this->maquinasComArtSeguranca()->isEmpty()) {
            return 'Sem ART informada';
        }

        return 'ARTs em dia';
    }

    public function contadorArtSegurancaClasse(): string
    {
        if ($this->maquinasArtVencidasCount() > 0) {
            return 'danger';
        }

        if ($this->maquinasArtVencendoCount() > 0) {
            return 'warning';
        }

        return '';
    }

    public function maquinasComArtSeguranca()
    {
        $maquinas = $this->relationLoaded('maquinas')
            ? $this->maquinas
            : $this->maquinas()->whereNotNull('art_seguranca_vencimento')->get();

        return $maquinas
            ->filter(fn (Maquina $maquina) => $maquina->art_seguranca_vencimento)
            ->sortBy(fn (Maquina $maquina) => $maquina->diasParaArtSeguranca())
            ->values();
    }

    public function maquinasArtVencidasCount(): int
    {
        return $this->maquinasComArtSeguranca()
            ->filter(fn (Maquina $maquina) => $maquina->diasParaArtSeguranca() < 0)
            ->count();
    }

    public function maquinasArtVencendoCount(): int
    {
        return $this->maquinasComArtSeguranca()
            ->filter(fn (Maquina $maquina) => $maquina->diasParaArtSeguranca() >= 0 && $maquina->diasParaArtSeguranca() <= 30)
            ->count();
    }

    public function proximaMaquinaArtSeguranca(): ?Maquina
    {
        return $this->maquinasComArtSeguranca()->first();
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function operadores(): HasMany
    {
        return $this->hasMany(Operador::class);
    }

    public function maquinas(): HasMany
    {
        return $this->hasMany(Maquina::class);
    }

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class);
    }
}
