<?php

use App\Http\Controllers\Admin\AlertaController;
use App\Http\Controllers\Admin\ChecklistTecnicoController;
use App\Http\Controllers\Admin\ContratoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormularioMaquinaController;
use App\Http\Controllers\Admin\FormularioModeloController;
use App\Http\Controllers\Admin\HistoricoController;
use App\Http\Controllers\Admin\InicioController;
use App\Http\Controllers\Admin\LocacaoClienteController;
use App\Http\Controllers\Admin\MaquinaController;
use App\Http\Controllers\Admin\OrcamentoController;
use App\Http\Controllers\Admin\RastreamentoController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Api\FlespiWebhookController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\OperadorAuthController;
use App\Http\Middleware\CheckEmpresaAcesso;
use App\Http\Middleware\CheckSistemaManutencao;
use App\Http\Middleware\BlockCommercialAccess;
use App\Http\Controllers\Operador\ChecklistController;
use App\Http\Controllers\Operador\ManutencaoController;
use App\Http\Controllers\Operador\MaquinaAcessoController;
use App\Http\Controllers\Operador\ProblemaController;
use App\Http\Middleware\SecurityAudit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/api/flespi/telemetria', [FlespiWebhookController::class, 'store'])
    ->middleware([SecurityAudit::class, 'throttle:120,1'])
    ->name('api.flespi.telemetria');

Route::middleware([SecurityAudit::class, CheckSistemaManutencao::class, CheckEmpresaAcesso::class])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });

    Route::get('/storage/{path}', function (string $path) {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    })->where('path', '.*')->name('storage.public');

    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
    Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');

    Route::post('/operador/login', [OperadorAuthController::class, 'store'])->middleware('throttle:8,1')->name('operador.login.store');
    Route::post('/operador/logout', [OperadorAuthController::class, 'destroy'])->name('operador.logout');

    Route::prefix('m/{token}')->name('maquinas.public.')->middleware('throttle:80,1')->group(function () {
        Route::get('/', [MaquinaAcessoController::class, 'show'])->name('show');

        Route::middleware('auth:operador')->group(function () {
            Route::get('/checklist/{periodicidade}', [ChecklistController::class, 'create'])->name('checklist.create');
            Route::post('/checklist/{periodicidade}', [ChecklistController::class, 'store'])->middleware('throttle:20,1')->name('checklist.store');
            Route::get('/checklist-extra/{formulario}', [ChecklistController::class, 'createExtra'])->name('checklist-extra.create');
            Route::post('/checklist-extra/{formulario}', [ChecklistController::class, 'storeExtra'])->middleware('throttle:20,1')->name('checklist-extra.store');
            Route::get('/manutencao', [ManutencaoController::class, 'create'])->name('manutencao.create');
            Route::post('/manutencao', [ManutencaoController::class, 'store'])->middleware('throttle:20,1')->name('manutencao.store');
            Route::post('/problemas', [ProblemaController::class, 'store'])->middleware('throttle:12,1')->name('problemas.store');
        });
    });
});

