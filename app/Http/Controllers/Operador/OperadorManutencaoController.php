<?php

namespace App\Http\Controllers\Operador;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\Registro;
use App\Services\FormularioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManutencaoController extends Controller
{
    public function create(string $token, FormularioService $formularios)
    {
        $maquina = $this->maquina($token);
        $formulario = $formularios->modeloManutencaoPadrao($maquina->empresa_id);

        return view('operador.formulario', [
            'maquina' => $maquina,
            'formulario' => $formulario,
            'tipo' => 'manutencao',
        ]);
    }

    public function store(Request $request, string $token, FormularioService $formularios)
    {
        $maquina = $this->maquina($token);
        $formulario = $formularios->modeloManutencaoPadrao($maquina->empresa_id);

        $this->validateDynamicForm($request, $formulario->itens);

        DB::transaction(function () use ($request, $maquina, $formulario) {
            $registro = Registro::create([
                'maquina_id' => $maquina->id,
                'formulario_id' => $formulario->id,
                'operador_id' => Auth::guard('operador')->id(),
                'tipo' => 'manutencao',
                'data_hora' => now(),
                'status_geral' => 'conforme',
            ]);

            $respostas = $request->input('respostas', []);
            $anexos = $request->file('anexos', []);

            foreach ($formulario->itens as $item) {
                if ($item->tipo_resposta === 'imagem') {
                    if (isset($anexos[$item->id])) {
                        $path = $anexos[$item->id]->store("registros/{$registro->id}", 'public');
                        $registro->anexos()->create([
                            'formulario_item_id' => $item->id,
                            'caminho_arquivo' => $path,
                        ]);
                    }

                    continue;
                }

                $registro->respostas()->create([
                    'formulario_item_id' => $item->id,
                    'resposta' => $respostas[$item->id] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('maquinas.public.show', $maquina->token)
            ->with('status', 'Manutenção registrada com sucesso.');
    }

    private function validateDynamicForm(Request $request, $itens): void
    {
        $rules = [];

        foreach ($itens as $item) {
            $field = $item->tipo_resposta === 'imagem'
                ? "anexos.{$item->id}"
                : "respostas.{$item->id}";

            $rules[$field] = $item->obrigatorio ? ['required'] : ['nullable'];

            if ($item->tipo_resposta === 'numero') {
                $rules[$field][] = 'numeric';
            }

            if ($item->tipo_resposta === 'imagem') {
                $rules[$field][] = 'image';
                $rules[$field][] = 'max:5120';
            }
        }

        $request->validate($rules);
    }

    private function maquina(string $token): Maquina
    {
        $maquina = Maquina::query()
            ->where('token', $token)
            ->where('status', 'ativa')
            ->firstOrFail();

        $operador = Auth::guard('operador')->user();

        abort_unless(
            $operador
            && (int) $operador->empresa_id === (int) $maquina->empresa_id
            && (! $operador->locacao_cliente_id || (int) $operador->locacao_cliente_id === (int) $maquina->locacao_cliente_id),
            403
        );

        return $maquina;
    }
}
