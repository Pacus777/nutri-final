<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('cod_rol_activo') || ! session()->has('nom_rol_activo')) {
            return redirect()->route('roles.seleccionar');
        }

        return $next($request);
    }
}