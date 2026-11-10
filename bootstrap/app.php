<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/flespi/telemetria',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*')) {
                return route('admin.login');
            }

            if ($request->route('token')) {
                return route('maquinas.public.show', $request->route('token'));
            }

            return route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            $segments = $request->segments();
            $mobileSegment = array_search('m', $segments, true);
            $isMobileRoute = $mobileSegment !== false;

            if ($isMobileRoute) {
                $token = $request->route('token') ?: ($segments[$mobileSegment + 1] ?? null);

                if ($token) {
                    return redirect()
                        ->route('maquinas.public.show', $token)
                        ->withErrors(['sessao' => 'Sua sessão expirou. Entre novamente para continuar.']);
                }
            }

            return null;
        });
    })->create();
