<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $usuario = Auth::user();

        if (! $usuario) {
            abort(403, 'Usuario no autenticado.');
        }

        if (! $usuario->relationLoaded('roles')) {
            $usuario->load('roles');
        }

        $tieneRol = $usuario->roles->contains(function ($rol) use ($roles) {
            return in_array($rol->nom_rol, $roles, true);
        });

        if (! $tieneRol) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}