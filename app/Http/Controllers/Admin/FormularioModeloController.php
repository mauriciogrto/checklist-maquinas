<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use App\Services\FormularioService;
use App\Support\ChecklistPeriodicidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormularioModeloController extends Controller
{
    public function index(FormularioService $formulariosService)
    {
        $formulariosService->garantirModelosPadrao($this->empresaAtualId());

        $formularios = $this->queryModelos()
            ->withCount('itens')
            ->orderByRaw("CASE periodicidade WHEN 'diario' THEN 1 WHEN 'mensal' THEN 2 WHEN 'trimestral' THEN 3 WHEN 'anual' THEN 4 ELSE 5 END")
            ->orderBy('tipo')
            ->orderBy('nome')
            ->paginate(20);

        return view('admin.formularios-modelo.index', compact('formularios'));
    }

    public function create(Request $request, FormularioService $formularios)
    {
        $tipo = $request->query('tipo') === 'manutencao' ? 'manutencao' : 'modelo_checklist';

        if ($tipo === 'manutencao') {
            $formularios->garantirModelosPadrao($this->empresaAtualId());

            return redirect()->route('admin.formularios-modelo.index');
        }

        return view('admin.formularios-modelo.form', [
            'formulario' => new Formulario(['tipo' => $tipo]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $formulario = Formulario::create([
                'nome' => $data['nome'],
                'empresa_id' => $this->empresaAtualId(),
                'tipo' => $data['tipo'],
                'periodicidade' => null,
                'maquina_id' => null,
            ]);

            $this->syncItens($formulario, $data['itens'] ?? []);
        });

        return redirect()->route('admin.formularios-modelo.index')->with('status', 'Modelo criado.');
    }

    public function show(Formulario $formularios_modelo)
    {
        return redirect()->route('admin.formularios-modelo.edit', $formularios_modelo);
    }

    public function edit(Formulario $formularios_modelo)
    {
        abort_unless($this->isModelo($formularios_modelo), 404);
        $this->abortUnlessEmpresa($formularios_modelo->empresa_id);

        $formularios_modelo->load('itens');
        $protegido = $this->isProtegido($formularios_modelo);

        return view('admin.formularios-modelo.form', [
            'formulario' => $formularios_modelo,
            'protegido' => $protegido,
        ]);
    }

    public function update(Request $request, Formulario $formularios_modelo)
    {
        abort_unless($this->isModelo($formularios_modelo), 404);
        $this->abortUnlessEmpresa($formularios_modelo->empresa_id);

        $data = $this->validated($request);

        DB::transaction(function () use ($formularios_modelo, $data) {
            $formularios_modelo->update([
                'nome' => $data['nome'],
                'empresa_id' => $this->empresaAtualId(),
                'tipo' => $formularios_modelo->tipo,
                'periodicidade' => $formularios_modelo->periodicidade,
                'maquina_id' => null,
            ]);
            $formularios_modelo->itens()->delete();
            $this->syncItens($formularios_modelo, $data['itens'] ?? []);
        });

        return redirect()->route('admin.formularios-modelo.index')->with('status', 'Modelo atualizado.');
    }

    public function destroy(Formulario $formularios_modelo, FormularioService $formularios)
    {
        abort_unless($this->isModelo($formularios_modelo), 404);
        $this->abortUnlessEmpresa($formularios_modelo->empresa_id);

        if ($this->isProtegido($formularios_modelo)) {
            return redirect()
                ->route('admin.formularios-modelo.index')
                ->with('status', 'Este modelo é padrão do sistema e não pode ser excluído.');
        }

        if ($this->isManutencaoLegada($formularios_modelo)) {
            $this->removerManutencaoLegada($formularios_modelo, $formularios);

            return redirect()->route('admin.formularios-modelo.index')->with('status', 'Manutenção padrão antiga removida.');
        }

        if ($formularios_modelo->registros()->exists()) {
            return redirect()
                ->route('admin.formularios-modelo.index')
                ->with('status', 'Não foi possível excluir. Este modelo possui registros históricos vinculados.');
        }

        $maquinas = Formulario::query()
            ->with('maquina')
            ->where('origem_modelo_id', $formularios_modelo->id)
            ->where('tipo', 'checklist_maquina')
            ->whereNotNull('maquina_id')
            ->get()
            ->pluck('maquina.nome')
            ->filter()
            ->unique()
            ->values();

        if ($maquinas->isNotEmpty()) {
            return redirect()
                ->route('admin.formularios-modelo.index')
                ->with('status', 'Não foi possível excluir. Este modelo está atribuído à máquina: '.$maquinas->take(5)->implode(', ').'.');
        }

        $formularios_modelo->delete();

        return redirect()->route('admin.formularios-modelo.index')->with('status', 'Modelo removido.');
    }

    private function queryModelos()
    {
        return $this->scopeEmpresa(Formulario::query())
            ->where(function ($query) {
                $query->where('tipo', 'modelo_checklist')
                    ->orWhere(function ($query) {
                        $query->where('tipo', 'manutencao')
                            ->whereNull('maquina_id');
                    });
            })
            ->where(function ($query) {
                $query->where('tipo', '!=', 'manutencao')
                    ->orWhereNull('periodicidade')
                    ->orWhereIn('periodicidade', array_keys(FormularioService::TIPOS_MANUTENCAO));
            });
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:modelo_checklist,manutencao'],
            'itens' => ['array'],
            'itens.*.pergunta' => ['nullable', 'string'],
            'itens.*.tipo_resposta' => ['nullable', 'in:conformidade,texto,numero,imagem'],
            'itens.*.ordem' => ['nullable', 'integer'],
            'itens.*.dispara_alerta' => ['nullable', 'boolean'],
            'itens.*.obrigatorio' => ['nullable', 'boolean'],
        ]);
    }

    private function syncItens(Formulario $formulario, array $itens): void
    {
        $ordem = 1;

        foreach ($itens as $item) {
            if (blank($item['pergunta'] ?? null)) {
                continue;
            }

            $formulario->itens()->create([
                'pergunta' => $item['pergunta'],
                'tipo_resposta' => $item['tipo_resposta'] ?? 'conformidade',
                'ordem' => $ordem++,
                'dispara_alerta' => $formulario->tipo === 'manutencao' ? false : (bool) ($item['dispara_alerta'] ?? false),
                'obrigatorio' => (bool) ($item['obrigatorio'] ?? true),
            ]);
        }
    }

    private function isModelo(Formulario $formulario): bool
    {
        return $formulario->tipo === 'modelo_checklist'
            || ($formulario->tipo === 'manutencao' && $formulario->maquina_id === null);
    }

    private function isProtegido(Formulario $formulario): bool
    {
        return ($formulario->tipo === 'manutencao' && array_key_exists((string) $formulario->periodicidade, FormularioService::TIPOS_MANUTENCAO))
            || ($formulario->tipo === 'modelo_checklist' && ChecklistPeriodicidade::isPadrao((string) $formulario->periodicidade));
    }

    private function isManutencaoLegada(Formulario $formulario): bool
    {
        return $formulario->tipo === 'manutencao'
            && $formulario->periodicidade === null
            && $formulario->maquina_id === null;
    }

    private function removerManutencaoLegada(Formulario $formulario, FormularioService $formularios): void
    {
        $destino = $formularios->modeloManutencaoPadrao($formulario->empresa_id, 'preventiva');
        $itensDestino = $destino->itens->keyBy(fn ($item) => $this->chaveItemManutencao($item->pergunta));

        DB::transaction(function () use ($formulario, $destino, $itensDestino) {
            $formulario->load('itens');

            foreach ($formulario->itens as $item) {
                $destinoItemId = $itensDestino->get($this->chaveItemManutencao($item->pergunta))?->id;

                DB::table('registro_respostas')
                    ->where('formulario_item_id', $item->id)
                    ->update(['formulario_item_id' => $destinoItemId]);

                DB::table('registro_anexos')
                    ->where('formulario_item_id', $item->id)
                    ->update(['formulario_item_id' => $destinoItemId]);

                DB::table('alertas')
                    ->where('formulario_item_id', $item->id)
                    ->update(['formulario_item_id' => $destinoItemId]);
            }

            DB::table('registros')
                ->where('formulario_id', $formulario->id)
                ->update([
                    'formulario_id' => $destino->id,
                    'periodicidade' => DB::raw("COALESCE(periodicidade, 'preventiva')"),
                ]);

            DB::table('maquinas')
                ->where('formulario_manutencao_id', $formulario->id)
                ->update(['formulario_manutencao_id' => $destino->id]);

            DB::table('formulario_itens')->where('formulario_id', $formulario->id)->delete();
            $formulario->delete();
        });
    }

    private function chaveItemManutencao(?string $pergunta): ?string
    {
        $texto = str((string) $pergunta)->lower()->ascii();

        return match (true) {
            $texto->contains('horimetro') => 'horimetro',
            $texto->contains('descricao') || $texto->contains('intervencao') || $texto->contains('foi feito') => 'descricao',
            $texto->contains('profissional') || $texto->contains('tecnico') || $texto->contains('empresa') => 'profissional',
            $texto->contains('assinatura') || $texto->contains('responsavel') => 'assinatura',
            default => null,
        };
    }
}
