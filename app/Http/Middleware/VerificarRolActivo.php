<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if (! $usuario) {
            return redirect()->route('login', [], 303);
        }

        $rolActivo = $this->obtenerRolActivo();

        $rolesUsuario = $usuario->roles()
            ->where('est_rol', 'activo')
            ->pluck('nom_rol')
            ->map(fn ($rol) => trim((string) $rol))
            ->values()
            ->toArray();

        if ($rolActivo === '' || ! in_array($rolActivo, $rolesUsuario, true)) {
            Log::info('VerificarRolActivo: rol activo inválido o faltante', [
                'user_id' => optional($usuario)->getKey(),
                'rolesUsuario' => $rolesUsuario,
                'rolActivo' => $rolActivo,
                'request_path' => $request->path(),
                'method' => $request->method(),
            ]);

            session()->forget(['cod_rol_activo', 'nom_rol_activo', 'rol_activo']);

            return redirect()->route('roles.seleccionar', [], 303);
        }

        return $next($request);
    }

    protected function obtenerRolActivo(): string
    {
        return trim((string) session('nom_rol_activo', session('rol_activo')));
    }
}