<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\Registro;
use App\Models\RegistroAnexo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistoricoController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $this->isSuperAdmin() && $request->filled('maquina_id')
            ? null
            : $this->empresaAtualId();

        $registros = Registro::with(['maquina', 'operador', 'admin', 'alertas'])
            ->when($empresaId, fn ($query) => $query->whereHas('maquina', fn ($maquinaQuery) => $maquinaQuery->where('empresa_id', $empresaId)))
            ->when($request->filled('maquina_id'), fn ($query) => $query->where('maquina_id', $request->maquina_id))
            ->when($request->filled('codigo_maquina'), function ($query) use ($request) {
                $codigo = trim((string) $request->codigo_maquina);

                $query->whereHas('maquina', fn ($maquina) => $maquina->where('codigo_interno', 'like', "%{$codigo}%"));
            })
            ->when($request->filled('tipo'), fn ($query) => $query->where('tipo', $request->tipo))
            ->when($request->filled('periodicidade'), fn ($query) => $query->where('periodicidade', $request->periodicidade))
            ->when($request->filled('status_geral'), fn ($query) => $query->where('status_geral', $request->status_geral))
            ->when($request->filled('inicio'), fn ($query) => $query->whereDate('data_hora', '>=', $request->inicio))
            ->when($request->filled('fim'), fn ($query) => $query->whereDate('data_hora', '<=', $request->fim))
            ->latest('data_hora')
            ->paginate(20)
            ->withQueryString();

        $maquinas = $this->scopeEmpresa(Maquina::query())->orderBy('nome')->get();

        return view('admin.historico.index', compact('registros', 'maquinas'));
    }

    public function show(Registro $registro)
    {
        $registro->load('maquina');
        $this->abortUnlessPodeAcessarMaquina($registro->maquina);

        $registro->load(['maquina', 'operador', 'admin', 'formulario', 'respostas.item', 'anexos.item', 'alertas.item']);

        return view('admin.historico.show', compact('registro'));
    }

    public function anexo(RegistroAnexo $anexo)
    {
        $anexo->load('registro.maquina');
        $this->abortUnlessPodeAcessarMaquina($anexo->registro?->maquina);
        abort_unless(Storage::disk('public')->exists($anexo->caminho_arquivo), 404);

        return Storage::disk('public')->response($anexo->caminho_arquivo);
    }

    private function abortUnlessPodeAcessarMaquina(?Maquina $maquina): void
    {
        abort_unless($maquina, 404);

        if ($this->isSuperAdmin() && $maquina->empresa_id) {
            return;
        }

        $this->abortUnlessEmpresa($maquina->empresa_id);
    }
}
