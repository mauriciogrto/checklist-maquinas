<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistReset;
use App\Models\Maquina;
use App\Models\Registro;
use App\Services\FormularioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChecklistTecnicoController extends Controller
{
    public function create(Maquina $maquina, string $periodicidade)
    {
        $this->abortUnlessEmpresa($maquina->empresa_id);

        $this->abortIfPeriodicidadeInvalida($periodicidade);
        $formulario = $maquina->formularioPorPeriodicidade($periodicidade)?->load('itens');

        abort_if(! $formulario, 404, 'Checklist não configurado para esta máquina.');

        if ($this->checklistJaRegistrado($maquina, $periodicidade)) {
            return redirect()
                ->route('admin.maquinas.show', $maquina)
                ->with('status', $this->mensagemChecklistJaRegistrado($periodicidade));
        }

        return view('admin.checklists.form', compact('maquina', 'formulario', 'periodicidade'));
    }

    public function createManutencao(Maquina $maquina, FormularioService $formularios)
    {
        $this->abortUnlessEmpresa($maquina->empresa_id);

        $formulario = $formularios->modeloManutencaoPadrao($maquina->empresa_id);

        return view('admin.checklists.form', [
            'maquina' => $maquina,
            'formulario' => $formulario,
            'periodicidade' => null,
            'tipo' => 'manutencao',
            'titulo' => 'Manutenção',
            'actionRoute' => route('admin.maquinas.manutencao.store', $maquina),
        ]);
    }

    public function store(Request $request, Maquina $maquina, string $periodicidade)
    {
        $this->abortUnlessEmpresa($maquina->empresa_id);

        $this->abortIfPeriodicidadeInvalida($periodicidade);
        $formulario = $maquina->formularioPorPeriodicidade($periodicidade)?->load('itens');

        abort_if(! $formulario, 404, 'Checklist não configurado para esta máquina.');

        $this->bloquearChecklistJaRegistrado($maquina, $periodicidade);

        $this->validateDynamicForm($request, $formulario->itens);

        DB::transaction(function () use ($request, $maquina, $formulario, $periodicidade) {
            $this->bloquearChecklistJaRegistrado($maquina, $periodicidade);

            $registro = Registro::create([
                'maquina_id' => $maquina->id,
                'formulario_id' => $formulario->id,
                'admin_id' => Auth::guard('admin')->id(),
                'tipo' => 'checklist',
                'periodicidade' => $periodicidade,
                'data_hora' => now(),
                'status_geral' => 'conforme',
            ]);

            $possuiPendencia = false;
            $respostas = $request->input('respostas', []);
            $anexos = $request->file('anexos', []);
            $evidenciasTexto = $request->input('evidencias_texto', []);
            $evidenciasFoto = $request->file('evidencias_foto', []);

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

                $resposta = $respostas[$item->id] ?? null;

                $registro->respostas()->create([
                    'formulario_item_id' => $item->id,
                    'resposta' => $resposta,
                ]);

                if ($item->tipo_resposta === 'conformidade' && $resposta === 'nao_conforme') {
                    $possuiPendencia = true;

                    if (filled($evidenciasTexto[$item->id] ?? null)) {
                        $registro->respostas()->create([
                            'formulario_item_id' => $item->id,
                            'resposta' => 'Observação do não conforme: '.$evidenciasTexto[$item->id],
                        ]);
                    }

                    if (isset($evidenciasFoto[$item->id])) {
                        $path = $evidenciasFoto[$item->id]->store("registros/{$registro->id}", 'public');
                        $registro->anexos()->create([
                            'formulario_item_id' => $item->id,
                            'caminho_arquivo' => $path,
                        ]);
                    }
                }
            }

            if ($possuiPendencia) {
                $registro->update(['status_geral' => 'com_pendencia']);
            }
        });

        return redirect()
            ->route('admin.maquinas.show', $maquina)
            ->with('status', 'Checklist '.$periodicidade.' registrado.');
    }

    public function storeManutencao(Request $request, Maquina $maquina, FormularioService $formularios)
    {
        $this->abortUnlessEmpresa($maquina->empresa_id);

        $formulario = $formularios->modeloManutencaoPadrao($maquina->empresa_id);

        $this->validateDynamicForm($request, $formulario->itens);

        DB::transaction(function () use ($request, $maquina, $formulario) {
            $registro = Registro::create([
                'maquina_id' => $maquina->id,
                'formulario_id' => $formulario->id,
                'admin_id' => Auth::guard('admin')->id(),
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
            ->route('admin.maquinas.show', $maquina)
            ->with('status', 'Manutenção registrada.');
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

            if ($item->tipo_resposta === 'conformidade') {
                $rules["evidencias_texto.{$item->id}"] = ['nullable', 'string', 'max:2000'];
                $rules["evidencias_foto.{$item->id}"] = $this->evidenciaArquivoRules();
            }
        }

        $request->validate($rules);
    }

    private function evidenciaArquivoRules(): array
    {
        return [
            'nullable',
            'file',
            'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm',
            'max:51200',
        ];
    }

    private function abortIfPeriodicidadeInvalida(string $periodicidade): void
    {
        abort_unless(in_array($periodicidade, ['trimestral', 'anual'], true), 404);
    }

    private function bloquearChecklistJaRegistrado(Maquina $maquina, string $periodicidade): void
    {
        if (! $this->checklistJaRegistrado($maquina, $periodicidade)) {
            return;
        }

        throw ValidationException::withMessages([
            'checklist' => $this->mensagemChecklistJaRegistrado($periodicidade),
        ]);
    }

    private function checklistJaRegistrado(Maquina $maquina, string $periodicidade): bool
    {
        $ciclo = $this->cicloAtualChecklist($periodicidade, $maquina->created_at, now());
        $reset = ChecklistReset::query()
            ->where('maquina_id', $maquina->id)
            ->where('periodicidade', $periodicidade)
            ->whereBetween('resetado_em', [$ciclo['inicio'], $ciclo['fim']])
            ->latest('resetado_em')
            ->first();

        return Registro::query()
            ->where('maquina_id', $maquina->id)
            ->where('tipo', 'checklist')
            ->where('periodicidade', $periodicidade)
            ->whereBetween('data_hora', [$ciclo['inicio'], $ciclo['fim']])
            ->when($reset, fn ($query) => $query->where('data_hora', '>', $reset->resetado_em))
            ->exists();
    }

    private function mensagemChecklistJaRegistrado(string $periodicidade): string
    {
        $labels = [
            'trimestral' => 'trimestral',
            'anual' => 'anual',
        ];

        return 'O checklist '.($labels[$periodicidade] ?? $periodicidade).' desta máquina já foi registrado no ciclo atual.';
    }

    private function cicloAtualChecklist(string $periodicidade, $cadastro, $agora): array
    {
        $meses = match ($periodicidade) {
            'trimestral' => 3,
            'anual' => 12,
            default => 1,
        };

        $inicio = $cadastro->copy()->startOfDay();

        while ($inicio->copy()->addMonthsNoOverflow($meses)->lessThanOrEqualTo($agora)) {
            $inicio = $inicio->copy()->addMonthsNoOverflow($meses);
        }

        return [
            'inicio' => $inicio,
            'fim' => $inicio->copy()->addMonthsNoOverflow($meses)->subSecond(),
        ];
    }
}
