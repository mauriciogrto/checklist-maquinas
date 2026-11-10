<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function create()
    {
        return view('auth.admin-login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['senha'],
        ], $request->boolean('remember'))) {
            $this->logLoginFalho($request, $credentials['email']);

            throw ValidationException::withMessages([
                'email' => 'Credenciais invalidas.',
            ]);
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin->is_superadmin && ! $admin->empresa?->acessoLiberado()) {
            Auth::guard('admin')->logout();
            $this->logLoginFalho($request, $credentials['email'], 'login_admin_bloqueado');

            throw ValidationException::withMessages([
                'email' => 'Acesso bloqueado. Empresa inativa ou com mensalidade pendente.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.inicio'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget(['superadmin_id', 'superadmin_empresa_ativa_id', 'empresa_ativa_id']);

        return redirect()->route('admin.login');
    }

    private function logLoginFalho(Request $request, string $email, string $evento = 'login_admin_falhou'): void
    {
        if (! Schema::hasTable('security_logs')) {
            return;
        }

        DB::table('security_logs')->insert([
            'evento' => $evento,
            'risco' => 'medio',
            'status_code' => 401,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route_name' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'payload' => json_encode(['email' => $email], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
