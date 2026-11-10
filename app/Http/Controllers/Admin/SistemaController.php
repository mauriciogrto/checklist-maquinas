<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Empresa;
use App\Models\SecurityLog;
use App\Models\SistemaCobranca;
use App\Models\SistemaConfiguracao;
use App\Models\SistemaProposta;
use App\Services\SistemaPropostaPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ZipArchive;

class SistemaController extends Controller
{
    public function index()
    {
        $this->abortUnlessSuperAdmin();

        return redirect()->route('admin.sistema.empresas');
    }

    public function empresas()
    {
        $this->abortUnlessSuperAdmin();

        return view('admin.sistema.index', $this->viewData('empresas'));
    }

    public function showEmpresa(Request $request, Empresa $empresa)
    {
        $this->abortUnlessSuperAdmin();

        $abas = ['dados', 'modulos', 'usuarios', 'propostas'];
        $abaEmpresa = in_array($request->query('aba'), $abas, true) ? $request->query('aba') : 'dados';

        $empresa->load([
            'admins' => fn ($query) => $query->orderBy('nome'),
            'sistemaPropostas',
        ])->loadCount(['maquinas', 'operadores', 'admins', 'sistemaPropostas']);

        return view('admin.sistema.index', array_merge($this->viewData('empresa'), [
            'empresa' => $empresa,
            'abaEmpresa' => $abaEmpresa,
        ]));
    }

    public function create()
    {
        $this->abortUnlessSuperAdmin();

        return view('admin.sistema.index', $this->viewData('cadastrar'));
    }

    public function backupPage()
    {
        $this->abortUnlessSuperAdmin();

        return view('admin.sistema.index', $this->viewData('backup'));
    }

    public function logs(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        $filtros = $request->validate([
            'risco' => ['nullable', 'in:baixo,medio,alto,critico'],
            'evento' => ['nullable', 'string', 'max:80'],
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date'],
        ]);

        $logs = SecurityLog::query()
            ->with(['admin', 'operador', 'empresa'])
            ->when($filtros['risco'] ?? null, fn ($query, $risco) => $query->where('risco', $risco))
            ->when($filtros['evento'] ?? null, fn ($query, $evento) => $query->where('evento', $evento))
            ->when($filtros['empresa_id'] ?? null, fn ($query, $empresaId) => $query->where('empresa_id', $empresaId))
            ->when($filtros['inicio'] ?? null, fn ($query, $inicio) => $query->whereDate('created_at', '>=', $inicio))
            ->when($filtros['fim'] ?? null, fn ($query, $fim) => $query->whereDate('created_at', '<=', $fim))
            ->latest('created_at')
            ->paginate(40)
            ->appends($request->query());

        return view('admin.sistema.index', array_merge($this->viewData('logs'), [
            'securityLogs' => $logs,
            'securityLogEventos' => SecurityLog::query()
                ->select('evento')
                ->distinct()
                ->orderBy('evento')
                ->pluck('evento'),
        ]));
    }

    public function proposta()
    {
        $this->abortUnlessSuperAdmin();

        return view('admin.sistema.index', $this->viewData('proposta'));
    }

    public function propostaPdf(Request $request, SistemaPropostaPdfService $pdf)
    {
        $this->abortUnlessSuperAdmin();

        $data = $request->validate([
            'cliente_nome' => ['required', 'string', 'max:255'],
            'cliente_documento' => ['nullable', 'string', 'max:255'],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'contato_email' => ['nullable', 'email', 'max:255'],
            'contato_telefone' => ['nullable', 'string', 'max:255'],
            'quantidade_rastreadores' => ['required', 'integer', 'min:0', 'max:9999'],
            'valor_rastreador' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_sistema_mensal' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_comodato_mensal' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_instalacao_unitaria' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'condicoes_pagamento' => ['nullable', 'string', 'max:500'],
            'vencimento_mensal_dia' => ['required', 'integer', 'min:1', 'max:31'],
            'prazo_contrato_meses' => ['nullable', 'integer', 'min:1', 'max:120'],
            'validade_dias' => ['nullable', 'integer', 'min:1', 'max:90'],
            'observacoes' => ['nullable', 'string', 'max:3000'],
        ], [
            'cliente_nome.required' => 'Informe o cliente da proposta.',
            'contato_email.email' => 'Informe um e-mail válido para contato.',
        ]);

        return response($pdf->render($data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($data).'"',
        ]);
    }

