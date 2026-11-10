<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\AlertaStatusHistorico;
use App\Models\Maquina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertaController extends Controller
{
    private const STATUS_ABERTOS = ['pendente', 'em_analise'];

    public function index(Request $request)
    {
        $statusFiltro = in_array($request->status, self::STATUS_ABERTOS, true)
            ? $request->status
            : null;
        $empresaId = $this->isSuperAdmin() && $request->filled('maquina_id')
            ? null
            : $this->empresaAtualId();

        $registroFiltro = preg_replace('/\D+/', '', (string) $request->registro);

        $alertas = Alerta::with(['maquina', 'item', 'operador', 'registro'])
            ->when($empresaId, fn ($query) => $query->whereHas('maquina', fn ($maquinaQuery) => $maquinaQuery->where('empresa_id', $empresaId)))
            ->whereIn('status', self::STATUS_ABERTOS)
            ->when($request->filled('maquina_id'), fn ($query) => $query->where('maquina_id', $request->maquina_id))
            ->when($registroFiltro !== '', fn ($query) => $query->where('registro_id', (int) $registroFiltro))
            ->when($statusFiltro, fn ($query) => $query->where('status', $statusFiltro))
            ->when($request->filled('inicio'), fn ($query) => $query->whereDate('created_at', '>=', $request->inicio))
            ->when($request->filled('fim'), fn ($query) => $query->whereDate('created_at', '<=', $request->fim))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $maquinas = $this->scopeEmpresa(Maquina::query())->orderBy('nome')->get();

        return view('admin.alertas.index', compact('alertas', 'maquinas'));
    }

    public function show(Alerta $alerta)
    {
        $alerta->load('maquina');
        $this->abortUnlessPodeAcessarAlerta($alerta);

        $alerta->load([
            'maquina',
            'item',
            'operador',
            'registro.respostas.item',
            'registro.anexos.item',
            'historicoStatus.admin',
        ]);

        return view('admin.alertas.show', compact('alerta'));
    }

    public function update(Request $request, Alerta $alerta)
    {
        $alerta->load('maquina');
        $this->abortUnlessPodeAcessarAlerta($alerta);

        $data = $request->validate([
            'status' => ['required', 'in:pendente,em_analise,resolvido,falso_positivo'],
            'observacao' => ['nullable', 'string'],
        ]);

        $statusAnterior = $alerta->status;
        $observacaoAnterior = (string) $alerta->observacao;
        $alerta->update($data);
        $observacaoMudou = $observacaoAnterior !== (string) $alerta->observacao;
        $this->atualizarStatusRegistro($alerta);

        if ($statusAnterior !== $alerta->status || $observacaoMudou) {
            AlertaStatusHistorico::create([
                'alerta_id' => $alerta->id,
                'status_anterior' => $statusAnterior,
                'status_novo' => $alerta->status,
                'observacao' => $alerta->observacao,
                'alterado_por' => Auth::guard('admin')->id(),
                'alterado_em' => now(),
            ]);
        }

        if (! in_array($alerta->status, self::STATUS_ABERTOS, true)) {
            if ($request->filled('from_maquina') && $alerta->maquina && (string) $request->from_maquina === (string) $alerta->maquina->id) {
                return redirect(route('admin.maquinas.show', $alerta->maquina).'#alertas-problemas')
                    ->with('status', 'Alerta tratado e mantido no histórico.');
            }

            return redirect()->route('admin.alertas.index')
                ->with('status', 'Alerta tratado e mantido no histórico.');
        }

        return back()->with('status', 'Alerta atualizado.');
    }

    public function destroy(Request $request, Alerta $alerta)
    {
        $alerta->load(['maquina', 'registro']);
        $this->abortUnlessPodeAcessarAlerta($alerta);
        $maquina = $alerta->maquina;

        $alerta->delete();
        $this->atualizarStatusRegistro($alerta);

        if ($request->filled('from_maquina') && $maquina && (string) $request->from_maquina === (string) $maquina->id) {
            return redirect(route('admin.maquinas.show', $maquina).'#alertas-problemas')
                ->with('status', 'Alerta arquivado.');
        }

        return redirect()->route('admin.alertas.index')->with('status', 'Alerta arquivado.');
    }

    private function atualizarStatusRegistro(Alerta $alerta): void
    {
        $registro = $alerta->registro;

        if (! $registro) {
            return;
        }

        $temAlertaAberto = $registro->alertas()
            ->whereIn('status', self::STATUS_ABERTOS)
            ->exists();

        $registro->update([
            'status_geral' => $temAlertaAberto ? 'com_pendencia' : 'conforme',
        ]);
    }

    private function abortUnlessPodeAcessarAlerta(Alerta $alerta): void
    {
        $maquina = $alerta->maquina;

        abort_unless($maquina, 404);

        if ($this->isSuperAdmin() && $maquina->empresa_id) {
            return;
        }

        $this->abortUnlessEmpresa($maquina->empresa_id);
    }
}
