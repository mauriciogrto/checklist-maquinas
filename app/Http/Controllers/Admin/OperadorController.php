<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocacaoCliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperadorController extends Controller
{
    public function index()
    {
        $operadores = $this->scopeEmpresa(Operador::query())
            ->with('cliente')
            ->latest()
            ->paginate(20);

        return view('admin.operadores.index', compact('operadores'));
    }

    public function create(Request $request)
    {
        $clienteId = $request->integer('cliente');
        $clienteSelecionado = $clienteId
            ? $this->scopeEmpresa(LocacaoCliente::query())->where('status', 'ativo')->whereKey($clienteId)->value('id')
            : null;

        return view('admin.operadores.form', [
            'operador' => new Operador([
                'status' => 'ativo',
                'locacao_cliente_id' => $clienteSelecionado,
            ]),
            'clientes' => $this->clientes(),
            'retornoClienteId' => $clienteSelecionado,
        ]);
    }

    public function store(Request $request)
    {
        $empresa = $this->empresaAtual();

        if ($empresa && Operador::query()->where('empresa_id', $empresa->id)->count() >= $empresa->limite_operadores) {
            return back()->withErrors([
                'usuario' => 'Limite atingido: esta empresa já possui o máximo de operadores contratado.',
            ])->withInput();
        }

        $operador = Operador::create($this->validated($request) + ['empresa_id' => $this->empresaAtualId()]);

        if ($request->integer('retorno_cliente_id')) {
            return redirect()
                ->route('admin.clientes.show', ['cliente' => $operador->locacao_cliente_id ?: $request->integer('retorno_cliente_id'), 'aba' => 'operadores'])
                ->with('status', 'Operador criado.');
        }

        return redirect()->route('admin.operadores.index')->with('status', 'Operador criado.');
    }

    public function edit(Operador $operadore)
    {
        $this->abortUnlessEmpresa($operadore->empresa_id);

        return view('admin.operadores.form', [
            'operador' => $operadore,
            'clientes' => $this->clientes(),
            'retornoClienteId' => null,
        ]);
    }

    public function update(Request $request, Operador $operadore)
    {
        $this->abortUnlessEmpresa($operadore->empresa_id);

        $data = $this->validated($request, $operadore);

        if (empty($data['senha'])) {
            unset($data['senha']);
        }

        $operadore->update($data);

        return redirect()->route('admin.operadores.index')->with('status', 'Operador atualizado.');
    }

    public function destroy(Operador $operadore)
    {
        $this->abortUnlessEmpresa($operadore->empresa_id);

        $operadore->delete();

        return redirect()->route('admin.operadores.index')->with('status', 'Operador excluído.');
    }

    private function validated(Request $request, ?Operador $operador = null): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'usuario' => [
                'required',
                'string',
                'max:255',
                Rule::unique('operadores', 'usuario')
                    ->where('empresa_id', $this->empresaAtualId())
                    ->whereNull('deleted_at')
                    ->ignore($operador),
            ],
            'senha' => [$operador ? 'nullable' : 'required', 'string', 'min:6'],
            'status' => ['required', 'in:ativo,inativo'],
            'locacao_cliente_id' => ['nullable', 'integer', 'exists:locacao_clientes,id'],
        ], [
            'nome.required' => 'Informe o nome do operador.',
            'usuario.required' => 'Informe o usuário de acesso do operador.',
            'usuario.unique' => 'Esse usuário já está cadastrado. Escolha outro.',
            'senha.required' => 'Informe uma senha para o operador.',
            'senha.min' => 'A senha precisa ter pelo menos :min caracteres.',
            'status.required' => 'Selecione o status do operador.',
            'status.in' => 'Selecione um status válido para o operador.',
        ]);

        if (! empty($data['locacao_cliente_id'])) {
            $this->scopeEmpresa(LocacaoCliente::query())->findOrFail($data['locacao_cliente_id']);
        } else {
            $data['locacao_cliente_id'] = null;
        }

        return $data;
    }

    private function clientes()
    {
        return $this->scopeEmpresa(LocacaoCliente::query())
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();
    }
}
