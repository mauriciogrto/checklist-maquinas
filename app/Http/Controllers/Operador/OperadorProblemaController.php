<?php

namespace App\Http\Controllers\Operador;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Maquina;
use App\Models\Registro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProblemaController extends Controller
{
    public function store(Request $request, string $token)
    {
        $maquina = $this->maquina($token);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:5000'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ], [
            'foto.image' => 'Envie uma foto em formato de imagem.',
            'foto.max' => 'A foto pode ter no máximo 5 MB.',
        ]);

        DB::transaction(function () use ($request, $maquina, $data) {
            $registro = Registro::create([
                'maquina_id' => $maquina->id,
                'formulario_id' => null,
                'operador_id' => Auth::guard('operador')->id(),
                'tipo' => 'problema',
                'periodicidade' => null,
                'data_hora' => now(),
                'status_geral' => 'com_pendencia',
            ]);

            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store("registros/{$registro->id}", 'public');

                $registro->anexos()->create([
                    'formulario_item_id' => null,
                    'caminho_arquivo' => $path,
                ]);
            }

            if (filled($data['descricao'] ?? null)) {
                $registro->respostas()->create([
                    'formulario_item_id' => null,
                    'resposta' => $data['descricao'],
                ]);
            }

            Alerta::create([
                'registro_id' => $registro->id,
                'maquina_id' => $maquina->id,
                'formulario_item_id' => null,
                'operador_id' => Auth::guard('operador')->id(),
                'status' => 'pendente',
                'observacao' => null,
            ]);
        });

        return redirect()
            ->route('maquinas.public.show', $maquina->token)
            ->with('status', 'Problema relatado com sucesso.');
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
