<?php

namespace App\Http\Controllers\Operador;

use App\Http\Controllers\Controller;
use App\Models\ChecklistReset;
use App\Models\Formulario;
use App\Models\Maquina;
use App\Models\Registro;
use App\Support\ChecklistPeriodicidade;
use Illuminate\Support\Facades\Auth;

class MaquinaAcessoController extends Controller
{
    public function show(string $token)
    {
        $maquina = Maquina::query()
            ->with(['formularios', 'formularioManutencao'])
            ->where('token', $token)
            ->where('status', 'ativa')
            ->firstOrFail();

        $operador = Auth::guard('operador')->user();

        if ($operador && ! $this->operadorPodeAcessar($operador, $maquina)) {
            Auth::guard('operador')->logout();
            request()->session()->regenerateToken();
        }

        $checklists = $this->checklistsOperador($maquina);

        return view('operador.maquina', compact('maquina', 'checklists'));
    }

    private function checklistsOperador(Maquina $maquina): array
    {
        $agora = now();
        $checklists = collect([
            'diario' => 'Checklist diário',
            'mensal' => 'Checklist mensal',
        ])->map(function (string $label, string $periodicidade) use ($maquina, $agora) {
            $formulario = $maquina->formularioPorPeriodicidade($periodicidade);

            if (! $formulario) {
                return [
                    'periodicidade' => $periodicidade,
                    'label' => $label,
                    'concluido' => false,
                    'bloqueado' => true,
                    'status' => 'Nao configurado',
                    'hint' => 'Este checklist ainda nao foi configurado para esta maquina.',
                    'url' => null,
                ];
            }

            $ciclo = ChecklistPeriodicidade::ciclo($periodicidade, $maquina->created_at, $agora);
            $reset = $this->resetCiclo($maquina, $periodicidade, $ciclo);
            $concluido = $this->registroExisteNoCiclo($maquina, $periodicidade, $ciclo, $reset);

            return [
                'periodicidade' => $periodicidade,
                'label' => $label,
                'concluido' => $concluido,
                'status' => $concluido
                    ? ($periodicidade === 'diario' ? 'Concluído hoje' : 'Concluído no ciclo atual')
                    : 'Disponível',
                'hint' => $concluido
                    ? 'Novo envio libera após '.$ciclo['fim']->format('d/m/Y H:i')
                    : ($reset ? 'Liberado manualmente pelo dono.' : 'Pode ser preenchido agora'),
                'url' => route('maquinas.public.checklist.create', [$maquina->token, $periodicidade]),
            ];
        });

        $extras = $maquina->formularios
            ->where('tipo', 'checklist_maquina')
            ->filter(fn (Formulario $formulario) => ChecklistPeriodicidade::isExtra($formulario->periodicidade))
            ->values()
            ->map(function (Formulario $formulario) use ($maquina, $agora) {
                $periodicidade = $formulario->periodicidade ?: 'extra_diario';
                $ciclo = ChecklistPeriodicidade::ciclo($periodicidade, $formulario->created_at ?? $maquina->created_at, $agora);
                $reset = $this->resetCiclo($maquina, $periodicidade, $ciclo);
                $concluido = $this->registroExisteNoCiclo($maquina, $periodicidade, $ciclo, $reset, $formulario);

                return [
                    'periodicidade' => $periodicidade,
                    'label' => $formulario->nome.' ('.ChecklistPeriodicidade::label($periodicidade).')',
                    'concluido' => $concluido,
                    'status' => $concluido ? 'Concluído no ciclo atual' : 'Disponível',
                    'hint' => $concluido
                        ? 'Novo envio libera após '.$ciclo['fim']->format('d/m/Y H:i')
                        : 'Pode ser preenchido agora',
                    'url' => route('maquinas.public.checklist-extra.create', [$maquina->token, $formulario]),
                ];
            });

        return $checklists->concat($extras)->values()->all();
    }

    private function operadorPodeAcessar($operador, Maquina $maquina): bool
    {
        return (int) $operador->empresa_id === (int) $maquina->empresa_id
            && (! $operador->locacao_cliente_id || (int) $operador->locacao_cliente_id === (int) $maquina->locacao_cliente_id);
    }

    private function resetCiclo(Maquina $maquina, string $periodicidade, array $ciclo): ?ChecklistReset
    {
        return ChecklistReset::query()
            ->where('maquina_id', $maquina->id)
            ->where('periodicidade', $periodicidade)
            ->whereBetween('resetado_em', [$ciclo['inicio'], $ciclo['fim']])
            ->latest('resetado_em')
            ->first();
    }

    private function registroExisteNoCiclo(Maquina $maquina, string $periodicidade, array $ciclo, ?ChecklistReset $reset = null, ?Formulario $formulario = null): bool
    {
        return Registro::query()
            ->where('maquina_id', $maquina->id)
            ->where('tipo', 'checklist')
            ->where('periodicidade', $periodicidade)
            ->when($formulario, fn ($query) => $query->where('formulario_id', $formulario->id))
            ->whereBetween('data_hora', [$ciclo['inicio'], $ciclo['fim']])
            ->when($reset, fn ($query) => $query->where('data_hora', '>', $reset->resetado_em))
            ->exists();
    }
}
