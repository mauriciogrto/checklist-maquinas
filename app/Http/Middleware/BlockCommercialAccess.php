<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockCommercialAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('admin')->user()?->isComercial()) {
            abort(403, 'Acesso não permitido para o perfil comercial.');
        }

        return $next($request);
    }
}
