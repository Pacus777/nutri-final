<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var Usuario $usuario */
        $usuario = $request->user();
        $rolActivo = $this->resolverRolActivo($usuario);

        if ($rolActivo !== '') {
            session([
                'nom_rol_activo' => $rolActivo,
                'rol_activo' => $rolActivo,
            ]);
        }

        Log::info("ACTIVIDAD: El usuario '{$usuario->nom_usu}' (ID: {$usuario->cod_usuarios}) ha iniciado sesión con el rol activo '{$rolActivo}' desde la IP {$request->ip()}.");

        return redirect()->intended($this->rutaPorRol($rolActivo));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        if ($usuario) {
            Log::info("ACTIVIDAD: El usuario '{$usuario->nom_usu}' (ID: {$usuario->cod_usuarios}) ha cerrado sesión.");
        }

        Auth::guard('web')->logout();

        $request->session()->forget(['cod_rol_activo', 'nom_rol_activo', 'rol_activo']);

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function resolverRolActivo(Usuario $usuario): string
    {
        $roles = $usuario->roles()
            ->where('est_rol', 'activo')
            ->pluck('nom_rol')
            ->map(fn ($rol) => trim((string) $rol))
            ->values()
            ->toArray();

        foreach (['SuperAdmin', 'Administrador', 'Nutricionista', 'Paciente'] as $rolPreferido) {
            if (in_array($rolPreferido, $roles, true)) {
                return $rolPreferido;
            }
        }

        return $roles[0] ?? '';
    }

    protected function rutaPorRol(string $rol): string
    {
        return match ($rol) {
            'Nutricionista' => route('dashboard.nutricionista', absolute: false),
            'Paciente' => route('dashboard.paciente', absolute: false),
            'SuperAdmin', 'Administrador' => route('dashboard.admin', absolute: false),
            default => route('dashboard', absolute: false),
        };
    }
}
