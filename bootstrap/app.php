<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

         $middleware->alias([
            'auth.usuario' => \Illuminate\Auth\Middleware\Authenticate::class,
            'check.estado' => \App\Http\Middleware\CheckUsuarioEstado::class,
            'usuario.activo' => \App\Http\Middleware\CheckUsuarioEstado::class,
            'verificar.rol' => \App\Http\Middleware\VerificarRol::class,
            'role' => \App\Http\Middleware\VerificarRol::class,
            'verificar.rol.activo' => \App\Http\Middleware\VerificarRolActivo::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if ($request->header('X-Inertia')) {
                return Inertia::location(route('login'));
            }

            if (! $request->isMethod('GET')) {
                return redirect()->guest(route('login'), 303);
            }

            return redirect()->guest(route('login'));
        });
    })->create();