Route::prefix('admin')->name('admin.')->middleware([SecurityAudit::class, 'auth:admin', CheckSistemaManutencao::class, CheckEmpresaAcesso::class])->group(function () {
    Route::get('/inicio', [InicioController::class, 'index'])->name('inicio');

    Route::middleware(BlockCommercialAccess::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/alertas.json', [DashboardController::class, 'alertasJson'])->name('dashboard.alertas-json');
        Route::get('/mapa', [RastreamentoController::class, 'index'])->name('rastreamento.mapa');
        Route::get('/mapa/dados.json', [RastreamentoController::class, 'dados'])->name('rastreamento.dados');

        Route::resource('alertas', AlertaController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('maquinas/create', [MaquinaController::class, 'create'])->name('maquinas.create');
        Route::post('maquinas', [MaquinaController::class, 'store'])->name('maquinas.store');
        Route::get('maquinas/{maquina}/edit', [MaquinaController::class, 'edit'])->name('maquinas.edit');
        Route::match(['put', 'patch'], 'maquinas/{maquina}', [MaquinaController::class, 'update'])->name('maquinas.update');
        Route::delete('maquinas/{maquina}', [MaquinaController::class, 'destroy'])->name('maquinas.destroy');
        Route::get('maquinas/{maquina}/locacao/edit', [MaquinaController::class, 'editLocacao'])->name('maquinas.locacao.edit');
        Route::patch('maquinas/{maquina}/locacao', [MaquinaController::class, 'updateLocacao'])->name('maquinas.locacao.update');
        Route::get('maquinas/{maquina}/horimetro.json', [MaquinaController::class, 'horimetroDados'])->name('maquinas.horimetro.dados');
        Route::get('maquinas/{maquina}/horimetro.pdf', [MaquinaController::class, 'horimetroPdf'])->name('maquinas.horimetro.pdf');
        Route::patch('maquinas/{maquina}/horimetro-inicial', [MaquinaController::class, 'atualizarHorimetroInicial'])->name('maquinas.horimetro-inicial.update');
        Route::post('maquinas/{maquina}/cobrancas', [MaquinaController::class, 'storeCobranca'])->name('maquinas.cobrancas.store');
        Route::patch('maquinas/{maquina}/cobrancas/{cobranca}', [MaquinaController::class, 'updateCobranca'])->name('maquinas.cobrancas.update');
        Route::delete('maquinas/{maquina}/cobrancas/{cobranca}', [MaquinaController::class, 'destroyCobranca'])->name('maquinas.cobrancas.destroy');
        Route::get('maquinas/{maquina}/qr-code', [MaquinaController::class, 'qrCode'])->name('maquinas.qr-code');
        Route::patch('maquinas/{maquina}/checklists/{periodicidade}/reset', [MaquinaController::class, 'resetChecklist'])->name('maquinas.checklists.reset');
        Route::patch('maquinas/{maquina}/checklists/{periodicidade}/modelo', [MaquinaController::class, 'atribuirChecklistModelo'])->name('maquinas.checklists.modelo');
        Route::patch('maquinas/{maquina}/checklists-extra', [MaquinaController::class, 'adicionarChecklistExtra'])->name('maquinas.checklists-extra.store');
        Route::delete('maquinas/{maquina}/arquivos/{arquivo}', [MaquinaController::class, 'destruirArquivo'])->name('maquinas.arquivos.destroy');
        Route::get('maquinas/{maquina}/manutencao', [ChecklistTecnicoController::class, 'createManutencao'])->name('maquinas.manutencao.create');
        Route::post('maquinas/{maquina}/manutencao', [ChecklistTecnicoController::class, 'storeManutencao'])->name('maquinas.manutencao.store');
        Route::get('maquinas/{maquina}/checklists/{periodicidade}', [ChecklistTecnicoController::class, 'create'])->name('maquinas.checklists.create');
        Route::post('maquinas/{maquina}/checklists/{periodicidade}', [ChecklistTecnicoController::class, 'store'])->name('maquinas.checklists.store');

        Route::resource('formularios-modelo', FormularioModeloController::class);
        Route::get('formularios/{maquina}/{tipo}/edit', [FormularioMaquinaController::class, 'edit'])->name('formularios-maquina.edit');
        Route::patch('formularios/{maquina}/{tipo}', [FormularioMaquinaController::class, 'update'])->name('formularios-maquina.update');
        Route::delete('formularios/{maquina}/{tipo}', [FormularioMaquinaController::class, 'destroy'])->name('formularios-maquina.destroy');
        Route::get('formularios/{maquina}/extra/{formulario}/edit', [FormularioMaquinaController::class, 'editExtra'])->name('formularios-maquina.extra.edit');
        Route::patch('formularios/{maquina}/extra/{formulario}', [FormularioMaquinaController::class, 'updateExtra'])->name('formularios-maquina.extra.update');
        Route::delete('formularios/{maquina}/extra/{formulario}', [FormularioMaquinaController::class, 'destroyExtra'])->name('formularios-maquina.extra.destroy');

        Route::get('historico', [HistoricoController::class, 'index'])->name('historico.index');
        Route::get('historico/anexos/{anexo}', [HistoricoController::class, 'anexo'])->name('historico.anexos.show');
        Route::get('historico/{registro}', [HistoricoController::class, 'show'])->name('historico.show');
        Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
        Route::get('relatorios/exportar', [RelatorioController::class, 'export'])->name('relatorios.export');
        Route::patch('contratos', [ContratoController::class, 'update'])->name('contratos.update');
    });

    Route::get('maquinas', [MaquinaController::class, 'index'])->name('maquinas.index');
    Route::get('maquinas/{maquina}', [MaquinaController::class, 'show'])->name('maquinas.show');
    Route::resource('clientes', LocacaoClienteController::class);
    Route::get('orcamentos/{orcamento}/pdf', [OrcamentoController::class, 'pdf'])->name('orcamentos.pdf');
    Route::get('orcamentos/{orcamento}/contrato', [OrcamentoController::class, 'contrato'])->name('orcamentos.contrato');
    Route::resource('orcamentos', OrcamentoController::class);
    Route::get('contratos', [ContratoController::class, 'index'])->name('contratos.index');
    Route::get('maquinas/{maquina}/arquivos/{arquivo}', [MaquinaController::class, 'abrirArquivo'])->name('maquinas.arquivos.show');

    Route::get('sistema', [SistemaController::class, 'index'])->name('sistema.index');
    Route::get('sistema/empresas', [SistemaController::class, 'empresas'])->name('sistema.empresas');
    Route::get('sistema/cadastrar', [SistemaController::class, 'create'])->name('sistema.create');
    Route::get('sistema/proposta', [SistemaController::class, 'proposta'])->name('sistema.proposta');
    Route::post('sistema/propostas', [SistemaController::class, 'storeProposta'])->name('sistema.propostas.store');
    Route::get('sistema/propostas', [SistemaController::class, 'propostas'])->name('sistema.propostas');
    Route::get('sistema/propostas/{proposta}/pdf', [SistemaController::class, 'propostaSalvaPdf'])->name('sistema.propostas.pdf');
    Route::patch('sistema/propostas/{proposta}/status', [SistemaController::class, 'updatePropostaStatus'])->name('sistema.propostas.status');
    Route::delete('sistema/propostas/{proposta}', [SistemaController::class, 'destroyProposta'])->name('sistema.propostas.destroy');
    Route::get('sistema/cobrancas', [SistemaController::class, 'cobrancas'])->name('sistema.cobrancas');
    Route::post('sistema/cobrancas', [SistemaController::class, 'storeCobranca'])->name('sistema.cobrancas.store');
    Route::patch('sistema/cobrancas/{cobranca}', [SistemaController::class, 'updateCobranca'])->name('sistema.cobrancas.update');
    Route::delete('sistema/cobrancas/{cobranca}', [SistemaController::class, 'destroyCobranca'])->name('sistema.cobrancas.destroy');
    Route::get('sistema/backup', [SistemaController::class, 'backupPage'])->name('sistema.backup');
    Route::get('sistema/backup/download', [SistemaController::class, 'backup'])->name('sistema.backup.download');
    Route::get('sistema/logs', [SistemaController::class, 'logs'])->name('sistema.logs');
    Route::post('sistema/empresas', [SistemaController::class, 'storeEmpresa'])->name('sistema.empresas.store');
    Route::get('sistema/empresas/{empresa}', [SistemaController::class, 'showEmpresa'])->name('sistema.empresas.show');
    Route::put('sistema/empresas/{empresa}', [SistemaController::class, 'updateEmpresa'])->name('sistema.empresas.update');
    Route::delete('sistema/empresas/{empresa}', [SistemaController::class, 'destroyEmpresa'])->name('sistema.empresas.destroy');
    Route::post('sistema/admins', [SistemaController::class, 'storeAdmin'])->name('sistema.admins.store');
    Route::put('sistema/admins/{admin}', [SistemaController::class, 'updateAdmin'])->name('sistema.admins.update');
    Route::delete('sistema/admins/{admin}', [SistemaController::class, 'destroyAdmin'])->name('sistema.admins.destroy');
    Route::post('sistema/admins/{admin}/acessar', [SistemaController::class, 'acessarAdmin'])->name('sistema.admins.acessar');
    Route::delete('sistema/voltar-superadmin', [SistemaController::class, 'voltarSuperadmin'])->name('sistema.superadmin.voltar');
    Route::patch('sistema/manutencao', [SistemaController::class, 'manutencao'])->name('sistema.manutencao');
});
