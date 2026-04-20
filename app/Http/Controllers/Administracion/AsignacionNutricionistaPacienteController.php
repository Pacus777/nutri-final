<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAsignacionNutricionistaPacienteRequest;
use App\Http\Requests\UpdateAsignacionNutricionistaPacienteRequest;
use App\Models\AsignacionNutricionistaPaciente;
use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AsignacionNutricionistaPacienteController extends Controller
{
    public function index(): Response
    {
        $asignaciones = AsignacionNutricionistaPaciente::with([
                'nutricionista.usuario',
                'paciente.usuario',
            ])
            ->orderBy('cod_asignacion_nutricionista_paciente', 'desc')
            ->get()
            ->map(function ($asignacion) {
                return [
                    'cod_asignacion_nutricionista_paciente' => $asignacion->cod_asignacion_nutricionista_paciente,
                    'fec_anp' => $asignacion->fec_anp,
                    'est_anp' => $asignacion->est_anp,
                    'nutricionista' => $asignacion->nutricionista ? [
                        'cod_nutricionistas' => $asignacion->nutricionista->cod_nutricionistas,
                        'esp_nut' => $asignacion->nutricionista->esp_nut,
                        'usuario' => $asignacion->nutricionista->usuario ? [
                            'nom_usu' => $asignacion->nutricionista->usuario->nom_usu,
                            'cor_usu' => $asignacion->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                    'paciente' => $asignacion->paciente ? [
                        'cod_pacientes' => $asignacion->paciente->cod_pacientes,
                        'usuario' => $asignacion->paciente->usuario ? [
                            'nom_usu' => $asignacion->paciente->usuario->nom_usu,
                            'cor_usu' => $asignacion->paciente->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('AsignacionesNutricionistaPaciente/Index', [
            'asignaciones' => $asignaciones,
        ]);
    }

    public function create(): Response
    {
        $nutricionistas = Nutricionista::with('usuario')
            ->where('est_nut', 'activo')
            ->whereHas('usuario', function ($query) {
                $query->where('est_usu', 'activo');
            })
            ->orderBy('cod_nutricionistas')
            ->get()
            ->map(function ($nutricionista) {
                return [
                    'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                    'esp_nut' => $nutricionista->esp_nut,
                    'usuario' => [
                        'nom_usu' => $nutricionista->usuario->nom_usu,
                        'cor_usu' => $nutricionista->usuario->cor_usu,
                    ],
                ];
            });

        $pacientes = Paciente::with('usuario')
            ->where('est_pac', 'activo')
            ->whereHas('usuario', function ($query) {
                $query->where('est_usu', 'activo');
            })
            ->orderBy('cod_pacientes')
            ->get()
            ->map(function ($paciente) {
                return [
                    'cod_pacientes' => $paciente->cod_pacientes,
                    'usuario' => [
                        'nom_usu' => $paciente->usuario->nom_usu,
                        'cor_usu' => $paciente->usuario->cor_usu,
                    ],
                ];
            });

        return Inertia::render('AsignacionesNutricionistaPaciente/Create', [
            'nutricionistas' => $nutricionistas,
            'pacientes' => $pacientes,
            'estados' => ['activa', 'cerrada', 'reasignada'],
        ]);
    }

    public function store(StoreAsignacionNutricionistaPacienteRequest $request): RedirectResponse
    {
        $paciente = Paciente::with('asignacionActiva')->findOrFail($request->cod_pacientes_anp);

        if ($paciente->asignacionActiva) {
            return back()->withErrors([
                'cod_pacientes_anp' => 'El paciente ya tiene una asignación activa. Debes cerrarla o reasignarla antes de crear una nueva.',
            ])->withInput();
        }

        AsignacionNutricionistaPaciente::create([
            'cod_nutricionistas_anp' => $request->cod_nutricionistas_anp,
            'cod_pacientes_anp' => $request->cod_pacientes_anp,
            'fec_anp' => $request->fec_anp,
            'est_anp' => $request->est_anp,
        ]);

        return redirect()
            ->route('asignaciones.index')
            ->with('success', 'Asignación registrada correctamente.');
    }

    public function historialPaciente(Paciente $paciente): Response
    {
        $paciente->load(['usuario', 'asignacionesNutricionista.nutricionista.usuario']);

        $historial = $paciente->asignacionesNutricionista
            ->sortByDesc('cod_asignacion_nutricionista_paciente')
            ->values()
            ->map(function ($asignacion) {
                return [
                    'cod_asignacion_nutricionista_paciente' => $asignacion->cod_asignacion_nutricionista_paciente,
                    'fec_anp' => $asignacion->fec_anp,
                    'est_anp' => $asignacion->est_anp,
                    'nutricionista' => $asignacion->nutricionista ? [
                        'cod_nutricionistas' => $asignacion->nutricionista->cod_nutricionistas,
                        'esp_nut' => $asignacion->nutricionista->esp_nut,
                        'usuario' => $asignacion->nutricionista->usuario ? [
                            'nom_usu' => $asignacion->nutricionista->usuario->nom_usu,
                            'cor_usu' => $asignacion->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('AsignacionesNutricionistaPaciente/HistorialPaciente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ] : null,
            ],
            'historial' => $historial,
        ]);
    }

    public function actualizarEstado(
        UpdateAsignacionNutricionistaPacienteRequest $request,
        AsignacionNutricionistaPaciente $asignacion
    ): RedirectResponse {
        if ($asignacion->est_anp !== 'activa') {
            return back()->withErrors([
                'general' => 'Solo se puede cerrar o reasignar una asignación que esté activa.',
            ]);
        }

        if (! in_array($request->est_anp, ['cerrada', 'reasignada'], true)) {
            return back()->withErrors([
                'est_anp' => 'Solo puedes cambiar una asignación activa a cerrada o reasignada.',
            ]);
        }

        $asignacion->update([
            'est_anp' => $request->est_anp,
        ]);

        return back()->with('success', 'Estado de asignación actualizado correctamente.');
    }
}