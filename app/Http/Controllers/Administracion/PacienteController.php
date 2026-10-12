<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacienteRapidoRequest;
use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PacienteController extends Controller
{
    public function index(): Response
    {
        $pacientes = Paciente::query()
            ->with(['usuario.roles', 'asignacionActiva.nutricionista.usuario'])
            ->orderByDesc('cod_pacientes')
            ->get()
            ->map(fn (Paciente $paciente) => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'sex_pac' => $paciente->sex_pac,
                'pes_pac' => $paciente->pes_pac,
                'estatura_pac' => $paciente->estatura_pac,
                'tip_san_pac' => $paciente->tip_san_pac,
                'ale_pac' => $paciente->ale_pac,
                'est_pac' => $paciente->est_pac,
                'edad_pac' => $paciente->edad,
                'usuario' => $paciente->usuario ? [
                    'cod_usuarios' => $paciente->usuario->cod_usuarios,
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'ape_usu' => $paciente->usuario->ape_usu,
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'ci_usu' => $paciente->usuario->ci_usu,
                    'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($paciente->usuario->fec_nac_usu)->format('Y-m-d'),
                    'fot_usu' => $paciente->usuario->fot_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                    'est_usu' => $paciente->usuario->est_usu,
                    'roles' => $paciente->usuario->roles->map(fn ($rol) => [
                        'cod_roles' => $rol->cod_roles,
                        'nom_rol' => $rol->nom_rol,
                    ]),
                ] : null,
                'nutricionista_actual' => $paciente->asignacionActiva ? [
                    'cod_asignacion_nutricionista_paciente' => $paciente->asignacionActiva->cod_asignacion_nutricionista_paciente,
                    'fec_anp' => optional($paciente->asignacionActiva->fec_anp)->format('Y-m-d'),
                    'nutricionista' => $paciente->asignacionActiva->nutricionista ? [
                        'cod_nutricionistas' => $paciente->asignacionActiva->nutricionista->cod_nutricionistas,
                        'esp_nut' => $paciente->asignacionActiva->nutricionista->esp_nut,
                        'usuario' => $paciente->asignacionActiva->nutricionista->usuario ? [
                            'nom_usu' => $paciente->asignacionActiva->nutricionista->usuario->nom_usu,
                            'cor_usu' => $paciente->asignacionActiva->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ] : null,
            ]);

        return Inertia::render('Pacientes/Index', [
            'pacientes' => $pacientes,
        ]);
    }

    public function create(): Response
    {
        $usuarios = Usuario::query()
            ->where('est_usu', 'activo')
            ->whereDoesntHave('paciente')
            ->whereDoesntHave('nutricionista')
            ->orderBy('nom_usu')
            ->get(['cod_usuarios', 'nom_usu', 'ape_usu', 'cor_usu'])
            ->map(fn (Usuario $usuario) => [
                'cod_usuarios' => $usuario->cod_usuarios,
                'nom_usu' => $usuario->nom_usu,
                'ape_usu' => $usuario->ape_usu,
                'nombre_completo' => $usuario->nombre_completo,
                'cor_usu' => $usuario->cor_usu,
            ]);

        return Inertia::render('Pacientes/Create', [
            'usuarios' => $usuarios,
            'estados' => ['activo', 'inactivo'],
            'sexos' => ['masculino', 'femenino', 'otro'],
        ]);
    }

    public function store(StorePacienteRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $paciente = Paciente::create([
                'cod_usuarios_pac' => $request->integer('cod_usuarios_pac'),
                'sex_pac' => $request->input('sex_pac'),
                'pes_pac' => $request->input('pes_pac'),
                'estatura_pac' => $request->input('estatura_pac'),
                'tip_san_pac' => $request->input('tip_san_pac'),
                'ale_pac' => $request->input('ale_pac'),
                'est_pac' => $request->input('est_pac'),
            ]);

            $usuario = Usuario::query()->findOrFail($paciente->cod_usuarios_pac);
            $rolPaciente = Rol::query()->where('nom_rol', 'Paciente')->first();

            if ($rolPaciente && ! $usuario->roles()->where('roles.cod_roles', $rolPaciente->cod_roles)->exists()) {
                $usuario->roles()->attach($rolPaciente->cod_roles);
            }
        });

        return redirect()->route('pacientes.index')->with('success', 'Paciente registrado correctamente.');
    }

    public function guardarRegistroRapido(StorePacienteRapidoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $usuario = Usuario::create([
                'nom_usu' => $request->input('nom_usu'),
                'ape_usu' => $request->input('ape_usu'),
                'ci_usu' => $request->input('ci_usu'),
                'ext_ci_usu' => $request->input('ext_ci_usu'),
                'fec_nac_usu' => $request->input('fec_nac_usu'),
                'fot_usu' => $request->hasFile('fot_usu') ? $request->file('fot_usu')->store('usuarios', 'public') : null,
                'cor_usu' => $request->input('cor_usu'),
                'cla_usu' => Hash::make($request->input('cla_usu')),
                'est_usu' => $request->input('est_usu'),
            ]);

            $rolPaciente = Rol::query()->where('nom_rol', 'Paciente')->first();

            if ($rolPaciente) {
                $usuario->roles()->syncWithoutDetaching([$rolPaciente->cod_roles]);
            }

            Paciente::create([
                'cod_usuarios_pac' => $usuario->cod_usuarios,
                'sex_pac' => $request->input('sex_pac'),
                'pes_pac' => $request->input('pes_pac'),
                'estatura_pac' => $request->input('estatura_pac'),
                'tip_san_pac' => $request->input('tip_san_pac'),
                'ale_pac' => $request->input('ale_pac'),
                'est_pac' => $request->input('est_pac'),
            ]);
        });

        return redirect()->route('pacientes.index')->with('success', 'Paciente creado correctamente desde registro rápido.');
    }

    public function show(Paciente $paciente): Response
    {
        $paciente->load([
            'usuario.roles',
            'asignacionActiva.nutricionista.usuario',
            'asignaciones.nutricionista.usuario',
            'evaluacionInicialActiva',
            'planNutricionalVigente',
        ]);

        return Inertia::render('Pacientes/Show', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'sex_pac' => $paciente->sex_pac,
                'pes_pac' => $paciente->pes_pac,
                'estatura_pac' => $paciente->estatura_pac,
                'tip_san_pac' => $paciente->tip_san_pac,
                'ale_pac' => $paciente->ale_pac,
                'est_pac' => $paciente->est_pac,
                'edad_pac' => $paciente->edad,
                'fecha_registro' => optional($paciente->created_at)->format('Y-m-d'),
                'nutricionista_actual' => $paciente->asignacionActiva ? [
                    'cod_asignacion_nutricionista_paciente' => $paciente->asignacionActiva->cod_asignacion_nutricionista_paciente,
                    'fec_anp' => optional($paciente->asignacionActiva->fec_anp)->format('Y-m-d'),
                    'nutricionista' => $paciente->asignacionActiva->nutricionista ? [
                        'cod_nutricionistas' => $paciente->asignacionActiva->nutricionista->cod_nutricionistas,
                        'esp_nut' => $paciente->asignacionActiva->nutricionista->esp_nut,
                        'usuario' => $paciente->asignacionActiva->nutricionista->usuario ? [
                            'nom_usu' => $paciente->asignacionActiva->nutricionista->usuario->nom_usu,
                            'ape_usu' => $paciente->asignacionActiva->nutricionista->usuario->ape_usu,
                            'cor_usu' => $paciente->asignacionActiva->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ] : null,
                'historial_asignaciones' => $paciente->asignaciones->map(fn ($asignacion) => [
                    'cod_asignacion_nutricionista_paciente' => $asignacion->cod_asignacion_nutricionista_paciente,
                    'fec_anp' => optional($asignacion->fec_anp)->format('Y-m-d'),
                    'est_anp' => $asignacion->est_anp,
                    'nutricionista' => $asignacion->nutricionista ? [
                        'cod_nutricionistas' => $asignacion->nutricionista->cod_nutricionistas,
                        'esp_nut' => $asignacion->nutricionista->esp_nut,
                        'usuario' => $asignacion->nutricionista->usuario ? [
                            'nom_usu' => $asignacion->nutricionista->usuario->nom_usu,
                            'ape_usu' => $asignacion->nutricionista->usuario->ape_usu,
                            'cor_usu' => $asignacion->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ]),
                'usuario' => $paciente->usuario ? [
                    'cod_usuarios' => $paciente->usuario->cod_usuarios,
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'ape_usu' => $paciente->usuario->ape_usu,
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'ci_usu' => $paciente->usuario->ci_usu,
                    'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($paciente->usuario->fec_nac_usu)->format('Y-m-d'),
                    'fot_usu' => $paciente->usuario->fot_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                    'est_usu' => $paciente->usuario->est_usu,
                    'roles' => $paciente->usuario->roles->map(fn ($rol) => [
                        'cod_roles' => $rol->cod_roles,
                        'nom_rol' => $rol->nom_rol,
                    ]),
                ] : null,
                'evaluacion_inicial_activa' => $paciente->evaluacionInicialActiva ? [
                    'cod_evaluaciones_iniciales' => $paciente->evaluacionInicialActiva->cod_evaluaciones_iniciales,
                    'fec_evi' => optional($paciente->evaluacionInicialActiva->fec_evi)->format('Y-m-d'),
                    'est_evi' => $paciente->evaluacionInicialActiva->est_evi,
                ] : null,
                'plan_nutricional_vigente' => $paciente->planNutricionalVigente ? [
                    'cod_planes_nutricionales' => $paciente->planNutricionalVigente->cod_planes_nutricionales,
                    'fec_pan' => optional($paciente->planNutricionalVigente->fec_pan)->format('Y-m-d'),
                    'est_pan' => $paciente->planNutricionalVigente->est_pan,
                ] : null,
                'historial_peso' => $paciente->getHistorialPeso(),
            ],
        ]);
    }

    public function edit(Paciente $paciente): Response
    {
        $paciente->load('usuario');

        return Inertia::render('Pacientes/Edit', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'sex_pac' => $paciente->sex_pac,
                'pes_pac' => $paciente->pes_pac,
                'estatura_pac' => $paciente->estatura_pac,
                'tip_san_pac' => $paciente->tip_san_pac,
                'ale_pac' => $paciente->ale_pac,
                'est_pac' => $paciente->est_pac,
                'usuario' => $paciente->usuario ? [
                    'cod_usuarios' => $paciente->usuario->cod_usuarios,
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'ape_usu' => $paciente->usuario->ape_usu,
                    'ci_usu' => $paciente->usuario->ci_usu,
                    'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($paciente->usuario->fec_nac_usu)->format('Y-m-d'),
                    'fot_usu' => $paciente->usuario->fot_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                    'est_usu' => $paciente->usuario->est_usu,
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                ] : null,
            ],
            'estadosUsuario' => ['activo', 'inactivo', 'bloqueado'],
            'estadosPaciente' => ['activo', 'inactivo'],
            'sexos' => ['masculino', 'femenino', 'otro'],
        ]);
    }

    public function update(UpdatePacienteRequest $request, Paciente $paciente): RedirectResponse
    {
        if ($paciente->tieneAsignacionActiva() && $request->input('est_pac') !== 'activo') {
            return back()->withErrors([
                'est_pac' => 'No puedes inactivar un paciente con una asignación activa.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $paciente) {
            $usuario = $paciente->usuario;
            $rutaFoto = $usuario->fot_usu;

            if ($request->hasFile('fot_usu')) {
                if ($usuario->fot_usu && Storage::disk('public')->exists($usuario->fot_usu)) {
                    Storage::disk('public')->delete($usuario->fot_usu);
                }

                $rutaFoto = $request->file('fot_usu')->store('usuarios', 'public');
            }

            $usuario->update([
                'nom_usu' => $request->input('nom_usu'),
                'ape_usu' => $request->input('ape_usu'),
                'ci_usu' => $request->input('ci_usu'),
                'ext_ci_usu' => $request->input('ext_ci_usu'),
                'fec_nac_usu' => $request->input('fec_nac_usu'),
                'fot_usu' => $rutaFoto,
                'cor_usu' => $request->input('cor_usu'),
                'est_usu' => $request->input('est_usu'),
            ]);

            if ($request->filled('cla_usu')) {
                $usuario->update(['cla_usu' => Hash::make($request->input('cla_usu'))]);
            }

            $paciente->update([
                'sex_pac' => $request->input('sex_pac'),
                'pes_pac' => $request->input('pes_pac'),
                'estatura_pac' => $request->input('estatura_pac'),
                'tip_san_pac' => $request->input('tip_san_pac'),
                'ale_pac' => $request->input('ale_pac'),
                'est_pac' => $request->input('est_pac'),
            ]);
        });

        return redirect()->route('pacientes.index')->with('success', 'Paciente actualizado correctamente.');
    }

    public function activar(Paciente $paciente): RedirectResponse
    {
        DB::transaction(function () use ($paciente) {
            $paciente->update(['est_pac' => 'activo']);
            $paciente->usuario?->update(['est_usu' => 'activo']);
        });

        return back()->with('success', 'Paciente activado correctamente.');
    }

    public function inactivar(Paciente $paciente): RedirectResponse
    {
        if ($paciente->tieneAsignacionActiva()) {
            return back()->withErrors([
                'general' => 'No puedes inactivar un paciente que tiene una asignación activa.',
            ]);
        }

        DB::transaction(function () use ($paciente) {
            $paciente->update(['est_pac' => 'inactivo']);
            $paciente->usuario?->update(['est_usu' => 'inactivo']);
        });

        return back()->with('success', 'Paciente inactivado correctamente.');
    }

    public function cambiarEstado(Paciente $paciente): RedirectResponse
    {
        return $paciente->estaActivo() ? $this->inactivar($paciente) : $this->activar($paciente);
    }

    public function destroy(Paciente $paciente): RedirectResponse
    {
        return $this->inactivar($paciente);
    }

    public function fichaPropia(): Response|RedirectResponse
    {
        $paciente = Auth::user()?->paciente;

        if (! $paciente) {
            return redirect()->route('dashboard')->withErrors([
                'general' => 'No se encontró una ficha clínica asociada a tu usuario.',
            ]);
        }

        $paciente->load([
            'usuario.roles',
            'asignacionActiva.nutricionista.usuario',
            'asignaciones.nutricionista.usuario',
        ]);

        return $this->show($paciente);
    }
}
