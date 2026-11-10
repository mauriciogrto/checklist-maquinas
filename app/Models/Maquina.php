<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maquina extends Model
{
    use SoftDeletes;

    protected $table = 'maquinas';

    protected $fillable = [
        'nome',
        'empresa_id',
        'setor',
        'modelo',
        'codigo_interno',
        'horimetro_inicial_horas',
        'token',
        'formulario_checklist_id',
        'formulario_manutencao_id',
        'status',
        'art_seguranca_vencimento',
        'locacao_status',
        'locacao_cliente_id',
        'locacao_cliente',
        'locacao_local',
        'contrato_inicio',
        'contrato_fim',
        'devolucao_prevista',
        'devolucao_realizada',
        'frete_responsavel',
        'frete_tipo',
        'frete_observacao',
    ];

    protected function casts(): array
    {
        return [
            'art_seguranca_vencimento' => 'date',
            'contrato_inicio' => 'date',
            'contrato_fim' => 'date',
            'devolucao_prevista' => 'date',
            'devolucao_realizada' => 'date',
            'horimetro_inicial_horas' => 'decimal:2',
        ];
    }

    public function formularioChecklist(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_checklist_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function formularioChecklistDiario(): ?Formulario
    {
        return $this->formularioPorPeriodicidade('diario');
    }

    public function formularioChecklistMensal(): ?Formulario
    {
        return $this->formularioPorPeriodicidade('mensal');
    }

    public function formularioChecklistTrimestral(): ?Formulario
    {
        return $this->formularioPorPeriodicidade('trimestral');
    }

    public function formularioChecklistAnual(): ?Formulario
    {
        return $this->formularioPorPeriodicidade('anual');
    }

    public function formularioPorPeriodicidade(string $periodicidade): ?Formulario
    {
        return $this->formularios()
            ->where('tipo', 'checklist_maquina')
            ->where('periodicidade', $periodicidade)
            ->first();
    }

    public function formularioManutencao(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_manutencao_id');
    }

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class);
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function arquivos(): HasMany
    {
        return $this->hasMany(MaquinaArquivo::class)->latest();
    }

    public function locacaoCliente(): BelongsTo
    {
        return $this->belongsTo(LocacaoCliente::class, 'locacao_cliente_id');
    }

    public function locacaoHistoricos(): HasMany
    {
        return $this->hasMany(LocacaoHistorico::class)->latest();
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(SistemaCobranca::class)->latest('vencimento');
    }

    public function rastreador(): HasOne
    {
        return $this->hasOne(RastreadorDispositivo::class);
    }

    public function loginQrPadrao(): string
    {
        return $this->loginQrAliases()[0] ?? 'maquina'.$this->id;
    }

    public function loginQrAliases(): array
    {
        $aliases = [
            $this->codigo_interno,
            $this->nome,
            'maquina'.$this->id,
        ];

        return collect($aliases)
            ->map(fn ($valor) => self::normalizarLoginQr($valor))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function credencialQrValida(string $usuario, string $senha): bool
    {
        $usuario = self::normalizarLoginQr($usuario);
        $senha = self::normalizarLoginQr($senha);

        return $usuario !== ''
            && $usuario === $senha
            && in_array($usuario, $this->loginQrAliases(), true);
    }

    public static function normalizarLoginQr(?string $valor): string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        $valor = strtolower($ascii ?: $valor);

        return preg_replace('/[^a-z0-9]+/', '', $valor) ?: '';
    }

    public function contratoArquivos(): HasMany
    {
        return $this->hasMany(MaquinaArquivo::class)
            ->where('categoria', 'contrato')
            ->latest();
    }

    public function getContratoAtualAttribute(): ?MaquinaArquivo
    {
        return $this->relationLoaded('arquivos')
            ? $this->arquivos->firstWhere('categoria', 'contrato')
            : $this->contratoArquivos()->first();
    }

    public function locacaoStatusLabel(): string
    {
        return [
            'disponivel' => 'Disponível',
            'locada' => 'Locada',
            'em_transporte' => 'Em transporte',
            'devolvida' => 'Devolvida',
            'manutencao' => 'Em manutenção',
        ][$this->locacao_status ?: 'disponivel'] ?? ucfirst(str_replace('_', ' ', (string) $this->locacao_status));
    }

    public function locacaoStatusClasse(): string
    {
        return [
            'disponivel' => 'status-conforme',
            'locada' => 'alert-status-em-analise',
            'em_transporte' => 'warning',
            'devolvida' => '',
            'manutencao' => 'status-com-pendencia',
        ][$this->locacao_status ?: 'disponivel'] ?? '';
    }

    public function diasParaFimContrato(): ?int
    {
        if (! $this->contrato_fim || $this->devolucao_realizada) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->contrato_fim->copy()->startOfDay(), false);
    }

    public function contadorContrato(): string
    {
        if ($this->devolucao_realizada) {
            return 'Devolvida em '.$this->devolucao_realizada->format('d/m/Y');
        }

        $dias = $this->diasParaFimContrato();

        if ($dias === null) {
            return 'Sem término informado';
        }

        if ($dias === 0) {
            return 'Contrato vence hoje';
        }

        if ($dias > 0) {
            return 'Faltam '.$dias.' dia'.($dias === 1 ? '' : 's');
        }

        $atraso = abs($dias);

        return 'Contrato vencido há '.$atraso.' dia'.($atraso === 1 ? '' : 's');
    }

    public function contadorContratoClasse(): string
    {
        if ($this->devolucao_realizada) {
            return 'status-conforme';
        }

        $dias = $this->diasParaFimContrato();

        if ($dias === null) {
            return '';
        }

        if ($dias < 0) {
            return 'danger';
        }

        if ($dias <= 15) {
            return 'warning';
        }

        return '';
    }

    public function cobrancaFinanceiraStatusLabel(): string
    {
        if (! $this->clienteCobrancaNomes()) {
            return 'Sem cliente';
        }

        return $this->possuiCobrancaVencida() ? 'Vencida' : 'Em dia';
    }

    public function cobrancaFinanceiraStatusClasse(): string
    {
        if (! $this->clienteCobrancaNomes()) {
            return '';
        }

        return $this->possuiCobrancaVencida() ? 'danger' : 'status-conforme';
    }

    public function cobrancaFinanceiraResumo(): string
    {
        if (! $this->clienteCobrancaNomes()) {
            return 'Sem cliente vinculado';
        }

        $cobranca = $this->cobrancaVencidaAtual() ?: $this->ultimaCobrancaFinanceira();

        if (! $cobranca) {
            return 'Nenhuma cobrança cadastrada';
        }

        $valor = 'R$ '.number_format((float) $cobranca->valor, 2, ',', '.');
        $vencimento = $cobranca->vencimento?->format('d/m/Y') ?: 'sem vencimento';

        return $valor.' · '.$vencimento;
    }

    public function possuiCobrancaVencida(): bool
    {
        return (bool) $this->cobrancaVencidaAtual();
    }

    public function cobrancaVencidaAtual(): ?SistemaCobranca
    {
        $nomes = $this->clienteCobrancaNomes();

        if (! $nomes) {
            return null;
        }

        return $this->cobrancasFinanceirasQuery()
            ->where(function ($query) {
                $query->where('status', 'vencida')
                    ->orWhere(function ($inner) {
                        $inner->where('status', 'aberta')
                            ->whereDate('vencimento', '<', now()->toDateString());
                    });
            })
            ->orderBy('vencimento')
            ->first();
    }

    public function ultimaCobrancaFinanceira(): ?SistemaCobranca
    {
        $nomes = $this->clienteCobrancaNomes();

        if (! $nomes) {
            return null;
        }

        return $this->cobrancasFinanceirasQuery()
            ->latest('vencimento')
            ->first();
    }

    public function cobrancasFinanceirasQuery()
    {
        $nomes = $this->clienteCobrancaNomes();

        return SistemaCobranca::query()
            ->where(function ($query) use ($nomes) {
                $query->where('maquina_id', $this->id);

                if ($nomes) {
                    $query->orWhere(function ($legacy) use ($nomes) {
                        $legacy->whereNull('maquina_id')
                            ->whereIn('cliente_nome', $nomes);
                    });
                }
            });
    }

    private function clienteCobrancaNomes(): array
    {
        return collect([
            $this->locacaoCliente?->nome,
            $this->locacao_cliente,
            $this->empresa?->nome,
        ])
            ->filter()
            ->map(fn ($nome) => trim((string) $nome))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function diasParaArtSeguranca(): ?int
    {
        if (! $this->art_seguranca_vencimento) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->art_seguranca_vencimento->copy()->startOfDay(), false);
    }

    public function contadorArtSeguranca(): string
    {
        $dias = $this->diasParaArtSeguranca();

        if ($dias === null) {
            return 'Sem ART informada';
        }

        if ($dias === 0) {
            return 'ART vence hoje';
        }

        if ($dias > 0) {
            return 'Faltam '.$dias.' dia'.($dias === 1 ? '' : 's');
        }

        $atraso = abs($dias);

        return 'ART vencida há '.$atraso.' dia'.($atraso === 1 ? '' : 's');
    }

    public function contadorArtSegurancaClasse(): string
    {
        $dias = $this->diasParaArtSeguranca();

        if ($dias === null) {
            return '';
        }

        if ($dias < 0) {
            return 'danger';
        }

        if ($dias <= 30) {
            return 'warning';
        }

        return '';
    }

    public function horasTrabalhadasHojeLabel(): string
    {
        $rastreador = $this->relationLoaded('rastreador') ? $this->rastreador : $this->rastreador()->first();

        return $rastreador ? $rastreador->horasTrabalhadasHojeLabel() : 'Sem rastreador';
    }

    public function horimetroInicialMinutos(): int
    {
        return (int) round(((float) $this->horimetro_inicial_horas) * 60);
    }

    public function horimetroInicialLabel(): string
    {
        return $this->formatarMinutosHorimetro($this->horimetroInicialMinutos());
    }

    public function formatarMinutosHorimetro(int $minutos): string
    {
        $horas = intdiv(max($minutos, 0), 60);
        $restante = max($minutos, 0) % 60;

        if ($horas === 0) {
            return $restante.' min';
        }

        return $horas.'h '.str_pad((string) $restante, 2, '0', STR_PAD_LEFT).'min';
    }

    public function localOperacionalLabel(bool $usarRastreamento = false): string
    {
        $rastreador = $this->relationLoaded('rastreador') ? $this->rastreador : ($usarRastreamento ? $this->rastreador()->first() : null);

        if ($usarRastreamento && $rastreador?->ultima_latitude !== null && $rastreador?->ultima_longitude !== null) {
            return $rastreador->localizacaoLabel();
        }

        return $this->locacao_local ?: 'Local não informado';
    }
}
