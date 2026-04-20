<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\StorePacienteRapidoRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PacienteController extends Controller
{
    public function index(): Response
    {
        $pacientes = Paciente::with(['usuario.roles'])
            ->orderBy('cod_pacientes', 'desc')
            ->get()
            ->map(function ($paciente) {
                return [
                    'cod_pacientes' => $paciente->cod_pacientes,
                    'sex_pac' => $paciente->sex_pac,
                    'pes_pac' => $paciente->pes_pac,
                    'estatura_pac' => $paciente->estatura_pac,
                    'tip_san_pac' => $paciente->tip_san_pac,
                    'ale_pac' => $paciente->ale_pac,
                    'est_pac' => $paciente->est_pac,
                    'fecha_registro' => optional($paciente->created_at)->format('Y-m-d'),
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
                ];
            });

        return Inertia::render('Pacientes/Index', [
            'pacientes' => $pacientes,
        ]);
    }

    public function create(): Response
    {
        $usuarios = Usuario::whereDoesntHave('paciente')
            ->where('est_usu', 'activo')
            ->orderBy('nom_usu')
            ->get(['cod_usuarios', 'nom_usu', 'ape_usu', 'cor_usu']);

        return Inertia::render('Pacientes/Create', [
            'usuarios' => $usuarios->map(fn ($usuario) => [
                'cod_usuarios' => $usuario->cod_usuarios,
                'nom_usu' => $usuario->nom_usu,
                'ape_usu' => $usuario->ape_usu,
                'nombre_completo' => $usuario->nombre_completo,
                'cor_usu' => $usuario->cor_usu,
            ]),
            'estados' => ['activo', 'inactivo'],
            'sexos' => ['masculino', 'femenino', 'otro'],
        ]);
    }

    public function store(StorePacienteRequest $request): RedirectResponse
    {
        Paciente::create([
            'cod_usuarios_pac' => $request->cod_usuarios_pac,
            'sex_pac' => $request->sex_pac,
            'pes_pac' => $request->pes_pac,
            'estatura_pac' => $request->estatura_pac,
            'tip_san_pac' => $request->tip_san_pac,
            'ale_pac' => $request->ale_pac,
            'est_pac' => $request->est_pac,
        ]);

        $usuario = Usuario::findOrFail($request->cod_usuarios_pac);
        $rolPaciente = Rol::where('nom_rol', 'Paciente')->first();

        if ($rolPaciente && ! $usuario->roles()->where('roles.cod_roles', $rolPaciente->cod_roles)->exists()) {
            $usuario->roles()->attach($rolPaciente->cod_roles);
        }

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Paciente registrado correctamente.');
    }

    public function guardarRegistroRapido(StorePacienteRapidoRequest $request): RedirectResponse
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

        $rolPaciente = Rol::where('nom_rol', 'Paciente')->first();

        if ($rolPaciente) {
            $usuario->roles()->syncWithoutDetaching([$rolPaciente->cod_roles]);
        }

        Paciente::create([
            'cod_usuarios_pac' => $usuario->cod_usuarios,
            'sex_pac' => $request->sex_pac,
            'pes_pac' => $request->pes_pac,
            'estatura_pac' => $request->estatura_pac,
            'tip_san_pac' => $request->tip_san_pac,
            'ale_pac' => $request->ale_pac,
            'est_pac' => $request->est_pac,
        ]);

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Paciente creado correctamente desde registro rápido.');
    }

    public function show(Paciente $paciente): Response
    {
        $paciente->load(['usuario.roles']);

        return Inertia::render('Pacientes/Show', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'sex_pac' => $paciente->sex_pac,
                'pes_pac' => $paciente->pes_pac,
                'estatura_pac' => $paciente->estatura_pac,
                'tip_san_pac' => $paciente->tip_san_pac,
                'ale_pac' => $paciente->ale_pac,
                'est_pac' => $paciente->est_pac,
                'fecha_registro' => optional($paciente->created_at)->format('Y-m-d'),
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
        $usuario = $paciente->usuario;

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

        $paciente->update([
            'sex_pac' => $request->sex_pac,
            'pes_pac' => $request->pes_pac,
            'estatura_pac' => $request->estatura_pac,
            'tip_san_pac' => $request->tip_san_pac,
            'ale_pac' => $request->ale_pac,
            'est_pac' => $request->est_pac,
        ]);

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Paciente actualizado correctamente.');
    }

    public function cambiarEstado(Paciente $paciente): RedirectResponse
    {
        $nuevoEstado = $paciente->est_pac === 'activo' ? 'inactivo' : 'activo';

        $paciente->update([
            'est_pac' => $nuevoEstado,
        ]);

        return back()->with('success', 'Estado del paciente actualizado correctamente.');
    }
}