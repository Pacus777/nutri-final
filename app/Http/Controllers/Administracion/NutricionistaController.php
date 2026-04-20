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
use App\Http\Requests\StoreNutricionistaRapidoRequest;

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
        $nutricionista->load('usuario');

        return Inertia::render('Nutricionistas/Edit', [
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'est_nut' => $nutricionista->est_nut,

                'usuario' => $nutricionista->usuario ? [
                    'cod_usuarios' => $nutricionista->usuario->cod_usuarios,
                    'nom_usu' => $nutricionista->usuario->nom_usu,
                    'ape_usu' => $nutricionista->usuario->ape_usu,
                    'ci_usu' => $nutricionista->usuario->ci_usu,
                    'ext_ci_usu' => $nutricionista->usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($nutricionista->usuario->fec_nac_usu)->format('Y-m-d'),
                    'fot_usu' => $nutricionista->usuario->fot_usu,
                    'cor_usu' => $nutricionista->usuario->cor_usu,
                    'est_usu' => $nutricionista->usuario->est_usu,
                    'nombre_completo' => $nutricionista->usuario->nombre_completo,
                ] : null,
            ],
            'estadosUsuario' => ['activo', 'inactivo', 'bloqueado'],
            'estadosNutricionista' => ['activo', 'inactivo'],
        ]);
    }
    public function update(UpdateNutricionistaRequest $request, Nutricionista $nutricionista): RedirectResponse
    {
        $usuario = $nutricionista->usuario;

        $rutaFoto = $usuario->fot_usu;

        if ($request->hasFile('fot_usu')) {
            if ($usuario->fot_usu && \Storage::disk('public')->exists($usuario->fot_usu)) {
                \Storage::disk('public')->delete($usuario->fot_usu);
            }

            $rutaFoto = $request->file('fot_usu')->store('usuarios', 'public');
        }

        $usuario->update([
            'nom_usu' => $request->nom_usu,
            'ape_usu' => $request->ape_usu,
            'ci_usu' => $request->ci_usu,
            'ext_ci_usu' => $request->ext_ci_usu,
            'fec_nac_usu' => $request->fec_nac_usu,
            'fot_usu' => $rutaFoto,
            'cor_usu' => $request->cor_usu,
            'est_usu' => $request->est_usu,
        ]);

        if ($request->filled('cla_usu')) {
            $usuario->update([
                'cla_usu' => $request->cla_usu,
            ]);
        }

        $nutricionista->update([
            'esp_nut' => $request->esp_nut,
            'est_nut' => $request->est_nut,
        ]);

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

    public function registroRapido(): Response
    {
        return Inertia::render('Nutricionistas/RegistroRapido', [
            'estadosUsuario' => ['activo', 'inactivo', 'bloqueado'],
            'estadosNutricionista' => ['activo', 'inactivo'],
        ]);
    }

    public function guardarRegistroRapido(StoreNutricionistaRapidoRequest $request): RedirectResponse
    {
        $rutaFoto = null;

        if ($request->hasFile('fot_usu')) {
            $rutaFoto = $request->file('fot_usu')->store('usuarios', 'public');
        }

        $usuario = \App\Models\Usuario::create([
            'nom_usu' => $request->nom_usu,
            'ape_usu' => $request->ape_usu,
            'ci_usu' => $request->ci_usu,
            'ext_ci_usu' => $request->ext_ci_usu,
            'fec_nac_usu' => $request->fec_nac_usu,
            'fot_usu' => $rutaFoto,
            'cor_usu' => $request->cor_usu,
            'cla_usu' => $request->cla_usu,
            'est_usu' => $request->est_usu,
        ]);

        $rolNutricionista = \App\Models\Rol::where('nom_rol', 'Nutricionista')->first();

        if ($rolNutricionista) {
            $usuario->roles()->syncWithoutDetaching([$rolNutricionista->cod_roles]);
        }

        Nutricionista::create([
            'cod_usuarios_nut' => $usuario->cod_usuarios,
            'esp_nut' => $request->esp_nut,
            'est_nut' => $request->est_nut,
        ]);

        return redirect()
            ->route('nutricionistas.index')
            ->with('success', 'Nutricionista creado correctamente desde registro rápido.');
    }
}