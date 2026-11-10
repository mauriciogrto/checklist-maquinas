<?php

namespace App\Services;

use App\Models\Maquina;
use App\Models\RastreadorDispositivo;
use App\Models\RastreadorPosicao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HorimetroService
{
    public function periodo(array $filtros): array
    {
        $inicio = $this->data($filtros['inicio'] ?? null)?->startOfDay() ?? now('America/Sao_Paulo')->startOfMonth();
        $fim = $this->data($filtros['fim'] ?? null)?->endOfDay() ?? now('America/Sao_Paulo')->endOfDay();

        if ($fim->lessThan($inicio)) {
            [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [$inicio, $fim];
    }

    public function resumo(Maquina $maquina, array $filtros = []): array
    {
        [$inicio, $fim] = $this->periodo($filtros);
        $rastreador = $maquina->rastreador;
        $horimetroInicialMinutos = $maquina->horimetroInicialMinutos();

        if (! $rastreador) {
            return [
                'inicio' => $inicio,
                'fim' => $fim,
                'total_minutos' => 0,
                'total_label' => $this->formatarMinutos(0),
                'horimetro_inicial_minutos' => $horimetroInicialMinutos,
                'horimetro_inicial_label' => $this->formatarMinutos($horimetroInicialMinutos),
                'total_rastreado_minutos' => 0,
                'total_rastreado_label' => $this->formatarMinutos(0),
                'total_geral_minutos' => $horimetroInicialMinutos,
                'total_geral_label' => $this->formatarMinutos($horimetroInicialMinutos),
                'dias' => collect(),
                'eventos' => collect(),
            ];
        }

        $posicoes = $this->posicoes($rastreador, $inicio, $fim);

        $eventos = $this->eventos($posicoes, $fim, $rastreador);
        $dias = $eventos
            ->groupBy(fn (array $evento) => $evento['inicio']->format('Y-m-d'))
            ->map(function (Collection $grupo, string $dia) {
                $minutos = $grupo->sum('minutos');

                return [
                    'dia' => Carbon::parse($dia, 'America/Sao_Paulo'),
                    'minutos' => $minutos,
                    'label' => $this->formatarMinutos($minutos),
                    'eventos' => $grupo->values(),
                ];
            })
            ->sortByDesc(fn (array $dia) => $dia['dia']->timestamp)
            ->values();

        $totalMinutos = $dias->sum('minutos');
        $totalRastreadoMinutos = $this->eventos(
            $this->posicoes($rastreador, null, now('America/Sao_Paulo')->endOfDay()),
            now('America/Sao_Paulo')->endOfDay(),
            $rastreador
        )->sum('minutos');
        $totalGeralMinutos = $horimetroInicialMinutos + $totalRastreadoMinutos;

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'total_minutos' => $totalMinutos,
            'total_label' => $this->formatarMinutos($totalMinutos),
            'horimetro_inicial_minutos' => $horimetroInicialMinutos,
            'horimetro_inicial_label' => $this->formatarMinutos($horimetroInicialMinutos),
            'total_rastreado_minutos' => $totalRastreadoMinutos,
            'total_rastreado_label' => $this->formatarMinutos($totalRastreadoMinutos),
            'total_geral_minutos' => $totalGeralMinutos,
            'total_geral_label' => $this->formatarMinutos($totalGeralMinutos),
            'dias' => $dias,
            'eventos' => $eventos,
        ];
    }

    public function formatarMinutos(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $restante = $minutos % 60;

        if ($horas === 0) {
            return $restante.' min';
        }

        return $horas.'h '.str_pad((string) $restante, 2, '0', STR_PAD_LEFT).'min';
    }

    private function eventos(Collection $posicoes, Carbon $fim, RastreadorDispositivo $rastreador): Collection
    {
        $eventos = collect();
        $inicioSessao = null;
        $ultimoAtivo = null;

        $posicoes = $posicoes
            ->map(function ($posicao) {
                $posicao->momento_normalizado = RastreadorDispositivo::momentoOperacional($posicao->gps_em, $posicao->recebido_em, $posicao->created_at);

                return $posicao;
            })
            ->filter(fn ($posicao) => $posicao->momento_normalizado)
            ->sortBy(fn ($posicao) => $posicao->momento_normalizado->timestamp)
            ->values();

        foreach ($posicoes as $posicao) {
            $momento = $posicao->momento_normalizado;

            if ((bool) $posicao->ignicao) {
                if ($inicioSessao && $ultimoAtivo && $ultimoAtivo->diffInMinutes($momento, false) > RastreadorDispositivo::MAX_IGNITION_GAP_MINUTES) {
                    $this->adicionarEvento($eventos, $inicioSessao, $ultimoAtivo);
                    $inicioSessao = $momento;
                }

                $inicioSessao ??= $momento;
                $ultimoAtivo = $momento;
                continue;
            }

            if ($inicioSessao && $ultimoAtivo) {
                $this->adicionarEvento($eventos, $inicioSessao, $momento);
            }

            $inicioSessao = null;
            $ultimoAtivo = null;
        }

        if ($inicioSessao && $ultimoAtivo) {
            $agora = now('America/Sao_Paulo');
            $fimSessao = ((bool) $rastreador->ignicao && $rastreador->estaOnline(3) && $fim->greaterThanOrEqualTo($agora))
                ? $agora
                : ($ultimoAtivo->greaterThanOrEqualTo($agora->copy()->subMinutes(3))
                ? ($agora->lessThan($fim) ? $agora : $fim)
                : $ultimoAtivo);
            $this->adicionarEvento($eventos, $inicioSessao, $fimSessao);
        }

        return $eventos;
    }

    private function posicoes(RastreadorDispositivo $rastreador, ?Carbon $inicio, Carbon $fim): Collection
    {
        return RastreadorPosicao::query()
            ->where('rastreador_dispositivo_id', $rastreador->id)
            ->where(function ($query) use ($inicio, $fim) {
                $fimComMargem = $fim->copy()->addHours(3);

                if ($inicio) {
                    $query->whereBetween('gps_em', [$inicio, $fimComMargem])
                        ->orWhereBetween('recebido_em', [$inicio, $fimComMargem]);

                    return;
                }

                $query->where('gps_em', '<=', $fimComMargem)
                    ->orWhere('recebido_em', '<=', $fimComMargem);
            })
            ->orderByRaw('COALESCE(gps_em, recebido_em) ASC')
            ->get(['latitude', 'longitude', 'velocidade', 'ignicao', 'tensao_externa', 'sinal', 'gps_em', 'recebido_em', 'created_at']);
    }

    private function adicionarEvento(Collection $eventos, Carbon $inicio, Carbon $fim): void
    {
        $minutos = (int) floor(max($inicio->diffInMinutes($fim, false), 0));

        if ($minutos <= 0) {
            return;
        }

        $eventos->push([
            'inicio' => $inicio->copy()->timezone('America/Sao_Paulo'),
            'fim' => $fim->copy()->timezone('America/Sao_Paulo'),
            'minutos' => $minutos,
            'label' => $this->formatarMinutos($minutos),
        ]);
    }

    private function data(?string $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        try {
            return Carbon::parse($valor, 'America/Sao_Paulo');
        } catch (\Throwable) {
            return null;
        }
    }
}
