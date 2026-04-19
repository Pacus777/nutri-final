<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeleccionarRolActivoRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RolActivoController extends Controller
{
    public function seleccionar(): Response|RedirectResponse
    {
        $usuario = auth()->user()->load('roles');

        if ($usuario->roles->isEmpty()) {
            abort(403, 'El usuario no tiene roles asignados.');
        }

        if ($usuario->roles->count() === 1) {
            $rol = $usuario->roles->first();

            session([
                'cod_rol_activo' => $rol->cod_roles,
                'nom_rol_activo' => $rol->nom_rol,
            ]);

            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/SeleccionarRol', [
            'roles' => $usuario->roles->map(function ($rol) {
                return [
                    'cod_roles' => $rol->cod_roles,
                    'nom_rol' => $rol->nom_rol,
                    'des_rol' => $rol->des_rol,
                ];
            }),
        ]);
    }

    public function guardar(SeleccionarRolActivoRequest $request): RedirectResponse
    {
        $usuario = auth()->user()->load('roles');

        $rol = $usuario->roles->firstWhere('cod_roles', (int) $request->cod_rol);

        if (! $rol) {
            return back()->withErrors([
                'cod_rol' => 'El rol seleccionado no pertenece al usuario autenticado.',
            ]);
        }

        session([
            'cod_rol_activo' => $rol->cod_roles,
            'nom_rol_activo' => $rol->nom_rol,
        ]);

        return redirect()->route('dashboard');
    }

    public function cambiar(SeleccionarRolActivoRequest $request): RedirectResponse
    {
        $usuario = auth()->user()->load('roles');

        $rol = $usuario->roles->firstWhere('cod_roles', (int) $request->cod_rol);

        if (! $rol) {
            return back()->withErrors([
                'cod_rol' => 'No puedes activar un rol que no tienes asignado.',
            ]);
        }

        session([
            'cod_rol_activo' => $rol->cod_roles,
            'nom_rol_activo' => $rol->nom_rol,
        ]);

        return back()->with('success', 'Rol activo actualizado correctamente.');
    }
}