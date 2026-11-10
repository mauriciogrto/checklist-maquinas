<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Maquina;
use App\Models\Orcamento;
use App\Models\Registro;

class InicioController extends Controller
{
    private const STATUS_ALERTAS_ABERTOS = ['pendente', 'em_analise'];

    public function index()
    {
        $empresa = $this->empresaAtual();
        $empresaId = $this->empresaAtualId();
        $hoje = now();
        $limiteContratos = $hoje->copy()->addDays(15)->toDateString();
        $limiteRastreamento = $hoje->copy()->subHours(2);

        $maquinas = Maquina::query()->where('empresa_id', $empresaId);
        $rastreamentoHabilitado = (bool) $empresa?->rastreamentoHabilitado();

        $resumo = [
            'alertas_abertos' => Alerta::query()
                ->whereIn('status', self::STATUS_ALERTAS_ABERTOS)
                ->whereHas('maquina', fn ($query) => $query->where('empresa_id', $empresaId))
                ->count(),
            'maquinas_locadas' => (clone $maquinas)->where('locacao_status', 'locada')->count(),
            'contratos_vencendo' => (clone $maquinas)
                ->whereNotNull('contrato_fim')
                ->whereNull('devolucao_realizada')
                ->whereBetween('contrato_fim', [$hoje->toDateString(), $limiteContratos])
                ->count(),
            'checklists_hoje' => Registro::query()
                ->where('tipo', 'checklist')
                ->whereDate('data_hora', $hoje->toDateString())
                ->whereHas('maquina', fn ($query) => $query->where('empresa_id', $empresaId))
                ->count(),
            'orcamentos_abertos' => Orcamento::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('status', ['rascunho', 'enviado'])
                ->count(),
        ];

        $rastreamento = null;

        if ($rastreamentoHabilitado) {
            $rastreamento = [
                'online' => (clone $maquinas)
                    ->whereHas('rastreador', fn ($query) => $query->where('ultimo_sinal_em', '>=', $limiteRastreamento))
                    ->count(),
                'sem_comunicacao' => (clone $maquinas)
                    ->whereHas('rastreador', fn ($query) => $query
                        ->where(function ($inner) use ($limiteRastreamento) {
                            $inner->whereNull('ultimo_sinal_em')
                                ->orWhere('ultimo_sinal_em', '<', $limiteRastreamento);
                        }))
                    ->count(),
            ];
        }

        return view('admin.inicio', [
            'empresa' => $empresa,
            'resumo' => $resumo,
            'rastreamento' => $rastreamento,
            'rastreamentoHabilitado' => $rastreamentoHabilitado,
        ]);
    }
}
