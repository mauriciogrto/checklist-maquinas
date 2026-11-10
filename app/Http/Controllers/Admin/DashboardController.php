<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Empresa;
use App\Models\Formulario;
use App\Models\Maquina;
use App\Models\RastreadorDispositivo;
use App\Models\Registro;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const STATUS_ALERTAS_ABERTOS = ['pendente', 'em_analise'];

    public function index(Request $request)
    {
        $alertas = $this->alertasQuery($request)->take(8)->get();
        $pendentes = $this->alertasEmpresaQuery()->whereIn('status', self::STATUS_ALERTAS_ABERTOS)->count();
        $contratosResumo = $this->contratosResumo();
        $checklistsOperacionais = $this->checklistsOperacionais();
        $rastreamentoHabilitado = $this->rastreamentoHabilitado();
        $rastreamentoResumo = $rastreamentoHabilitado ? $this->rastreamentoResumo() : null;
        $rastreamentoItens = $rastreamentoHabilitado ? $this->rastreamentoItens() : collect();

        return view('admin.dashboard', compact('alertas', 'pendentes', 'contratosResumo', 'checklistsOperacionais', 'rastreamentoHabilitado', 'rastreamentoResumo', 'rastreamentoItens'));
    }

    public function alertasJson()
    {
        return response()->json([
            'pendentes' => $this->alertasEmpresaQuery()->whereIn('status', self::STATUS_ALERTAS_ABERTOS)->count(),
            'recentes' => $this->alertasEmpresaQuery()
                ->with(['maquina', 'item', 'operador'])
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    private function alertasQuery(Request $request)
    {
        return $this->alertasEmpresaQuery()
            ->with(['maquina', 'item', 'operador'])
            ->when($request->filled('inicio'), fn ($query) => $query->whereDate('created_at', '>=', $request->inicio))
            ->when($request->filled('fim'), fn ($query) => $query->whereDate('created_at', '<=', $request->fim))
            ->latest();
    }

    private function checklistsMes(): array
    {
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $totais = Registro::query()
            ->selectRaw('periodicidade, COUNT(*) as total')
            ->whereHas('maquina', fn ($query) => $query->where('empresa_id', $this->empresaAtualId()))
            ->where('tipo', 'checklist')
            ->whereBetween('data_hora', [$inicioMes, $fimMes])
            ->groupBy('periodicidade')
            ->pluck('total', 'periodicidade');

        $periodicidades = [
            'diario' => ['label' => 'Diário', 'color' => '#a3e635'],
            'mensal' => ['label' => 'Mensal', 'color' => '#22c55e'],
            'trimestral' => ['label' => 'Trimestral', 'color' => '#14b8a6'],
            'anual' => ['label' => 'Anual', 'color' => '#38bdf8'],
        ];

        $series = collect($periodicidades)->map(function (array $periodicidade, string $key) use ($totais) {
            return [
                'key' => $key,
                'label' => $periodicidade['label'],
                'color' => $periodicidade['color'],
                'total' => (int) ($totais[$key] ?? 0),
            ];
        })->values();

        $total = $series->sum('total');
        $inicio = 0;
        $fatias = [];

        foreach ($series as $item) {
            if ($total === 0 || $item['total'] === 0) {
                continue;
            }

            $fim = $inicio + (($item['total'] / $total) * 100);
            $fatias[] = "{$item['color']} {$inicio}% {$fim}%";
            $inicio = $fim;
        }

        return [
            'series' => $series,
            'total' => $total,
            'gradient' => $fatias ? implode(', ', $fatias) : 'rgba(255, 255, 255, 0.08) 0 100%',
            'periodo' => $inicioMes->format('m/Y'),
        ];
    }

    private function alertasEmpresaQuery()
    {
        return Alerta::query()
            ->whereIn('status', self::STATUS_ALERTAS_ABERTOS)
            ->whereHas('maquina', fn ($query) => $query->where('empresa_id', $this->empresaAtualId()));
    }

    private function maquinasEmpresaQuery()
    {
        return Maquina::query()
            ->where('empresa_id', $this->empresaAtualId());
    }

    private function contratosResumo(): array
    {
        $hoje = now()->toDateString();
        $limite = now()->addDays(15)->toDateString();
        $base = $this->maquinasEmpresaQuery();
        $total = (clone $base)->count();

        return [
            'total' => $total,
            'locadas' => (clone $base)->where('locacao_status', 'locada')->count(),
            'transporte' => (clone $base)->where('locacao_status', 'em_transporte')->count(),
            'disponiveis' => (clone $base)->where(function ($query) {
                $query->whereNull('locacao_status')
                    ->orWhere('locacao_status', 'disponivel');
            })->count(),
            'contratos_vencendo' => (clone $base)
                ->whereNotNull('contrato_fim')
                ->whereNull('devolucao_realizada')
                ->whereBetween('contrato_fim', [$hoje, $limite])
                ->count(),
            'contratos_vencidos' => (clone $base)
                ->whereNotNull('contrato_fim')
                ->whereNull('devolucao_realizada')
                ->whereDate('contrato_fim', '<', $hoje)
                ->count(),
            'devolucoes_previstas' => (clone $base)
                ->whereNotNull('devolucao_prevista')
                ->whereNull('devolucao_realizada')
                ->whereBetween('devolucao_prevista', [$hoje, $limite])
                ->count(),
            'nao_devolvidas' => (clone $base)
                ->whereIn('locacao_status', ['locada', 'em_transporte'])
                ->whereNull('devolucao_realizada')
                ->count(),
            'devolucoes_atrasadas' => (clone $base)
                ->whereNotNull('devolucao_prevista')
                ->whereNull('devolucao_realizada')
                ->whereDate('devolucao_prevista', '<', $hoje)
                ->count(),
            'com_contrato' => (clone $base)
                ->whereHas('arquivos', fn ($query) => $query->where('categoria', 'contrato'))
                ->count(),
        ];
    }

    private function rastreamentoResumo(): array
    {
        $base = $this->rastreadoresAtivosEmpresaQuery();
        $limiteOnline = now()->subMinutes(RastreadorDispositivo::ONLINE_MINUTES);
        $dispositivos = (clone $base)->get();
        $online = $dispositivos->filter(fn (RastreadorDispositivo $rastreador) => $rastreador->estaOnline());
        $emMovimento = $online->filter(fn (RastreadorDispositivo $rastreador) => $rastreador->emMovimento());
        $comAlimentacao = $online->filter(fn (RastreadorDispositivo $rastreador) => $rastreador->comAlimentacaoExterna());
        $semAlimentacao = $online->filter(fn (RastreadorDispositivo $rastreador) => $rastreador->semAlimentacaoExterna());
        $horasHojeMinutos = $dispositivos->sum(fn (RastreadorDispositivo $rastreador) => $rastreador->minutosTrabalhadosHoje());

        return [
            'com_rastreador' => $dispositivos->count(),
            'sem_rastreador' => $this->maquinasEmpresaQuery()->whereDoesntHave('rastreador')->count(),
            'online' => $online->count(),
            'sem_comunicacao' => (clone $base)
                ->where(function ($inner) use ($limiteOnline) {
                    $inner->whereNull('ultimo_sinal_em')
                        ->orWhere('ultimo_sinal_em', '<', $limiteOnline);
                })
                ->count(),
            'em_movimento' => $emMovimento->count(),
            'ligadas' => $comAlimentacao->count(),
            'sem_alimentacao' => $semAlimentacao->count(),
            'paradas' => max($online->count() - $emMovimento->count(), 0),
            'sem_posicao' => (clone $base)
                ->where(function ($query) {
                    $query->whereNull('ultima_latitude')
                        ->orWhereNull('ultima_longitude');
                })
                ->count(),
            'horas_hoje_minutos' => $horasHojeMinutos,
            'horas_hoje_label' => $horasHojeMinutos < 60
                ? $horasHojeMinutos.' min'
                : number_format($horasHojeMinutos / 60, 1, ',', '.').' h',
        ];
    }

    private function rastreamentoItens()
    {
        return $this->rastreadoresAtivosEmpresaQuery()
            ->with('maquina')
            ->orderByRaw('ultimo_sinal_em IS NULL ASC')
            ->latest('ultimo_sinal_em')
            ->take(6)
            ->get();
    }

    private function rastreadoresAtivosEmpresaQuery()
    {
        return RastreadorDispositivo::query()
            ->whereHas('maquina', fn ($query) => $query->where('empresa_id', $this->empresaAtualId()));
    }

    private function rastreamentoHabilitado(): bool
    {
        return (bool) $this->empresaAtual()?->rastreamentoHabilitado();
    }

    private function checklistsOperacionais(): array
    {
        return [
            'diario' => $this->resumoChecklistPeriodo('diario', 'Hoje', now()->startOfDay(), now()->endOfDay(), '#baff5a'),
            'mensal' => $this->resumoChecklistPeriodo('mensal', 'Mês', now()->startOfMonth(), now()->endOfMonth(), '#22c55e'),
            'anual' => $this->resumoChecklistPeriodo('anual', 'Ano', now()->startOfYear(), now()->endOfYear(), '#14b8a6'),
        ];
    }

    private function resumoChecklistPeriodo(string $periodicidade, string $label, $inicio, $fim, string $cor): array
    {
        $formularios = Formulario::query()
            ->where('tipo', 'checklist_maquina')
            ->where('periodicidade', $periodicidade)
            ->whereHas('maquina', fn ($query) => $query
                ->where('empresa_id', $this->empresaAtualId())
                ->where('status', 'ativa'))
            ->pluck('id');

        $total = $formularios->count();

        $concluidos = $total
            ? Registro::query()
                ->where('tipo', 'checklist')
                ->where('periodicidade', $periodicidade)
                ->whereIn('formulario_id', $formularios)
                ->whereBetween('data_hora', [$inicio, $fim])
                ->distinct('formulario_id')
                ->count('formulario_id')
            : 0;

        $abertos = max($total - $concluidos, 0);

        return [
            'periodicidade' => $periodicidade,
            'label' => $label,
            'total' => $total,
            'concluidos' => $concluidos,
            'abertos' => $abertos,
            'percentual' => $total ? round(($concluidos / $total) * 100) : 0,
            'cor' => $cor,
        ];
    }

    private function maquinasGestao()
    {
        return $this->maquinasEmpresaQuery()
            ->with('arquivos')
            ->where(function ($query) {
                $query->whereNotNull('contrato_fim')
                    ->orWhereNotNull('devolucao_prevista')
                    ->orWhereIn('locacao_status', ['locada', 'em_transporte']);
            })
            ->orderByRaw("
                CASE
                    WHEN contrato_fim IS NOT NULL AND devolucao_realizada IS NULL AND contrato_fim < CURDATE() THEN 0
                    WHEN contrato_fim IS NOT NULL AND devolucao_realizada IS NULL AND contrato_fim <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 1
                    WHEN devolucao_prevista IS NOT NULL AND devolucao_realizada IS NULL AND devolucao_prevista < CURDATE() THEN 2
                    WHEN devolucao_prevista IS NOT NULL AND devolucao_realizada IS NULL AND devolucao_prevista <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 3
                    WHEN locacao_status IN ('locada', 'em_transporte') AND devolucao_realizada IS NULL THEN 4
                    ELSE 5
                END
            ")
            ->orderByRaw('COALESCE(contrato_fim, devolucao_prevista, updated_at) ASC')
            ->take(8)
            ->get();
    }

    private function empresasResumo(): ?array
    {
        if (! $this->isSuperAdmin()) {
            return null;
        }

        $total = Empresa::query()->count();
        $emDia = Empresa::query()->where('mensalidade_em_dia', true)->count();
        $pendentes = Empresa::query()->where('mensalidade_em_dia', false)->count();
        $planos = Empresa::query()
            ->selectRaw('plano_periodicidade, COUNT(*) as total')
            ->groupBy('plano_periodicidade')
            ->pluck('total', 'plano_periodicidade');
        $proximoVencimento = Empresa::query()
            ->whereNotNull('plano_vencimento')
            ->orderBy('plano_vencimento')
            ->first();

        return [
            'total' => $total,
            'em_dia' => $emDia,
            'pendentes' => $pendentes,
            'em_dia_percentual' => $total ? round(($emDia / $total) * 100) : 0,
            'pendentes_percentual' => $total ? round(($pendentes / $total) * 100) : 0,
            'planos' => [
                'mensal' => (int) ($planos['mensal'] ?? 0),
                'trimestral' => (int) ($planos['trimestral'] ?? 0),
                'anual' => (int) ($planos['anual'] ?? 0),
            ],
            'proximo_vencimento' => $proximoVencimento,
        ];
    }
}
