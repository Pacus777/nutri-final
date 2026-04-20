<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $rolActivo = session('nom_rol_activo');

        if (! $rolActivo) {
            return redirect()->route('roles.seleccionar')
                ->withErrors([
                    'general' => 'Debes seleccionar un rol activo para continuar.',
                ]);
        }

        if (! in_array($rolActivo, $roles, true)) {
            abort(403, 'No tienes permisos para acceder a esta sección con el rol activo actual.');
        }

        return $next($request);
    }
}