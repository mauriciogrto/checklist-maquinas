<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocacaoCliente;
use App\Models\Maquina;
use App\Models\RastreadorDispositivo;
use Illuminate\Http\Request;

class RastreamentoController extends Controller
{
    public function index(Request $request)
    {
        $this->abortUnlessRastreamento();

        return view('admin.rastreamento.mapa', [
            'filtros' => $request->only(['busca', 'cliente', 'status', 'comunicacao', 'maquina']),
            'clientesLocacao' => $this->clientesLocacao(),
            'resumo' => $this->resumo(),
        ]);
    }

    public function dados(Request $request)
    {
        $this->abortUnlessRastreamento();

        $maquinas = $this->queryMapa($request)
            ->get()
            ->map(function (Maquina $maquina) {
                $rastreador = $maquina->rastreador;
                $temPosicao = $rastreador?->ultima_latitude !== null && $rastreador?->ultima_longitude !== null;

                return [
                    'id' => $maquina->id,
                    'nome' => $maquina->nome,
                    'codigo' => $maquina->codigo_interno,
                    'cliente' => $maquina->locacaoCliente?->nome ?? $maquina->locacao_cliente,
                    'local' => $maquina->localOperacionalLabel(true),
                    'endereco' => $temPosicao ? $rastreador->localizacaoLabel() : null,
                    'status_locacao' => $maquina->locacaoStatusLabel(),
                    'status_classe' => $maquina->locacaoStatusClasse(),
                    'rastreador_status' => $rastreador?->statusLabel() ?? 'Nao vinculado',
                    'rastreador_classe' => $rastreador?->statusClasse() ?? '',
                    'operacao_status' => $rastreador?->operacaoLabel(),
                    'operacao_classe' => $rastreador?->operacaoClasse(),
                    'alimentacao_status' => $rastreador?->alimentacaoLabel(),
                    'alimentacao_classe' => $rastreador?->alimentacaoClasse(),
                    'imei' => $rastreador?->imei,
                    'latitude' => $temPosicao ? (float) $rastreador->ultima_latitude : null,
                    'longitude' => $temPosicao ? (float) $rastreador->ultima_longitude : null,
                    'velocidade' => $rastreador?->ultima_velocidade !== null ? (float) $rastreador->ultima_velocidade : null,
                    'ignicao' => $rastreador?->ignicao,
                    'horimetro_hoje' => $rastreador?->horasTrabalhadasHojeLabel(),
                    'tensao' => $rastreador?->tensao_externa !== null ? (float) $rastreador->tensao_externa : null,
                    'ultimo_sinal' => $rastreador?->ultimoSinalLocalLabel(),
                    'ultimo_sinal_iso' => $rastreador?->ultimo_sinal_em?->toIso8601String(),
                    'url' => route('admin.maquinas.show', $maquina),
                ];
            })
            ->values();

        return response()->json([
            'atualizado_em' => now('America/Sao_Paulo')->format('d/m/Y H:i:s'),
            'resumo' => $this->resumo(),
            'maquinas' => $maquinas,
        ]);
    }

    private function queryMapa(Request $request)
    {
        $limiteOnline = now()->subMinutes(RastreadorDispositivo::ONLINE_MINUTES);

        return $this->scopeEmpresa(Maquina::query())
            ->with(['rastreador', 'locacaoCliente'])
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = trim((string) $request->busca);

                $query->where(function ($inner) use ($busca) {
                    $inner->where('nome', 'like', "%{$busca}%")
                        ->orWhere('codigo_interno', 'like', "%{$busca}%")
                        ->orWhere('modelo', 'like', "%{$busca}%")
                        ->orWhereHas('rastreador', fn ($rastreador) => $rastreador->where('imei', 'like', "%{$busca}%"));
                });
            })
            ->when($request->filled('cliente'), fn ($query) => $query->where('locacao_cliente_id', $request->cliente))
            ->when($request->filled('maquina'), fn ($query) => $query->whereKey($request->maquina))
            ->when($request->filled('status'), fn ($query) => $query->where('locacao_status', $request->status))
            ->when($request->filled('comunicacao'), function ($query) use ($request, $limiteOnline) {
                match ($request->comunicacao) {
                    'online' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador->where('ultimo_sinal_em', '>=', $limiteOnline)),
                    'offline' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador
                        ->where(function ($inner) use ($limiteOnline) {
                            $inner->whereNull('ultimo_sinal_em')
                                ->orWhere('ultimo_sinal_em', '<', $limiteOnline);
                        })),
                    'sem_alimentacao' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador
                        ->where('ultimo_sinal_em', '>=', $limiteOnline)
                        ->whereNotNull('tensao_externa')
                        ->where('tensao_externa', '<=', RastreadorDispositivo::EXTERNAL_POWER_OFF_MAX_VOLTAGE)),
                    'sem_posicao' => $query->whereHas('rastreador', fn ($rastreador) => $rastreador
                        ->where(function ($inner) {
                            $inner->whereNull('ultima_latitude')
                                ->orWhereNull('ultima_longitude');
                        })),
                    'sem_rastreador' => $query->whereDoesntHave('rastreador'),
                    default => null,
                };
            })
            ->orderBy('nome');
    }

    private function resumo(): array
    {
        $base = $this->scopeEmpresa(Maquina::query());
        $limiteOnline = now()->subMinutes(RastreadorDispositivo::ONLINE_MINUTES);

        return [
            'total' => (clone $base)->count(),
            'com_rastreador' => (clone $base)->whereHas('rastreador')->count(),
            'online' => (clone $base)->whereHas('rastreador', fn ($query) => $query->where('ultimo_sinal_em', '>=', $limiteOnline))->count(),
            'sem_comunicacao' => (clone $base)->whereHas('rastreador', fn ($query) => $query
                ->where(function ($inner) use ($limiteOnline) {
                    $inner->whereNull('ultimo_sinal_em')
                        ->orWhere('ultimo_sinal_em', '<', $limiteOnline);
                }))
                ->count(),
            'sem_alimentacao' => (clone $base)->whereHas('rastreador', fn ($query) => $query
                ->where('ultimo_sinal_em', '>=', $limiteOnline)
                ->whereNotNull('tensao_externa')
                ->where('tensao_externa', '<=', RastreadorDispositivo::EXTERNAL_POWER_OFF_MAX_VOLTAGE))
                ->count(),
            'com_posicao' => (clone $base)->whereHas('rastreador', fn ($query) => $query
                ->whereNotNull('ultima_latitude')
                ->whereNotNull('ultima_longitude'))
                ->count(),
        ];
    }

    private function clientesLocacao()
    {
        return $this->scopeEmpresa(LocacaoCliente::query())
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();
    }

    private function abortUnlessRastreamento(): void
    {
        abort_unless((bool) $this->empresaAtual()?->rastreamentoHabilitado(), 404);
    }
}
