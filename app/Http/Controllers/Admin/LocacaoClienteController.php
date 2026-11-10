<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocacaoCliente;
use App\Models\Maquina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocacaoClienteController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['busca', 'status']);

        $clientes = $this->scopeEmpresa(LocacaoCliente::query())
            ->withCount(['maquinas', 'orcamentos'])
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = trim((string) $request->busca);

                $query->where(function ($inner) use ($busca) {
                    $inner->where('nome', 'like', "%{$busca}%")
                        ->orWhere('documento', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%")
                        ->orWhere('telefone', 'like', "%{$busca}%");
                });
            })
            ->when(in_array($request->status, ['ativo', 'inativo'], true), fn ($query) => $query->where('status', $request->status))
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('admin.clientes.index', compact('clientes', 'filtros'));
    }

    public function create()
    {
        return view('admin.clientes.form', [
            'cliente' => new LocacaoCliente(['status' => 'ativo']),
        ]);
    }

    public function show(Request $request, LocacaoCliente $cliente)
    {
        $this->abortUnlessEmpresa($cliente->empresa_id);

        $abas = ['resumo', 'maquinas', 'orcamentos'];
        $aba = in_array($request->query('aba'), $abas, true) ? $request->query('aba') : 'resumo';

        $cliente->loadCount(['maquinas', 'orcamentos']);
        $cliente->load([
            'maquinas' => fn ($query) => $query->with('rastreador')->latest()->take(12),
            'orcamentos' => fn ($query) => $query->with('itens')->latest()->take(12),
        ]);

        return view('admin.clientes.show', compact('cliente', 'aba'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        LocacaoCliente::create($data + [
            'empresa_id' => $this->empresaAtualId(),
        ]);

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente cadastrado.');
    }

    public function edit(LocacaoCliente $cliente)
    {
        $this->abortUnlessEmpresa($cliente->empresa_id);

        return view('admin.clientes.form', compact('cliente'));
    }

    public function update(Request $request, LocacaoCliente $cliente)
    {
        $this->abortUnlessEmpresa($cliente->empresa_id);

        $cliente->update($this->validated($request));

        Maquina::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('locacao_cliente_id', $cliente->id)
            ->update(['locacao_cliente' => $cliente->nome]);

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente atualizado.');
    }

    public function destroy(LocacaoCliente $cliente)
    {
        $this->abortUnlessEmpresa($cliente->empresa_id);

        DB::transaction(function () use ($cliente) {
            Maquina::query()
                ->where('empresa_id', $cliente->empresa_id)
                ->where('locacao_cliente_id', $cliente->id)
                ->update(['locacao_cliente_id' => null]);

            DB::table('operadores')
                ->where('empresa_id', $cliente->empresa_id)
                ->where('locacao_cliente_id', $cliente->id)
                ->update(['locacao_cliente_id' => null]);

            $cliente->delete();
        });

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente removido.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:255'],
            'endereco' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'status' => ['required', 'in:ativo,inativo'],
        ], [
            'nome.required' => 'Informe o nome do cliente.',
            'email.email' => 'Informe um e-mail valido.',
        ]);
    }
}
