<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeleccionarRolActivoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RolActivoController extends Controller
{
    public function seleccionar(): Response|RedirectResponse
    {
        $usuario = Auth::user();

        $roles = $usuario->roles()
            ->select('cod_roles', 'nom_rol')
            ->get()
            ->map(fn ($rol) => [
                'cod_roles' => $rol->cod_roles,
                'nom_rol' => trim((string) $rol->nom_rol),
            ])
            ->values();

        $rolActivo = trim((string) session('nom_rol_activo', session('rol_activo')));

        $rolesValidos = $roles->pluck('nom_rol')->toArray();

        // Si ya tiene un rol activo válido, no mostrar selector
        if ($rolActivo !== '' && in_array($rolActivo, $rolesValidos, true)) {
            return redirect()->route('dashboard');
        }

        // Si solo tiene un rol, lo asignamos automáticamente
        if ($roles->count() === 1) {
            session([
                'cod_rol_activo' => $roles->first()['cod_roles'],
                'nom_rol_activo' => $roles->first()['nom_rol'],
                'rol_activo' => $roles->first()['nom_rol'],
            ]);

            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/SeleccionarRol', [
            'roles' => $roles,
            'rolActivo' => $rolActivo,
        ]);
    }

    public function guardar(SeleccionarRolActivoRequest $request): RedirectResponse
    {
        $usuario = Auth::user();

        $rolSolicitado = $usuario->roles()
            ->where('roles.cod_roles', $request->integer('cod_rol'))
            ->where('est_rol', 'activo')
            ->first();

        if (! $rolSolicitado) {
            return back()->withErrors([
                'cod_rol' => 'El rol seleccionado no pertenece a este usuario.',
            ]);
        }

        session([
            'cod_rol_activo' => $rolSolicitado->cod_roles,
            'nom_rol_activo' => trim((string) $rolSolicitado->nom_rol),
            'rol_activo' => trim((string) $rolSolicitado->nom_rol),
        ]);

        return redirect()->route('dashboard');
    }

    public function cambiar(): RedirectResponse
    {
        session()->forget(['cod_rol_activo', 'nom_rol_activo', 'rol_activo']);

        return redirect()->route('roles.seleccionar');
    }
}