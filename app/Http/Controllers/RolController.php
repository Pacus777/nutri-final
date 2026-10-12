<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Inertia\Inertia;
use Inertia\Response;

class RolController extends Controller
{
    public function index(): Response
    {
        $roles = Rol::query()
            ->orderBy('nom_rol')
            ->get()
            ->map(fn (Rol $rol) => [
                'cod_roles' => $rol->cod_roles,
                'nom_rol' => $rol->nom_rol,
                'des_rol' => $rol->des_rol,
                'est_rol' => $rol->est_rol ?? 'activo',
                'usuarios_count' => $rol->usuarios()->count(),
            ]);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }
}
