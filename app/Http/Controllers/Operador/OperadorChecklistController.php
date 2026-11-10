<?php

namespace App\Http\Controllers\Operador;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\ChecklistReset;
use App\Models\Formulario;
use App\Models\Maquina;
use App\Models\Registro;
use App\Support\ChecklistPeriodicidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChecklistController extends Controller
{
    public function create(string $token, string $periodicidade)
    {
        $this->abortIfPeriodicidadeInvalida($periodicidade);
        $maquina = $this->maquina($token);
        $formulario = $maquina->formularioPorPeriodicidade($periodicidade)?->load('itens');

        abort_if(! $formulario, 404, 'Checklist não configurado para esta máquina.');

        if ($this->checklistJaRegistrado($maquina, $periodicidade)) {
            return redirect()
                ->route('maquinas.public.show', $maquina->token)
                ->with('status', $this->mensagemChecklistJaRegistrado($periodicidade));
        }

        return view('operador.formulario', [
            'maquina' => $maquina,
            'formulario' => $formulario,
            'tipo' => 'checklist',
            'periodicidade' => $periodicidade,
        ]);
    }

    public function store(Request $request, string $token, string $periodicidade)
    {
        $this->abortIfPeriodicidadeInvalida($periodicidade);
        $maquina = $this->maquina($token);
        $formulario = $maquina->formularioPorPeriodicidade($periodicidade)?->load('itens');

        abort_if(! $formulario, 404, 'Checklist não configurado para esta máquina.');

        return $this->registrarChecklist($request, $maquina, $formulario, $periodicidade, true);
    }

    public function createExtra(string $token, Formulario $formulario)
    {
        $maquina = $this->maquina($token);
        $formulario = $this->formularioExtraDaMaquina($maquina, $formulario)->load('itens');
        $periodicidade = $formulario->periodicidade ?: 'extra_diario';

        if ($this->checklistJaRegistrado($maquina, $periodicidade, $formulario)) {
            return redirect()
                ->route('maquinas.public.show', $maquina->token)
                ->with('status', $this->mensagemChecklistJaRegistrado($periodicidade));
        }

        return view('operador.formulario', [
            'maquina' => $maquina,
            'formulario' => $formulario,
            'tipo' => 'checklist',
            'periodicidade' => $periodicidade,
            'actionRoute' => route('maquinas.public.checklist-extra.store', [$maquina->token, $formulario]),
        ]);
    }

    public function storeExtra(Request $request, string $token, Formulario $formulario)
    {
        $maquina = $this->maquina($token);
        $formulario = $this->formularioExtraDaMaquina($maquina, $formulario)->load('itens');
        $periodicidade = $formulario->periodicidade ?: 'extra_diario';

        return $this->registrarChecklist($request, $maquina, $formulario, $periodicidade, true);
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
        $evidenciasFoto = $request->file('evidencias_foto', []);

        foreach ($itens as $item) {
            if ($item->tipo_resposta !== 'conformidade') {
                continue;
            }

            if ($request->input("respostas.{$item->id}") !== 'nao_conforme') {
                continue;
            }

            if (filled($request->input("evidencias_texto.{$item->id}")) || (isset($evidenciasFoto[$item->id]) && $evidenciasFoto[$item->id]->isValid())) {
                continue;
            }

            throw ValidationException::withMessages([
                "evidencias_texto.{$item->id}" => 'Informe uma observação ou envie uma foto/vídeo quando marcar Não conforme.',
            ]);
        }
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

    private function maquina(string $token): Maquina
    {
        $maquina = Maquina::query()
            ->with('formularios')
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

    private function formularioExtraDaMaquina(Maquina $maquina, Formulario $formulario): Formulario
    {
        abort_unless(
            $formulario->maquina_id === $maquina->id
            && $formulario->empresa_id === $maquina->empresa_id
            && $formulario->tipo === 'checklist_maquina'
            && ChecklistPeriodicidade::isExtra($formulario->periodicidade),
            404
        );

        return $formulario;
    }

    private function registrarChecklist(Request $request, Maquina $maquina, Formulario $formulario, string $periodicidade, bool $bloquearCiclo): \Illuminate\Http\RedirectResponse
    {
        if ($bloquearCiclo) {
            $this->bloquearChecklistJaRegistrado($maquina, $periodicidade, $formulario);
        }

        $this->validateDynamicForm($request, $formulario->itens);

        DB::transaction(function () use ($request, $maquina, $formulario, $periodicidade, $bloquearCiclo) {
            if ($bloquearCiclo) {
                $this->bloquearChecklistJaRegistrado($maquina, $periodicidade, $formulario);
            }

            $registro = Registro::create([
                'maquina_id' => $maquina->id,
                'formulario_id' => $formulario->id,
                'operador_id' => Auth::guard('operador')->id(),
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

                    if ($item->dispara_alerta) {
                        Alerta::create([
                            'registro_id' => $registro->id,
                            'maquina_id' => $maquina->id,
                            'formulario_item_id' => $item->id,
                            'operador_id' => Auth::guard('operador')->id(),
                            'status' => 'pendente',
                        ]);
                    }
                }
            }

            if ($possuiPendencia) {
                $registro->update(['status_geral' => 'com_pendencia']);
            }
        });

        return redirect()
            ->route('maquinas.public.show', $maquina->token)
            ->with('status', 'Checklist registrado com sucesso.');
    }

    private function abortIfPeriodicidadeInvalida(string $periodicidade): void
    {
        abort_unless(in_array($periodicidade, ['diario', 'mensal'], true), 404);
    }

    private function bloquearChecklistJaRegistrado(Maquina $maquina, string $periodicidade, ?Formulario $formulario = null): void
    {
        if (! $this->checklistJaRegistrado($maquina, $periodicidade, $formulario)) {
            return;
        }

        throw ValidationException::withMessages([
            'checklist' => $this->mensagemChecklistJaRegistrado($periodicidade),
        ]);
    }

    private function checklistJaRegistrado(Maquina $maquina, string $periodicidade, ?Formulario $formulario = null): bool
    {
        $cadastro = $formulario?->created_at ?? $maquina->created_at;
        $ciclo = ChecklistPeriodicidade::ciclo($periodicidade, $cadastro, now());
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
            ->when($formulario, fn ($query) => $query->where('formulario_id', $formulario->id))
            ->whereBetween('data_hora', [$ciclo['inicio'], $ciclo['fim']])
            ->when($reset, fn ($query) => $query->where('data_hora', '>', $reset->resetado_em))
            ->exists();
    }

    private function mensagemChecklistJaRegistrado(string $periodicidade): string
    {
        if ($periodicidade === 'diario') {
            return 'O checklist diário desta máquina já foi registrado hoje. Novo envio libera amanhã.';
        }

        if ($periodicidade === 'mensal') {
            return 'O checklist mensal desta máquina já foi registrado no ciclo atual.';
        }

        return 'Este checklist extra já foi registrado no ciclo atual.';
    }
}
