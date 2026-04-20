<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNutricionistaRequest;
use App\Http\Requests\UpdateNutricionistaRequest;
use App\Models\Nutricionista;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NutricionistaController extends Controller
{
    public function index(): Response
    {
        $nutricionistas = Nutricionista::with(['usuario.roles'])
            ->orderBy('cod_nutricionistas', 'desc')
            ->get()
            ->map(function ($nutricionista) {
                return [
                    'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                    'esp_nut' => $nutricionista->esp_nut,
                    'est_nut' => $nutricionista->est_nut,
                    'usuario' => $nutricionista->usuario ? [
                        'cod_usuarios' => $nutricionista->usuario->cod_usuarios,
                        'nom_usu' => $nutricionista->usuario->nom_usu,
                        'cor_usu' => $nutricionista->usuario->cor_usu,
                        'est_usu' => $nutricionista->usuario->est_usu,
                        'roles' => $nutricionista->usuario->roles->map(fn ($rol) => [
                            'cod_roles' => $rol->cod_roles,
                            'nom_rol' => $rol->nom_rol,
                        ]),
                    ] : null,
                ];
            });

        return Inertia::render('Nutricionistas/Index', [
            'nutricionistas' => $nutricionistas,
        ]);
    }

    public function create(): Response
    {
        $usuarios = Usuario::whereDoesntHave('nutricionista')
            ->where('est_usu', 'activo')
            ->orderBy('nom_usu')
            ->get(['cod_usuarios', 'nom_usu', 'cor_usu']);

        return Inertia::render('Nutricionistas/Create', [
            'usuarios' => $usuarios,
            'estados' => ['activo', 'inactivo'],
        ]);
    }

    public function store(StoreNutricionistaRequest $request): RedirectResponse
    {
        $nutricionista = Nutricionista::create([
            'cod_usuarios_nut' => $request->cod_usuarios_nut,
            'esp_nut' => $request->esp_nut,
            'est_nut' => $request->est_nut,
        ]);

        $usuario = Usuario::findOrFail($request->cod_usuarios_nut);
        $rolNutricionista = Rol::where('nom_rol', 'Nutricionista')->first();

        if ($rolNutricionista && ! $usuario->roles()->where('roles.cod_roles', $rolNutricionista->cod_roles)->exists()) {
            $usuario->roles()->attach($rolNutricionista->cod_roles);
        }

        return redirect()
            ->route('nutricionistas.index')
            ->with('success', 'Nutricionista registrado correctamente.');
    }

    public function show(Nutricionista $nutricionista): Response
    {
        $nutricionista->load(['usuario.roles']);

        return Inertia::render('Nutricionistas/Show', [
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'est_nut' => $nutricionista->est_nut,
                'usuario' => $nutricionista->usuario ? [
                    'cod_usuarios' => $nutricionista->usuario->cod_usuarios,
                    'nom_usu' => $nutricionista->usuario->nom_usu,
                    'cor_usu' => $nutricionista->usuario->cor_usu,
                    'est_usu' => $nutricionista->usuario->est_usu,
                    'roles' => $nutricionista->usuario->roles->map(fn ($rol) => [
                        'cod_roles' => $rol->cod_roles,
                        'nom_rol' => $rol->nom_rol,
                    ]),
                ] : null,
            ],
        ]);
    }

    public function edit(Nutricionista $nutricionista): Response
    {
        $usuarios = Usuario::where(function ($query) use ($nutricionista) {
                $query->whereDoesntHave('nutricionista')
                    ->orWhere('cod_usuarios', $nutricionista->cod_usuarios_nut);
            })
            ->where('est_usu', 'activo')
            ->orderBy('nom_usu')
            ->get(['cod_usuarios', 'nom_usu', 'cor_usu']);

        return Inertia::render('Nutricionistas/Edit', [
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'cod_usuarios_nut' => $nutricionista->cod_usuarios_nut,
                'esp_nut' => $nutricionista->esp_nut,
                'est_nut' => $nutricionista->est_nut,
            ],
            'usuarios' => $usuarios,
            'estados' => ['activo', 'inactivo'],
        ]);
    }

    public function update(UpdateNutricionistaRequest $request, Nutricionista $nutricionista): RedirectResponse
    {
        $nutricionista->update([
            'cod_usuarios_nut' => $request->cod_usuarios_nut,
            'esp_nut' => $request->esp_nut,
            'est_nut' => $request->est_nut,
        ]);

        $usuario = Usuario::findOrFail($request->cod_usuarios_nut);
        $rolNutricionista = Rol::where('nom_rol', 'Nutricionista')->first();

        if ($rolNutricionista && ! $usuario->roles()->where('roles.cod_roles', $rolNutricionista->cod_roles)->exists()) {
            $usuario->roles()->attach($rolNutricionista->cod_roles);
        }

        return redirect()
            ->route('nutricionistas.index')
            ->with('success', 'Nutricionista actualizado correctamente.');
    }

    public function cambiarEstado(Nutricionista $nutricionista): RedirectResponse
    {
        $nuevoEstado = $nutricionista->est_nut === 'activo' ? 'inactivo' : 'activo';

        $nutricionista->update([
            'est_nut' => $nuevoEstado,
        ]);

        return back()->with('success', 'Estado del nutricionista actualizado correctamente.');
    }
}