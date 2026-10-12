<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, ...$rolesPermitidos): Response
    {
        if (! Auth::check()) {
            Log::info('VerificarRol: Auth not authenticated', [
                'request_path' => $request->path(),
                'method' => $request->method(),
            ]);

            return redirect()->route('login', [], 303);
        }

        $rolActivo = trim((string) session('nom_rol_activo', session('rol_activo')));

        $rolesPermitidos = collect($rolesPermitidos)
            ->map(fn ($rol) => trim((string) $rol))
            ->values()
            ->toArray();

        if ($rolActivo === '' || ! in_array($rolActivo, $rolesPermitidos, true)) {
            Log::info('VerificarRol: rol activo inválido o sin permiso', [
                'user_id' => Auth::id(),
                'rolActivo' => $rolActivo,
                'rolesPermitidos' => $rolesPermitidos,
                'request_path' => $request->path(),
                'method' => $request->method(),
            ]);

            session()->forget(['cod_rol_activo', 'nom_rol_activo', 'rol_activo']);

            return redirect()->route('roles.seleccionar', [], 303)
                ->withErrors([
                    'rol' => 'No tienes permiso para acceder a esta sección.',
                ]);
        }

        return $next($request);
    }
}