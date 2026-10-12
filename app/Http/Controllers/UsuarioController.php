<?php

namespace App\Http\Controllers;

use App\Services\UsuarioRegistroService;

use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Paciente;
use App\Models\Nutricionista;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{


    protected function rolesAdministrativos(): array
    {
        return ['SuperAdmin', 'Administrador'];
    }

    protected function existeOtroAdministrador(Usuario $usuario, array $rolesAExcluir = []): bool
    {
        $rolesAdministrativos = collect($this->rolesAdministrativos())
            ->reject(fn ($rol) => in_array($rol, $rolesAExcluir, true))
            ->values();

        if ($rolesAdministrativos->isEmpty()) {
            return false;
        }

        return Usuario::query()
            ->where('est_usu', 'activo')
            ->whereHas('roles', function ($query) use ($rolesAdministrativos) {
                $query->whereIn('nom_rol', $rolesAdministrativos->all())
                    ->where('est_rol', 'activo');
            })
            ->where('cod_usuarios', '!=', $usuario->cod_usuarios)
            ->exists();
    }

    protected function puedeRetirarRolesAdministrativos(Usuario $usuario, array $nuevosRoles): bool
    {
        $rolesAdministrativos = collect($this->rolesAdministrativos());

        if (! $usuario->roles->pluck('nom_rol')->intersect($rolesAdministrativos)->isNotEmpty()) {
            return true;
        }

        $mantieneRolAdministrativo = collect($nuevosRoles)
            ->map(fn ($rol) => (int) $rol)
            ->pipe(function ($ids) use ($rolesAdministrativos) {
                return Rol::query()
                    ->whereIn('cod_roles', $ids->all())
                    ->whereIn('nom_rol', $rolesAdministrativos->all())
                    ->where('est_rol', 'activo')
                    ->exists();
            });

        if ($mantieneRolAdministrativo) {
            return true;
        }

        return $this->existeOtroAdministrador($usuario, $usuario->roles->pluck('nom_rol')->all());
    }

    public function index(Request $request): Response
    {
        $buscar = $request->input('buscar');

        $query = Usuario::with(['roles', 'nutricionista', 'paciente'])
            ->orderBy('cod_usuarios', 'desc');

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nom_usu', 'like', "%{$buscar}%")
                    ->orWhere('ape_usu', 'like', "%{$buscar}%")
                    ->orWhere('cor_usu', 'like', "%{$buscar}%")
                    ->orWhere('ci_usu', 'like', "%{$buscar}%")
                    ->orWhere('cod_usuarios', 'like', "%{$buscar}%");
            });
        }

        $usuarios = $query->paginate(10)
            ->withQueryString()
            ->through(function ($usuario) {
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

        $resumen = [
            'total' => Usuario::count(),
            'activos' => Usuario::where('est_usu', 'activo')->count(),
            'inactivos' => Usuario::where('est_usu', 'inactivos')->orWhere('est_usu', 'inactivo')->count(),
            'bloqueados' => Usuario::where('est_usu', 'bloqueado')->count(),
        ];

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => [
                'buscar' => $buscar,
            ],
            'resumenGlobal' => $resumen,
        ]);
    }

    public function show(Usuario $usuario): Response
    {
        $usuario->load(['roles', 'nutricionista', 'paciente']);

        return Inertia::render('Usuarios/Show', [
            'usuario' => [
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
                'fecha_registro' => optional($usuario->created_at)->format('Y-m-d'),
                'roles' => $usuario->roles->map(fn ($rol) => [
                    'cod_roles' => $rol->cod_roles,
                    'nom_rol' => $rol->nom_rol,
                ]),
                'nutricionista' => $usuario->nutricionista ? [
                    'cod_nutricionistas' => $usuario->nutricionista->cod_nutricionistas,
                    'esp_nut' => $usuario->nutricionista->esp_nut,
                    'est_nut' => $usuario->nutricionista->est_nut,
                ] : null,
                'paciente' => $usuario->paciente ? [
                    'cod_pacientes' => $usuario->paciente->cod_pacientes,
                    'sex_pac' => $usuario->paciente->sex_pac,
                    'pes_pac' => $usuario->paciente->pes_pac,
                    'estatura_pac' => $usuario->paciente->estatura_pac,
                    'tip_san_pac' => $usuario->paciente->tip_san_pac,
                    'ale_pac' => $usuario->paciente->ale_pac,
                    'est_pac' => $usuario->paciente->est_pac,
                ] : null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Usuarios/Create', [
            'estados' => ['activo', 'inactivo', 'bloqueado'],
        ]);
    }

    public function store(
        StoreUsuarioRequest $request,
        UsuarioRegistroService $usuarioRegistroService
    ): RedirectResponse {
        $usuarioCreado = $usuarioRegistroService->crear(
            $request->validated(),
            $request->file('fot_usu')
        );

        $creador = auth()->user();
        $creadorName = $creador ? $creador->nom_usu : 'Sistema';
        Log::info("ACTIVIDAD: El usuario '{$creadorName}' ha creado un nuevo usuario en el sistema (Nombre: {$request->nom_usu} {$request->ape_usu}, Email: {$request->cor_usu}).");

        return redirect()
            ->route('admin.usuarios.index')
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
        }

        if (! $this->puedeRetirarRolesAdministrativos($usuario, $rolesSeleccionados->all())) {
            return back()->withErrors([
                'roles' => 'No puedes retirar el último rol administrador del sistema.',
            ])->withInput();
        }

        if (in_array($request->est_usu, ['inactivo', 'bloqueado'], true)) {
            if ($usuario->paciente && $usuario->paciente->tieneAsignacionActiva()) {
                return back()->withErrors([
                    'est_usu' => 'No puedes inactivar o bloquear un usuario que tiene un perfil de paciente con asignación activa.',
                ])->withInput();
            }

            if ($usuario->nutricionista && $usuario->nutricionista->pacientesActivos()->exists()) {
                return back()->withErrors([
                    'est_usu' => 'No puedes inactivar o bloquear un usuario que tiene un perfil de nutricionista con pacientes activos asignados.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($request, $usuario, $rolesSeleccionados) {
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
                    'est_nut' => Usuario::estadoPerfilDesdeUsuario($request->est_usu),
                ]);
            }

            if ($usuario->paciente) {
                $usuario->paciente->update([
                    'sex_pac' => $request->sex_pac,
                    'pes_pac' => $request->pes_pac,
                    'estatura_pac' => $request->estatura_pac,
                    'tip_san_pac' => $request->tip_san_pac,
                    'ale_pac' => $request->ale_pac,
                    'est_pac' => Usuario::estadoPerfilDesdeUsuario($request->est_usu),
                ]);
            }
        });

        $editor = auth()->user();
        $editorName = $editor ? $editor->nom_usu : 'Sistema';
        Log::info("ACTIVIDAD: El usuario '{$editorName}' ha editado los datos del usuario '{$usuario->nom_usu} {$usuario->ape_usu}' (ID: {$usuario->cod_usuarios}, Email: {$usuario->cor_usu}).");

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Usuario $usuario): RedirectResponse
    {
        $usuarioAutenticado = auth()->user();

        if ($usuarioAutenticado->cod_usuarios === $usuario->cod_usuarios) {
            return back()->withErrors([
                'general' => 'No puedes cambiar tu propio estado desde esta acción rápida.',
            ]);
        }

        if (
            $usuario->roles()->pluck('nom_rol')->intersect($this->rolesAdministrativos())->isNotEmpty()
            && in_array($request->input('est_usu'), ['inactivo', 'bloqueado'], true)
            && ! $this->existeOtroAdministrador($usuario)
        ) {
            return back()->withErrors([
                'est_usu' => 'No puedes desactivar al último usuario administrador activo del sistema.',
            ]);
        }

        $estadoSolicitado = $request->input('est_usu');

        if (in_array($estadoSolicitado, ['activo', 'inactivo', 'bloqueado'], true)) {
            $nuevoEstado = $estadoSolicitado;
        } else {
            $nuevoEstado = match ($usuario->est_usu->value) {
                'activo' => 'inactivo',
                'inactivo' => 'activo',
                default => 'activo',
            };
        }

        if (in_array($nuevoEstado, ['inactivo', 'bloqueado'], true)) {
            if ($usuario->paciente && $usuario->paciente->tieneAsignacionActiva()) {
                return back()->withErrors([
                    'general' => 'No puedes inactivar o bloquear un usuario que tiene un perfil de paciente con asignación activa.',
                ]);
            }

            if ($usuario->nutricionista && $usuario->nutricionista->pacientesActivos()->exists()) {
                return back()->withErrors([
                    'general' => 'No puedes inactivar o bloquear un usuario que tiene un perfil de nutricionista con pacientes activos asignados.',
                ]);
            }
        }

        DB::transaction(function () use ($usuario, $nuevoEstado) {
            $usuario->update([
                'est_usu' => $nuevoEstado,
            ]);

            if ($usuario->nutricionista) {
                $usuario->nutricionista->update([
                    'est_nut' => Usuario::estadoPerfilDesdeUsuario($nuevoEstado),
                ]);
            }

            if ($usuario->paciente) {
                $usuario->paciente->update([
                    'est_pac' => Usuario::estadoPerfilDesdeUsuario($nuevoEstado),
                ]);
            }
        });

        $editor = auth()->user();
        $editorName = $editor ? $editor->nom_usu : 'Sistema';
        Log::info("ACTIVIDAD: El usuario '{$editorName}' ha cambiado el estado del usuario '{$usuario->nom_usu} {$usuario->ape_usu}' (ID: {$usuario->cod_usuarios}) a '{$nuevoEstado}'.");

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }

    public function asignarRol(AssignRoleRequest $request, Usuario $usuario): RedirectResponse
    {
        $rol = Rol::query()
            ->where('cod_roles', $request->integer('cod_roles_url'))
            ->where('est_rol', 'activo')
            ->firstOrFail();

        DB::transaction(function () use ($usuario, $rol) {
            $usuario->roles()->syncWithoutDetaching([$rol->cod_roles]);
        });

        return back()->with('success', 'Rol asignado correctamente.');
    }

    public function quitarRol(Usuario $usuario, Rol $rol): RedirectResponse
    {
        if (! $usuario->roles()->where('roles.cod_roles', $rol->cod_roles)->exists()) {
            return back()->withErrors([
                'roles' => 'El usuario no tiene asignado ese rol.',
            ]);
        }

        if (in_array($rol->nom_rol, $this->rolesAdministrativos(), true)) {
            $rolesRestantes = $usuario->roles()
                ->whereIn('roles.nom_rol', $this->rolesAdministrativos())
                ->where('roles.cod_roles', '!=', $rol->cod_roles)
                ->exists();

            if (! $rolesRestantes && ! $this->existeOtroAdministrador($usuario, [$rol->nom_rol])) {
                return back()->withErrors([
                    'roles' => 'No puedes eliminar el último rol administrador del sistema.',
                ]);
            }
        }

        DB::transaction(function () use ($usuario, $rol) {
            $usuario->roles()->detach($rol->cod_roles);
        });

        return back()->with('success', 'Rol quitado correctamente.');
    }
}