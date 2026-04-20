<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Paciente;
use App\Models\Nutricionista;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        $usuarios = Usuario::with(['roles', 'nutricionista', 'paciente'])
            ->orderBy('cod_usuarios', 'desc')
            ->get()
            ->map(function ($usuario) {
                return [
                    'cod_usuarios' => $usuario->cod_usuarios,
                    'nom_usu' => $usuario->nom_usu,
                    'ape_usu' => $usuario->ape_usu,
                    'nombre_completo' => $usuario->nombre_completo,
                    'ci_usu' => $usuario->ci_usu,
                    'ext_ci_usu' => $usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($usuario->fec_nac_usu)->format('Y-m-d'),
                    'fot_usu' => $usuario->fot_usu,
                    'cor_usu' => $usuario->cor_usu,
                    'est_usu' => $usuario->est_usu,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'cod_roles' => $rol->cod_roles,
                            'nom_rol' => $rol->nom_rol,
                        ];
                    }),
                    'tiene_perfil_nutricionista' => $usuario->nutricionista !== null,
                    'tiene_perfil_paciente' => $usuario->paciente !== null,
                ];
            });

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Usuarios/Create', [
            'estados' => ['activo', 'inactivo', 'bloqueado'],
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $rutaFoto = null;

        if ($request->hasFile('fot_usu')) {
            $rutaFoto = $request->file('fot_usu')->store('usuarios', 'public');
        }

        $usuario = Usuario::create([
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

        $rol = Rol::where('nom_rol', $request->tipo_registro)->first();

        if ($rol) {
            $usuario->roles()->sync([$rol->cod_roles]);
        }

        if ($request->tipo_registro === 'Nutricionista') {
            Nutricionista::create([
                'cod_usuarios_nut' => $usuario->cod_usuarios,
                'esp_nut' => $request->esp_nut,
                'est_nut' => $request->est_nut,
            ]);
        }

        if ($request->tipo_registro === 'Paciente') {
            Paciente::create([
                'cod_usuarios_pac' => $usuario->cod_usuarios,
                'sex_pac' => $request->sex_pac,
                'pes_pac' => $request->pes_pac,
                'estatura_pac' => $request->estatura_pac,
                'tip_san_pac' => $request->tip_san_pac,
                'ale_pac' => $request->ale_pac,
                'est_pac' => $request->est_pac,
            ]);
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Registro creado correctamente.');
    }

    public function edit(Usuario $usuario): Response
    {
        $roles = Rol::orderBy('nom_rol')->get(['cod_roles', 'nom_rol']);

        $usuario->load(['roles', 'nutricionista', 'paciente']);

        return Inertia::render('Usuarios/Edit', [
            'usuario' => [
                'cod_usuarios' => $usuario->cod_usuarios,
                'nom_usu' => $usuario->nom_usu,
                'ape_usu' => $usuario->ape_usu,
                'ci_usu' => $usuario->ci_usu,
                'ext_ci_usu' => $usuario->ext_ci_usu,
                'fec_nac_usu' => optional($usuario->fec_nac_usu)->format('Y-m-d'),
                'fot_usu' => $usuario->fot_usu,
                'cor_usu' => $usuario->cor_usu,
                'est_usu' => $usuario->est_usu,
                'roles' => $usuario->roles->pluck('cod_roles')->map(fn ($id) => (int) $id)->values(),
                'tiene_perfil_nutricionista' => $usuario->nutricionista !== null,
                'tiene_perfil_paciente' => $usuario->paciente !== null,

                'nutricionista' => $usuario->nutricionista ? [
                    'esp_nut' => $usuario->nutricionista->esp_nut,
                    'est_nut' => $usuario->nutricionista->est_nut,
                ] : null,

                'paciente' => $usuario->paciente ? [
                    'sex_pac' => $usuario->paciente->sex_pac,
                    'pes_pac' => $usuario->paciente->pes_pac,
                    'estatura_pac' => $usuario->paciente->estatura_pac,
                    'tip_san_pac' => $usuario->paciente->tip_san_pac,
                    'ale_pac' => $usuario->paciente->ale_pac,
                    'est_pac' => $usuario->paciente->est_pac,
                ] : null,
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

            $rolSuperAdmin = Rol::where('nom_rol', 'SuperAdmin')->first();

            if ($rolSuperAdmin && $usuarioAutenticado->tieneRol('SuperAdmin')) {
                $mantieneSuperAdmin = $rolesSeleccionados->contains((int) $rolSuperAdmin->cod_roles);

                if (! $mantieneSuperAdmin) {
                    return back()->withErrors([
                        'roles' => 'No puedes quitarte a ti mismo el rol SuperAdmin.',
                    ])->withInput();
                }
            }
        }

        $rolNutricionista = Rol::where('nom_rol', 'Nutricionista')->first();
        $rolPaciente = Rol::where('nom_rol', 'Paciente')->first();

        if ($usuario->nutricionista && $rolNutricionista) {
            $mantieneRolNutricionista = $rolesSeleccionados->contains((int) $rolNutricionista->cod_roles);

            if (! $mantieneRolNutricionista) {
                return back()->withErrors([
                    'roles' => 'No puedes quitar el rol Nutricionista a un usuario que ya tiene perfil de nutricionista registrado.',
                ])->withInput();
            }
        }

        if ($usuario->paciente && $rolPaciente) {
            $mantieneRolPaciente = $rolesSeleccionados->contains((int) $rolPaciente->cod_roles);

            if (! $mantieneRolPaciente) {
                return back()->withErrors([
                    'roles' => 'No puedes quitar el rol Paciente a un usuario que ya tiene perfil de paciente registrado.',
                ])->withInput();
            }
        }

        $rutaFoto = $usuario->fot_usu;

        if ($request->hasFile('fot_usu')) {
            if ($usuario->fot_usu && Storage::disk('public')->exists($usuario->fot_usu)) {
                Storage::disk('public')->delete($usuario->fot_usu);
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

        $usuario->roles()->sync($rolesSeleccionados->all());

        if ($usuario->nutricionista) {
            $usuario->nutricionista->update([
                'esp_nut' => $request->esp_nut,
                'est_nut' => $request->est_nut,
            ]);
        }

        if ($usuario->paciente) {
            $usuario->paciente->update([
                'sex_pac' => $request->sex_pac,
                'pes_pac' => $request->pes_pac,
                'estatura_pac' => $request->estatura_pac,
                'tip_san_pac' => $request->tip_san_pac,
                'ale_pac' => $request->ale_pac,
                'est_pac' => $request->est_pac,
            ]);
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Usuario $usuario): RedirectResponse
    {
        $usuarioAutenticado = auth()->user();

        if ($usuarioAutenticado->cod_usuarios === $usuario->cod_usuarios) {
            return back()->withErrors([
                'general' => 'No puedes cambiar tu propio estado desde esta acción rápida.',
            ]);
        }

        $nuevoEstado = match ($usuario->est_usu) {
            'activo' => 'inactivo',
            'inactivo' => 'activo',
            default => 'activo',
        };

        $usuario->update([
            'est_usu' => $nuevoEstado,
        ]);

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }
}