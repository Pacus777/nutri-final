<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUsuarioEstado
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario && $usuario->est_usu !== 'activo') {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'cor_usu' => 'Tu cuenta no está activa para ingresar al sistema.',
            ]);
        }

        return $next($request);
    }
}