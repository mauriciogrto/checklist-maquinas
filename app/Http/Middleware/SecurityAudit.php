<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SecurityAudit
{
    private const SENSITIVE_KEYS = [
        'senha',
        'password',
        'token',
        '_token',
        'admin_senha',
        'bearer',
        'authorization',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->logIfNeeded($request, $response);

        return $response;
    }

    private function logIfNeeded(Request $request, Response $response): void
    {
        if (! Schema::hasTable('security_logs')) {
            return;
        }

        if ($request->is('storage/*') || $request->is('assets/*')) {
            return;
        }

        [$evento, $risco] = $this->classify($request, $response);

        if (! $evento) {
            return;
        }

        try {
            $admin = Auth::guard('admin')->user();
            $operador = Auth::guard('operador')->user();

            DB::table('security_logs')->insert([
                'admin_id' => $admin?->id,
                'operador_id' => $operador?->id,
                'empresa_id' => $admin?->empresa_id ?: $operador?->empresa_id ?: session('empresa_ativa_id'),
                'evento' => $evento,
                'risco' => $risco,
                'status_code' => $response->getStatusCode(),
                'method' => $request->method(),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 500),
                'route_name' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'payload' => json_encode($this->safePayload($request), JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            report(new \RuntimeException('Nao foi possivel gravar log de seguranca.'));
        }
    }

    private function classify(Request $request, Response $response): array
    {
        $status = $response->getStatusCode();

        if ($status === 429) {
            return ['rate_limit', 'alto'];
        }

        if (in_array($status, [401, 403, 419], true)) {
            return ['acesso_bloqueado', 'medio'];
        }

        if ($status === 404 && ($request->is('admin/*') || $request->is('m/*') || $request->is('api/*'))) {
            return ['rota_inexistente', 'baixo'];
        }

        if ($status >= 500) {
            return ['erro_servidor', 'alto'];
        }

        if ($this->hasSuspiciousInput($request)) {
            return ['entrada_suspeita', 'alto'];
        }

        return [null, null];
    }

    private function hasSuspiciousInput(Request $request): bool
    {
        $content = json_encode($this->safePayload($request), JSON_UNESCAPED_UNICODE);

        if (! $content) {
            return false;
        }

        $patterns = [
            '/(\bunion\b\s+\bselect\b|\bselect\b.+\bfrom\b|\bdrop\b\s+\btable\b|\binsert\b\s+\binto\b)/i',
            '/(\bor\b\s+1\s*=\s*1|\band\b\s+1\s*=\s*1|--|#|\/\*)/i',
            '/(<script|javascript:|onerror\s*=|onload\s*=)/i',
            '/(\.\.\/|\.\.\\\\|\/etc\/passwd|cmd\.exe|powershell)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function safePayload(Request $request): array
    {
        $payload = array_merge($request->query(), $request->except(array_values(self::SENSITIVE_KEYS)));

        return $this->sanitize($payload);
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $clean = [];

            foreach ($value as $key => $item) {
                if ($this->isSensitiveKey((string) $key)) {
                    $clean[$key] = '[removido]';
                    continue;
                }

                $clean[$key] = $this->sanitize($item);
            }

            return $clean;
        }

        if (is_string($value)) {
            return mb_substr($value, 0, 300);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains(mb_strtolower($key), $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
