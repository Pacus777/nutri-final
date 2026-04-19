<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        $usuarios = Usuario::with('roles')
            ->orderBy('cod_usuarios', 'desc')
            ->get()
            ->map(function ($usuario) {
                return [
                    'cod_usuarios' => $usuario->cod_usuarios,
                    'nom_usu' => $usuario->nom_usu,
                    'cor_usu' => $usuario->cor_usu,
                    'est_usu' => $usuario->est_usu,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'cod_roles' => $rol->cod_roles,
                            'nom_rol' => $rol->nom_rol,
                        ];
                    }),
                ];
            });

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function create(): Response
    {
        $roles = Rol::orderBy('nom_rol')->get(['cod_roles', 'nom_rol']);

        return Inertia::render('Usuarios/Create', [
            'roles' => $roles,
            'estados' => ['activo', 'inactivo', 'bloqueado'],
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $usuario = Usuario::create([
            'nom_usu' => $request->nom_usu,
            'cor_usu' => $request->cor_usu,
            'cla_usu' => $request->cla_usu,
            'est_usu' => $request->est_usu,
        ]);

        $usuario->roles()->sync($request->roles);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }
}