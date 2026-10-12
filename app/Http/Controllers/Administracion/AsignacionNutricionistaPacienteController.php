<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\CerrarAsignacionNutricionistaPacienteRequest;
use App\Http\Requests\ReasignarPacienteRequest;
use App\Http\Requests\StoreAsignacionNutricionistaPacienteRequest;
use App\Models\AsignacionNutricionistaPaciente;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Services\AsignacionPacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AsignacionNutricionistaPacienteController extends Controller
{
    public function __construct(protected AsignacionPacienteService $service)
    {
    }

    public function index(): Response
    {
        $asignaciones = AsignacionNutricionistaPaciente::query()
            ->with(['nutricionista.usuario', 'paciente.usuario'])
            ->orderByDesc('cod_asignacion_nutricionista_paciente')
            ->get()
            ->map(fn (AsignacionNutricionistaPaciente $asignacion) => [
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
                'paciente' => $asignacion->paciente ? [
                    'cod_pacientes' => $asignacion->paciente->cod_pacientes,
                    'sexo' => $asignacion->paciente->sex_pac,
                    'usuario' => $asignacion->paciente->usuario ? [
                        'nom_usu' => $asignacion->paciente->usuario->nom_usu,
                        'ape_usu' => $asignacion->paciente->usuario->ape_usu,
                        'cor_usu' => $asignacion->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
            ]);

        return Inertia::render('AsignacionesNutricionistaPaciente/Index', [
            'asignaciones' => $asignaciones,
        ]);
    }

    public function create(): Response
    {
        $nutricionistas = Nutricionista::query()
            ->with('usuario')
            ->where('est_nut', 'activo')
            ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'))
            ->orderBy('cod_nutricionistas')
            ->get()
            ->map(fn (Nutricionista $nutricionista) => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'usuario' => [
                    'nom_usu' => $nutricionista->usuario->nom_usu,
                    'ape_usu' => $nutricionista->usuario->ape_usu,
                    'cor_usu' => $nutricionista->usuario->cor_usu,
                ],
            ]);

        $pacientes = Paciente::query()
            ->with(['usuario', 'asignacionActiva.nutricionista.usuario'])
            ->where('est_pac', \App\Enums\EstadoPerfil::ACTIVO)
            ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'))
            ->orderBy('cod_pacientes')
            ->get()
            ->map(fn (Paciente $paciente) => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'ape_usu' => $paciente->usuario->ape_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
                'nutricionista_activo' => $paciente->asignacionActiva && $paciente->asignacionActiva->nutricionista && $paciente->asignacionActiva->nutricionista->usuario
                    ? $paciente->asignacionActiva->nutricionista->usuario->nom_usu . ' ' . $paciente->asignacionActiva->nutricionista->usuario->ape_usu
                    : null,
            ]);

        $pacientesSinAsignacion = Paciente::query()
            ->with('usuario')
            ->where('est_pac', \App\Enums\EstadoPerfil::ACTIVO)
            ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'))
            ->whereDoesntHave('asignaciones', fn ($query) => $query->where('est_anp', 'activa'))
            ->orderByDesc('cod_pacientes')
            ->limit(3)
            ->get()
            ->map(fn (Paciente $paciente) => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'ape_usu' => $paciente->usuario->ape_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
            ]);

        return Inertia::render('AsignacionesNutricionistaPaciente/Create', [
            'nutricionistas' => $nutricionistas,
            'pacientes' => $pacientes,
            'pacientesSinAsignacion' => $pacientesSinAsignacion,
            'estados' => ['activa', 'cerrada', 'reasignada'],
        ]);
    }

    public function store(StoreAsignacionNutricionistaPacienteRequest $request): RedirectResponse
    {
        $this->service->asignarPaciente(
            $request->integer('cod_pacientes_anp'),
            $request->integer('cod_nutricionistas_anp'),
            $request->input('fec_anp')
        );

        return redirect()->route('asignaciones.index')->with('success', 'Asignación registrada correctamente.');
    }

    public function show(AsignacionNutricionistaPaciente $asignacion): Response
    {
        $asignacion->load(['nutricionista.usuario', 'paciente.usuario']);

        return Inertia::render('AsignacionesNutricionistaPaciente/Show', [
            'asignacion' => [
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
                'paciente' => $asignacion->paciente ? [
                    'cod_pacientes' => $asignacion->paciente->cod_pacientes,
                    'usuario' => $asignacion->paciente->usuario ? [
                        'nom_usu' => $asignacion->paciente->usuario->nom_usu,
                        'ape_usu' => $asignacion->paciente->usuario->ape_usu,
                        'cor_usu' => $asignacion->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function historialPaciente(Paciente $paciente): Response
    {
        $paciente->load(['usuario', 'asignaciones.nutricionista.usuario', 'asignacionActiva.nutricionista.usuario']);

        return Inertia::render('AsignacionesNutricionistaPaciente/HistorialPaciente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nom_usu' => $paciente->usuario->nom_usu,
                    'ape_usu' => $paciente->usuario->ape_usu,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ] : null,
                'asignacion_activa' => $paciente->asignacionActiva ? [
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
            ],
            'historial' => $paciente->asignaciones->sortByDesc('cod_asignacion_nutricionista_paciente')->values()->map(fn ($asignacion) => [
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
        ]);
    }

    public function cerrar(CerrarAsignacionNutricionistaPacienteRequest $request, AsignacionNutricionistaPaciente $asignacion): RedirectResponse
    {
        $this->service->cerrarAsignacion($asignacion->cod_asignacion_nutricionista_paciente, null, $request->input('observacion'));

        return back()->with('success', 'Asignación cerrada correctamente.');
    }

    public function reasignar(ReasignarPacienteRequest $request): RedirectResponse
    {
        $this->service->reasignarPaciente(
            $request->integer('cod_pacientes_anp'),
            $request->integer('cod_nutricionistas_anp'),
            $request->input('fec_anp'),
            $request->input('observacion')
        );

        return redirect()->route('asignaciones.index')->with('success', 'Paciente reasignado correctamente.');
    }

    public function actualizarEstado(\App\Http\Requests\UpdateAsignacionNutricionistaPacienteRequest $request, AsignacionNutricionistaPaciente $asignacion): RedirectResponse
    {
        if ($request->input('est_anp') === 'cerrada') {
            $asignacion->loadMissing(['paciente.usuario', 'nutricionista.usuario']);

            $this->service->cerrarAsignacion($asignacion->cod_asignacion_nutricionista_paciente);

            return back()->with('success', 'Asignación cerrada correctamente.');
        }

        if ($request->input('est_anp') === 'reasignada') {
            return back()->withErrors([
                'general' => 'Usa el flujo de reasignación para cerrar y crear una nueva asignación activa.',
            ]);
        }

        return back()->withErrors([
            'est_anp' => 'Solo se puede cerrar o reasignar una asignación activa.',
        ]);
    }

    public function pacientesAsignados(): Response
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista()->firstOrFail();

        $pacientes = $nutricionista->pacientesActivos()
            ->with('usuario')
            ->orderByDesc('pacientes.cod_pacientes')
            ->paginate(8);

        $pacientes->getCollection()->transform(fn (Paciente $paciente) => [
            'cod_pacientes' => $paciente->cod_pacientes,
            'sexo' => $paciente->sex_pac,
            'est_pac' => $paciente->est_pac,
            'usuario' => $paciente->usuario ? [
                'nom_usu' => $paciente->usuario->nom_usu,
                'ape_usu' => $paciente->usuario->ape_usu,
                'cor_usu' => $paciente->usuario->cor_usu,
            ] : null,
            'adherencia' => $paciente->calcularEstadoAdherencia(),
        ]);

        return Inertia::render('Nutricionistas/PacientesIndex', [
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'usuario' => [
                    'nom_usu' => $usuario->nom_usu,
                    'cor_usu' => $usuario->cor_usu,
                ],
            ],
            'pacientes' => $pacientes,
        ]);
    }
}