    public function storeProposta(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        $data = $this->validarProposta($request, true);
        $empresa = Empresa::query()->findOrFail($data['empresa_id']);
        $data['status'] = $request->input('status', 'rascunho');
        $data['cliente_nome'] = ($data['cliente_nome'] ?? null) ?: $empresa->nome;
        $data['cliente_documento'] = ($data['cliente_documento'] ?? null) ?: $empresa->documento;

        if ($data['status'] === 'enviada') {
            $data['enviada_em'] = now();
        }

        SistemaProposta::create($data);

        return redirect()
            ->route('admin.sistema.empresas.show', ['empresa' => $empresa, 'aba' => 'propostas'])
            ->with('status', 'Proposta salva na empresa.');
    }

    public function propostas(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        return redirect()->route('admin.sistema.empresas')->with('status', 'As propostas salvas agora ficam dentro de cada empresa.');

        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:rascunho,enviada,aceita,recusada'],
        ]);

        $propostas = SistemaProposta::query()
            ->when($filtros['busca'] ?? null, function ($query, $busca) {
                $query->where(function ($inner) use ($busca) {
                    $inner->where('cliente_nome', 'like', '%'.$busca.'%')
                        ->orWhere('cliente_documento', 'like', '%'.$busca.'%')
                        ->orWhere('contato_nome', 'like', '%'.$busca.'%');
                });
            })
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.sistema.index', array_merge($this->viewData('propostas'), [
            'propostas' => $propostas,
            'propostaStatus' => SistemaProposta::STATUS,
        ]));
    }

    public function propostaSalvaPdf(SistemaProposta $proposta, SistemaPropostaPdfService $pdf)
    {
        $this->abortUnlessSuperAdmin();

        return response($pdf->render($proposta), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->filename($proposta).'"',
        ]);
    }

    public function updatePropostaStatus(Request $request, SistemaProposta $proposta)
    {
        $this->abortUnlessSuperAdmin();

        $data = $request->validate([
            'status' => ['required', 'in:rascunho,enviada,aceita,recusada'],
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'enviada' && ! $proposta->enviada_em) {
            $updates['enviada_em'] = now();
        }

        if (in_array($data['status'], ['aceita', 'recusada'], true)) {
            $updates['respondida_em'] = now();
        }

        $proposta->update($updates);

        return back()->with('status', 'Status da proposta atualizado.');
    }

    public function destroyProposta(SistemaProposta $proposta)
    {
        $this->abortUnlessSuperAdmin();

        $proposta->delete();

        return back()->with('status', 'Proposta removida.');
    }

    public function cobrancas(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        return redirect()->route('admin.maquinas.index')->with('status', 'As cobranças agora ficam dentro de cada máquina.');

        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:aberta,paga,vencida,cancelada'],
            'referencia_mes' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $cobrancas = SistemaCobranca::query()
            ->when($filtros['busca'] ?? null, fn ($query, $busca) => $query->where('cliente_nome', 'like', '%'.$busca.'%'))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filtros['referencia_mes'] ?? null, fn ($query, $mes) => $query->whereDate('referencia_mes', $mes.'-01'))
            ->orderBy('vencimento')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.sistema.index', array_merge($this->viewData('cobrancas'), [
            'cobrancas' => $cobrancas,
            'cobrancaStatus' => SistemaCobranca::STATUS,
        ]));
    }

    public function storeCobranca(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        SistemaCobranca::create($this->validarCobranca($request));

        return back()->with('status', 'Cobrança registrada.');
    }

    public function updateCobranca(Request $request, SistemaCobranca $cobranca)
    {
        $this->abortUnlessSuperAdmin();

        $data = $request->validate([
            'status' => ['required', 'in:aberta,paga,vencida,cancelada'],
            'pago_em' => ['nullable', 'date'],
        ]);

        if ($data['status'] === 'paga' && empty($data['pago_em'])) {
            $data['pago_em'] = now()->toDateString();
        }

        if ($data['status'] !== 'paga') {
            $data['pago_em'] = null;
        }

        $cobranca->update($data);

        return back();
    }

    public function destroyCobranca(SistemaCobranca $cobranca)
    {
        $this->abortUnlessSuperAdmin();

        $cobranca->delete();

        return back()->with('status', 'Cobrança removida.');
    }

    private function validarProposta(Request $request, bool $exigirEmpresa = false): array
    {
        return $request->validate([
            'empresa_id' => [$exigirEmpresa ? 'required' : 'nullable', 'integer', 'exists:empresas,id'],
            'cliente_nome' => [$exigirEmpresa ? 'nullable' : 'required', 'string', 'max:255'],
            'cliente_documento' => ['nullable', 'string', 'max:255'],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'contato_email' => ['nullable', 'email', 'max:255'],
            'contato_telefone' => ['nullable', 'string', 'max:255'],
            'quantidade_rastreadores' => ['required', 'integer', 'min:0', 'max:9999'],
            'valor_rastreador' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_sistema_mensal' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_comodato_mensal' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_treinamento' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_instalacao' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'valor_deslocamento' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'condicoes_pagamento' => ['nullable', 'string', 'max:500'],
            'vencimento_mensal_dia' => ['required', 'integer', 'min:1', 'max:31'],
            'prazo_contrato_meses' => ['nullable', 'integer', 'min:1', 'max:120'],
            'validade_dias' => ['nullable', 'integer', 'min:1', 'max:90'],
            'observacoes' => ['nullable', 'string', 'max:3000'],
            'status' => ['nullable', 'in:rascunho,enviada,aceita,recusada'],
        ], [
            'empresa_id.required' => 'Selecione a empresa vinculada à proposta.',
            'cliente_nome.required' => 'Informe o cliente da proposta.',
            'contato_email.email' => 'Informe um e-mail valido para contato.',
        ]);
    }

    private function validarCobranca(Request $request): array
    {
        $data = $request->validate([
            'cliente_nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'referencia_mes' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'vencimento' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', 'in:aberta,paga,vencida,cancelada'],
            'forma_pagamento' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ], [
            'cliente_nome.required' => 'Informe o cliente da cobrança.',
            'vencimento.required' => 'Informe a data de vencimento.',
        ]);

        $data['referencia_mes'] = ! empty($data['referencia_mes']) ? $data['referencia_mes'].'-01' : null;
        $data['pago_em'] = ($data['status'] ?? null) === 'paga' ? now()->toDateString() : null;

        return $data;
    }

    private function viewData(string $pagina): array
    {
        return [
            'paginaSistema' => $pagina,
            'empresas' => Empresa::with(['maquinas' => fn ($query) => $query->orderBy('nome')])
                ->withCount(['maquinas', 'operadores', 'admins'])
                ->orderBy('nome')
                ->get(),
            'admins' => Admin::with('empresa')->orderBy('nome')->get(),
            'empresaAtual' => $this->empresaAtual(),
            'manutencaoAtiva' => SistemaConfiguracao::manutencaoAtiva(),
        ];
    }

    public function storeEmpresa(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:ativa,inativa'],
            'limite_admins' => ['required', 'integer', 'min:0', 'max:9999'],
            'limite_operadores' => ['required', 'integer', 'min:0', 'max:9999'],
            'limite_maquinas' => ['required', 'integer', 'min:0', 'max:9999'],
            'mensalidade_em_dia' => ['required', 'boolean'],
            'plano_periodicidade' => ['required', 'in:mensal,trimestral,anual'],
            'plano_vencimento' => ['nullable', 'date'],
            'modulo_rastreamento' => ['nullable', 'boolean'],
            'modulos' => ['nullable', 'array'],
            'modulos.rastreamento' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'admin_nome' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')],
            'admin_senha' => ['required', 'string', 'min:6'],
            'admin_perfil' => ['nullable', 'in:admin,comercial'],
        ]);

        $data['plano_vencimento'] ??= $this->vencimentoPadrao($data['plano_periodicidade']);
        $data['modulo_rastreamento'] = $request->boolean('modulos.rastreamento') || $request->boolean('modulo_rastreamento');
        $adminData = [
            'nome' => $data['admin_nome'],
            'email' => $data['admin_email'],
            'senha' => $data['admin_senha'],
            'perfil' => $data['admin_perfil'] ?? 'admin',
        ];

        unset($data['admin_nome'], $data['admin_email'], $data['admin_senha'], $data['admin_perfil'], $data['modulos'], $data['logo']);

        if ((int) $data['limite_admins'] < 1) {
            return back()->withErrors([
                'limite_admins' => 'Informe pelo menos 1 dono para cadastrar o administrador da empresa.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $data, $adminData) {
            $empresa = Empresa::create($data);
            $this->sincronizarLogoEmpresa($request, $empresa);

            Admin::create([
                'empresa_id' => $empresa->id,
                'nome' => $adminData['nome'],
                'email' => $adminData['email'],
                'senha' => $adminData['senha'],
                'is_superadmin' => false,
                'perfil' => $adminData['perfil'],
            ]);
        });

        return redirect()->route('admin.sistema.empresas')->with('status', 'Empresa e administrador criados.');
    }

    public function updateEmpresa(Request $request, Empresa $empresa)
    {
        $this->abortUnlessSuperAdmin();

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:ativa,inativa'],
            'limite_admins' => ['required', 'integer', 'min:0', 'max:9999'],
            'limite_operadores' => ['required', 'integer', 'min:0', 'max:9999'],
            'limite_maquinas' => ['required', 'integer', 'min:0', 'max:9999'],
            'mensalidade_em_dia' => ['required', 'boolean'],
            'plano_periodicidade' => ['required', 'in:mensal,trimestral,anual'],
            'plano_vencimento' => ['nullable', 'date'],
            'modulo_rastreamento' => ['nullable', 'boolean'],
            'modulos' => ['nullable', 'array'],
            'modulos.rastreamento' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remover_logo' => ['nullable', 'boolean'],
        ]);

        $data['modulo_rastreamento'] = $request->boolean('modulos.rastreamento') || $request->boolean('modulo_rastreamento');
        unset($data['modulos'], $data['logo'], $data['remover_logo']);

        DB::transaction(function () use ($request, $empresa, $data) {
            $empresa->update($data);
            $this->sincronizarLogoEmpresa($request, $empresa);
        });

        return back()->with('status', 'Empresa atualizada.');
    }

    public function destroyEmpresa(Empresa $empresa)
    {
        $this->abortUnlessSuperAdmin();

        DB::transaction(function () use ($empresa) {
            $this->removerDadosEmpresa($empresa);
            $empresa->delete();
        });

        return back()->with('status', 'Empresa e dados vinculados excluídos.');
    }


    public function storeAdmin(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        $data = $this->validarAdmin($request);

        $empresa = Empresa::query()->findOrFail($data['empresa_id']);

        if ($empresa->admins()->count() >= $empresa->limite_admins) {
            return back()->withErrors([
                'empresa_id' => 'Limite atingido: esta empresa já possui o máximo de donos/administradores contratado.',
            ])->withInput();
        }

        Admin::create([
            'empresa_id' => $data['empresa_id'],
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha' => $data['senha'],
            'is_superadmin' => false,
            'perfil' => $data['perfil'],
        ]);

        return back()->with('status', $data['perfil'] === 'comercial' ? 'Usuário comercial criado.' : 'Administrador criado.');
    }

    public function updateAdmin(Request $request, Admin $admin)
    {
        $this->abortUnlessSuperAdmin();
        abort_if($admin->is_superadmin, 404);

        $data = $this->validarAdmin($request, $admin);
        $empresa = Empresa::query()->findOrFail($data['empresa_id']);

        if ((int) $admin->empresa_id !== (int) $empresa->id && $empresa->admins()->count() >= $empresa->limite_admins) {
            return back()->withErrors([
                'empresa_id' => 'Limite atingido: esta empresa já possui o máximo de usuários contratado.',
            ])->withInput();
        }

        $updates = [
            'empresa_id' => $empresa->id,
            'nome' => $data['nome'],
            'email' => $data['email'],
            'perfil' => $data['perfil'],
        ];

        if (! empty($data['senha'])) {
            $updates['senha'] = $data['senha'];
        }

        $admin->update($updates);

        return back()->with('status', 'Usuário atualizado.');
    }

    public function destroyAdmin(Admin $admin)
    {
        $this->abortUnlessSuperAdmin();
        abort_if($admin->is_superadmin, 404);

        $mantemAdministrador = Admin::query()
            ->where('empresa_id', $admin->empresa_id)
            ->whereKeyNot($admin->id)
            ->where('is_superadmin', false)
            ->where(fn ($query) => $query->whereNull('perfil')->orWhere('perfil', 'admin'))
            ->exists();

        if ($admin->perfil() === 'admin' && ! $mantemAdministrador) {
            return back()->withErrors([
                'admin' => 'Mantenha pelo menos um dono/administrador nesta empresa.',
            ]);
        }

        $admin->delete();

        return back()->with('status', 'Usuário removido.');
    }

    public function acessarAdmin(Admin $admin)
    {
        $this->abortUnlessSuperAdmin();
        abort_if($admin->is_superadmin, 404);
        abort_unless($admin->empresa?->status === 'ativa', 404);

        session([
            'superadmin_id' => Auth::guard('admin')->id(),
            'superadmin_empresa_ativa_id' => session('empresa_ativa_id'),
        ]);

        Auth::guard('admin')->login($admin);
        session()->forget('empresa_ativa_id');

        return redirect()->route('admin.inicio')->with('status', 'Acessando como '.$admin->nome.'.');
    }

    public function voltarSuperadmin()
    {
        $superadminId = session('superadmin_id');
        abort_unless($superadminId, 403);

        $superadmin = Admin::query()
            ->whereKey($superadminId)
            ->where('is_superadmin', true)
            ->firstOrFail();

        Auth::guard('admin')->login($superadmin);

        $empresaAtivaId = session('superadmin_empresa_ativa_id');
        session()->forget(['superadmin_id', 'superadmin_empresa_ativa_id']);

        if ($empresaAtivaId) {
            session(['empresa_ativa_id' => $empresaAtivaId]);
        } else {
            session()->forget('empresa_ativa_id');
        }

        return redirect()->route('admin.sistema.index')->with('status', 'Você voltou ao superadmin.');
    }

    public function manutencao(Request $request)
    {
        $this->abortUnlessSuperAdmin();

        $data = $request->validate([
            'manutencao_geral' => ['nullable', 'boolean'],
        ]);

        SistemaConfiguracao::definir('manutencao_geral', $request->boolean('manutencao_geral') ? '1' : '0');

        return back()->with('status', $request->boolean('manutencao_geral')
            ? 'Sistema em manutenção geral.'
            : 'Sistema liberado para os usuários.');
    }

    public function backup()
    {
        $this->abortUnlessSuperAdmin();

        if (! class_exists(ZipArchive::class)) {
            return back()->withErrors([
                'backup' => 'O servidor não possui suporte a ZIP habilitado.',
            ]);
        }

        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = now()->format('Ymd-His');
        $path = $dir.'/gestao-backup-'.$timestamp.'.zip';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors([
                'backup' => 'Não foi possível criar o arquivo de backup.',
            ]);
        }

        $zip->addFromString('backup-info.json', json_encode([
            'sistema' => 'Gestão de Máquinas',
            'gerado_em' => now()->toDateTimeString(),
            'gerado_por' => Auth::guard('admin')->user()?->email,
            'conteudo' => [
                'database.sql',
                'database.json',
                'storage-public/',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->addFromString('database.sql', $this->backupSql());
        $zip->addFromString('database.json', json_encode($this->backupJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->adicionarStoragePublicoAoZip($zip);
        $zip->close();

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    }

    private function abortUnlessSuperAdmin(): void
    {
        abort_unless($this->isSuperAdmin(), 403);
    }

    private function validarAdmin(Request $request, ?Admin $admin = null): array
    {
        $senhaRules = $admin ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'];
        $emailRule = Rule::unique('admins', 'email');

        if ($admin) {
            $emailRule->ignore($admin->id);
        }

        return $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'senha' => $senhaRules,
            'perfil' => ['required', 'in:admin,comercial'],
        ]);
    }

    private function vencimentoPadrao(string $periodicidade): string
    {
        return match ($periodicidade) {
            'trimestral' => now()->addMonthsNoOverflow(3)->toDateString(),
            'anual' => now()->addYearNoOverflow()->toDateString(),
            default => now()->addMonthNoOverflow()->toDateString(),
        };
    }

    private function sincronizarLogoEmpresa(Request $request, Empresa $empresa): void
    {
        if ($request->boolean('remover_logo')) {
            $this->removerLogoEmpresa($empresa);
        }

        if (! $request->hasFile('logo')) {
            return;
        }

        $arquivo = $request->file('logo');

        if (! $arquivo || ! $arquivo->isValid()) {
            return;
        }

        $this->removerLogoEmpresa($empresa);

        $extensao = strtolower($arquivo->getClientOriginalExtension() ?: $arquivo->extension() ?: 'png');
        $path = $arquivo->storeAs('empresas/'.$empresa->id, 'logo-'.now()->format('YmdHis').'.'.$extensao, 'public');

        $empresa->forceFill(['logo_path' => $path])->save();
    }

    private function removerLogoEmpresa(Empresa $empresa): void
    {
        if ($empresa->logo_path) {
            Storage::disk('public')->delete($empresa->logo_path);
        }

        $empresa->forceFill(['logo_path' => null])->save();
    }

    private function tabelasBackup(): array
    {
        return array_values(array_filter([
            'sistema_configuracoes',
            'sistema_propostas',
            'sistema_cobrancas',
            'empresas',
            'admins',
            'operadores',
            'maquinas',
            'locacao_clientes',
            'locacao_historicos',
            'orcamentos',
            'orcamento_itens',
            'maquina_arquivos',
            'formularios',
            'formulario_itens',
            'registros',
            'registro_respostas',
            'registro_anexos',
            'alertas',
            'alerta_status_historicos',
            'checklist_resets',
            'security_logs',
        ], fn (string $table) => Schema::hasTable($table)));
    }

    private function backupJson(): array
    {
        $data = [];

        foreach ($this->tabelasBackup() as $table) {
            $data[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $data;
    }

    private function backupSql(): string
    {
        $pdo = DB::getPdo();
        $sql = [
            '-- Backup Gestão de Máquinas',
            '-- Gerado em '.now()->toDateTimeString(),
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        foreach ($this->tabelasBackup() as $table) {
            $rows = DB::table($table)->get();
            $columns = Schema::getColumnListing($table);
            $quotedTable = '`'.str_replace('`', '``', $table).'`';

            $sql[] = '-- Tabela '.$table;

            if ($rows->isEmpty()) {
                $sql[] = '';
                continue;
            }

            $quotedColumns = collect($columns)
                ->map(fn (string $column) => '`'.str_replace('`', '``', $column).'`')
                ->implode(', ');

            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;
                    $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
                }

                $sql[] = 'INSERT INTO '.$quotedTable.' ('.$quotedColumns.') VALUES ('.implode(', ', $values).');';
            }

            $sql[] = '';
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $sql[] = '';

        return implode(PHP_EOL, $sql);
    }

    private function adicionarStoragePublicoAoZip(ZipArchive $zip): void
    {
        $storagePublic = storage_path('app/public');

        if (! is_dir($storagePublic)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePublic, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $localPath = $file->getPathname();
            $relativePath = str_replace('\\', '/', substr($localPath, strlen($storagePublic) + 1));
            $zip->addFile($localPath, 'storage-public/'.$relativePath);
        }
    }

    private function removerDadosEmpresa(Empresa $empresa): void
    {
        $maquinaIds = DB::table('maquinas')->where('empresa_id', $empresa->id)->pluck('id');
        $formularioIds = DB::table('formularios')->where('empresa_id', $empresa->id)->pluck('id');
        $itemIds = DB::table('formulario_itens')->whereIn('formulario_id', $formularioIds)->pluck('id');
        $operadorIds = DB::table('operadores')->where('empresa_id', $empresa->id)->pluck('id');
        $adminIds = DB::table('admins')
            ->where('empresa_id', $empresa->id)
            ->where('is_superadmin', false)
            ->pluck('id');

        $registroIds = DB::table('registros')
            ->whereIn('maquina_id', $maquinaIds)
            ->orWhereIn('formulario_id', $formularioIds)
            ->pluck('id');

        $alertaIds = DB::table('alertas')
            ->whereIn('maquina_id', $maquinaIds)
            ->orWhereIn('formulario_item_id', $itemIds)
            ->orWhereIn('operador_id', $operadorIds)
            ->orWhereIn('registro_id', $registroIds)
            ->pluck('id');

        DB::table('alerta_status_historicos')->whereIn('alerta_id', $alertaIds)->delete();
        DB::table('alertas')->whereIn('id', $alertaIds)->delete();
        DB::table('registro_anexos')->whereIn('registro_id', $registroIds)->delete();
        DB::table('registro_respostas')->whereIn('registro_id', $registroIds)->delete();
        DB::table('registros')->whereIn('id', $registroIds)->delete();
        DB::table('checklist_resets')->whereIn('maquina_id', $maquinaIds)->delete();
        if (Schema::hasTable('locacao_historicos')) {
            DB::table('locacao_historicos')->whereIn('maquina_id', $maquinaIds)->delete();
        }

        if (Schema::hasTable('orcamentos')) {
            if (Schema::hasTable('orcamento_itens')) {
                DB::table('orcamento_itens')
                    ->whereIn('orcamento_id', DB::table('orcamentos')->where('empresa_id', $empresa->id)->pluck('id'))
                    ->delete();
            }

            DB::table('orcamentos')->where('empresa_id', $empresa->id)->delete();
        }

        if (Schema::hasTable('locacao_clientes')) {
            DB::table('locacao_clientes')->where('empresa_id', $empresa->id)->delete();
        }
        DB::table('maquina_arquivos')->whereIn('maquina_id', $maquinaIds)->delete();
        DB::table('maquinas')->whereIn('id', $maquinaIds)->delete();
        DB::table('formulario_itens')->whereIn('formulario_id', $formularioIds)->delete();
        DB::table('formularios')->whereIn('id', $formularioIds)->delete();
        DB::table('operadores')->where('empresa_id', $empresa->id)->delete();
        DB::table('admins')->whereIn('id', $adminIds)->delete();
    }
}
