<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\UpdateUsuarioRequest;

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

    public function edit(Usuario $usuario): Response
    {
        $roles = Rol::orderBy('nom_rol')->get(['cod_roles', 'nom_rol']);

        return Inertia::render('Usuarios/Edit', [
            'usuario' => [
                'cod_usuarios' => $usuario->cod_usuarios,
                'nom_usu' => $usuario->nom_usu,
                'cor_usu' => $usuario->cor_usu,
                'est_usu' => $usuario->est_usu,
                'roles' => $usuario->roles->pluck('cod_roles'),
            ],
            'roles' => $roles,
            'estados' => ['activo', 'inactivo', 'bloqueado'],
        ]);
    }

    public function update(UpdateUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $usuarioAutenticado = auth()->user();
        $rolesSeleccionados = collect($request->roles)->map(fn ($rol) => (int) $rol)->values();

        if ($rolesSeleccionados->isEmpty()) {
            return back()->withErrors([
                'roles' => 'Debes asignar al menos un rol al usuario.',
            ])->withInput();
        }

        if ($usuarioAutenticado->cod_usuarios === $usuario->cod_usuarios) {
            if (in_array($request->est_usu, ['inactivo', 'bloqueado'], true)) {
                return back()->withErrors([
                    'est_usu' => 'No puedes cambiar tu propio estado a inactivo o bloqueado.',
                ])->withInput();
            }

            $rolSuperAdmin = \App\Models\Rol::where('nom_rol', 'SuperAdmin')->first();

            if ($rolSuperAdmin && $usuarioAutenticado->tieneRol('SuperAdmin')) {
                $mantieneSuperAdmin = $rolesSeleccionados->contains((int) $rolSuperAdmin->cod_roles);

                if (! $mantieneSuperAdmin) {
                    return back()->withErrors([
                        'roles' => 'No puedes quitarte a ti mismo el rol SuperAdmin.',
                    ])->withInput();
                }
            }
        }

        $usuario->update([
            'nom_usu' => $request->nom_usu,
            'cor_usu' => $request->cor_usu,
            'est_usu' => $request->est_usu,
        ]);

        if ($request->filled('cla_usu')) {
            $usuario->update([
                'cla_usu' => $request->cla_usu,
            ]);
        }

        $usuario->roles()->sync($rolesSeleccionados->all());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(\Illuminate\Http\Request $request, Usuario $usuario): RedirectResponse
    {
        $request->validate([
            'est_usu' => ['required', \Illuminate\Validation\Rule::in(['activo', 'inactivo', 'bloqueado'])],
        ]);

        $usuarioAutenticado = auth()->user();

        if ($usuarioAutenticado->cod_usuarios === $usuario->cod_usuarios) {
            if (in_array($request->est_usu, ['inactivo', 'bloqueado'], true)) {
                return back()->withErrors([
                    'general' => 'No puedes cambiar tu propio estado a inactivo o bloqueado.',
                ]);
            }
        }

        $usuario->update([
            'est_usu' => $request->est_usu,
        ]);

        return back()->with('success', 'Estado actualizado correctamente.');
    }
}