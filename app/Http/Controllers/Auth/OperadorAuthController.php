<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OperadorAuthController extends Controller
{
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'usuario' => ['required', 'string'],
            'senha' => ['required', 'string'],
            'token' => ['required', 'string'],
        ]);

        $maquina = Maquina::query()
            ->where('token', $credentials['token'])
            ->where('status', 'ativa')
            ->with('empresa')
            ->firstOrFail();

        if (! $maquina->empresa?->acessoLiberado()) {
            $this->logLoginFalho($request, $credentials['usuario'], $maquina->empresa_id, 'login_operador_bloqueado');

            throw ValidationException::withMessages([
                'usuario' => 'Acesso bloqueado. Empresa inativa ou com mensalidade pendente.',
            ]);
        }

        if ($maquina->credencialQrValida($credentials['usuario'], $credentials['senha'])) {
            Auth::guard('operador')->login($this->operadorAutomaticoDaMaquina($maquina), $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('maquinas.public.show', $credentials['token']);
        }

        if (! Auth::guard('operador')->attempt([
            'usuario' => $credentials['usuario'],
            'empresa_id' => $maquina->empresa_id,
            'status' => 'ativo',
            'password' => $credentials['senha'],
        ], $request->boolean('remember'))) {
            $this->logLoginFalho($request, $credentials['usuario'], $maquina->empresa_id);

            throw ValidationException::withMessages([
                'usuario' => 'Usuario ou senha invalidos.',
            ]);
        }

        $request->session()->regenerate();

        $operador = Auth::guard('operador')->user();

        if ($operador?->locacao_cliente_id && (int) $operador->locacao_cliente_id !== (int) $maquina->locacao_cliente_id) {
            Auth::guard('operador')->logout();
            $request->session()->regenerateToken();
            $this->logLoginFalho($request, $credentials['usuario'], $maquina->empresa_id, 'login_operador_cliente_invalido');

            throw ValidationException::withMessages([
                'usuario' => 'Este operador nao esta vinculado ao cliente desta maquina.',
            ]);
        }

        return redirect()->route('maquinas.public.show', $credentials['token']);
    }

    private function operadorAutomaticoDaMaquina(Maquina $maquina): Operador
    {
        $usuario = $maquina->loginQrPadrao();

        return Operador::query()->firstOrCreate(
            [
                'empresa_id' => $maquina->empresa_id,
                'usuario' => $usuario,
            ],
            [
                'nome' => 'Acesso '.$maquina->nome,
                'senha' => $usuario,
                'status' => 'ativo',
                'locacao_cliente_id' => null,
            ]
        );
    }

    public function destroy(Request $request)
    {
        $token = $request->input('token');

        Auth::guard('operador')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $token
            ? redirect()->route('maquinas.public.show', $token)
            : redirect('/');
    }

    private function logLoginFalho(Request $request, string $usuario, ?int $empresaId = null, string $evento = 'login_operador_falhou'): void
    {
        if (! Schema::hasTable('security_logs')) {
            return;
        }

        DB::table('security_logs')->insert([
            'empresa_id' => $empresaId,
            'evento' => $evento,
            'risco' => 'medio',
            'status_code' => 401,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route_name' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'payload' => json_encode(['usuario' => $usuario], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
