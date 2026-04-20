<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
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
                    'fec_pac' => $paciente->fec_pac,
                    'eda_pac' => $paciente->eda_pac,
                    'sex_pac' => $paciente->sex_pac,
                    'pes_pac' => $paciente->pes_pac,
                    'tal_pac' => $paciente->tal_pac,
                    'est_pac' => $paciente->est_pac,
                    'usuario' => $paciente->usuario ? [
                        'cod_usuarios' => $paciente->usuario->cod_usuarios,
                        'nom_usu' => $paciente->usuario->nom_usu,
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
            ->get(['cod_usuarios', 'nom_usu', 'cor_usu']);

        return Inertia::render('Pacientes/Create', [
            'usuarios' => $usuarios,
            'estados' => ['activo', 'inactivo'],
            'sexos' => ['masculino', 'femenino', 'otro'],
        ]);
    }

    public function store(StorePacienteRequest $request): RedirectResponse
    {
        Paciente::create([
            'cod_usuarios_pac' => $request->cod_usuarios_pac,
            'fec_pac' => $request->fec_pac,
            'eda_pac' => $request->eda_pac,
            'sex_pac' => $request->sex_pac,
            'pes_pac' => $request->pes_pac,
            'tal_pac' => $request->tal_pac,
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

    public function show(Paciente $paciente): Response
    {
        $paciente->load(['usuario.roles']);

        return Inertia::render('Pacientes/Show', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'fec_pac' => $paciente->fec_pac,
                'eda_pac' => $paciente->eda_pac,
                'sex_pac' => $paciente->sex_pac,
                'pes_pac' => $paciente->pes_pac,
                'tal_pac' => $paciente->tal_pac,
                'est_pac' => $paciente->est_pac,
                'usuario' => $paciente->usuario ? [
                    'cod_usuarios' => $paciente->usuario->cod_usuarios,
                    'nom_usu' => $paciente->usuario->nom_usu,
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
        $usuarios = Usuario::where(function ($query) use ($paciente) {
                $query->whereDoesntHave('paciente')
                    ->orWhere('cod_usuarios', $paciente->cod_usuarios_pac);
            })
            ->where('est_usu', 'activo')
            ->orderBy('nom_usu')
            ->get(['cod_usuarios', 'nom_usu', 'cor_usu']);

        return Inertia::render('Pacientes/Edit', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'cod_usuarios_pac' => $paciente->cod_usuarios_pac,
                'fec_pac' => $paciente->fec_pac,
                'eda_pac' => $paciente->eda_pac,
                'sex_pac' => $paciente->sex_pac,
                'pes_pac' => $paciente->pes_pac,
                'tal_pac' => $paciente->tal_pac,
                'est_pac' => $paciente->est_pac,
            ],
            'usuarios' => $usuarios,
            'estados' => ['activo', 'inactivo'],
            'sexos' => ['masculino', 'femenino', 'otro'],
        ]);
    }

    public function update(UpdatePacienteRequest $request, Paciente $paciente): RedirectResponse
    {
        $paciente->update([
            'cod_usuarios_pac' => $request->cod_usuarios_pac,
            'fec_pac' => $request->fec_pac,
            'eda_pac' => $request->eda_pac,
            'sex_pac' => $request->sex_pac,
            'pes_pac' => $request->pes_pac,
            'tal_pac' => $request->tal_pac,
            'est_pac' => $request->est_pac,
        ]);

        $usuario = Usuario::findOrFail($request->cod_usuarios_pac);
        $rolPaciente = Rol::where('nom_rol', 'Paciente')->first();

        if ($rolPaciente && ! $usuario->roles()->where('roles.cod_roles', $rolPaciente->cod_roles)->exists()) {
            $usuario->roles()->attach($rolPaciente->cod_roles);
        }

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