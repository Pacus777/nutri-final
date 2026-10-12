<?php

namespace App\Http\Controllers\Administracion;



use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNutricionistaRapidoRequest;
use App\Http\Requests\StoreNutricionistaRequest;
use App\Http\Requests\UpdateNutricionistaRequest;
use App\Models\ObjetivoNutricional;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use App\Models\EvaluacionInicial;
use App\Models\ContextoPaciente;
use App\Models\PatronAlimenticio;
use App\Models\PrediccionMl;
use App\Models\FeatureNutricional;
use App\Services\AnalisisInteligentePacienteService;
use App\Services\MlInferenceService;
use App\Services\MotorAnalisisNutricionalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class NutricionistaController extends Controller
{
    public function __construct(
        private readonly AnalisisInteligentePacienteService $analisisInteligenteService,
        private readonly MlInferenceService $mlInferenceService,
        private readonly MotorAnalisisNutricionalService $motorAnalisisService
    ) {}

    public function index(): Response
    {
        $nutricionistas = Nutricionista::query()
            ->with(['usuario.roles'])
            ->withCount([
                'asignaciones as asignaciones_count',
                'pacientesActivos as pacientes_activos_count',
            ])
            ->orderByDesc('cod_nutricionistas')
            ->get()
            ->map(fn (Nutricionista $nutricionista) => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'est_nut' => $nutricionista->est_nut,
                'pacientes_activos_count' => $nutricionista->pacientes_activos_count,
                'asignaciones_count' => $nutricionista->asignaciones_count,
                'usuario' => $nutricionista->usuario ? [
                    'cod_usuarios' => $nutricionista->usuario->cod_usuarios,
                    'nom_usu' => $nutricionista->usuario->nom_usu,
                    'ape_usu' => $nutricionista->usuario->ape_usu,
                    'nombre_completo' => $nutricionista->usuario->nombre_completo,
                    'cor_usu' => $nutricionista->usuario->cor_usu,
                    'est_usu' => $nutricionista->usuario->est_usu,
                    'roles' => $nutricionista->usuario->roles->map(fn ($rol) => [
                        'cod_roles' => $rol->cod_roles,
                        'nom_rol' => $rol->nom_rol,
                    ]),
                ] : null,
            ]);

            
        return Inertia::render('Nutricionistas/Index', [
            'nutricionistas' => $nutricionistas,
        ]);
    }

    public function create(): Response
    {
        $usuarios = Usuario::query()
            ->where('est_usu', 'activo')
            ->whereDoesntHave('nutricionista')
            ->whereDoesntHave('paciente')
            ->orderBy('nom_usu')
            ->get(['cod_usuarios', 'nom_usu', 'ape_usu', 'cor_usu'])
            ->map(fn (Usuario $usuario) => [
                'cod_usuarios' => $usuario->cod_usuarios,
                'nom_usu' => $usuario->nom_usu,
                'ape_usu' => $usuario->ape_usu,
                'nombre_completo' => $usuario->nombre_completo,
                'cor_usu' => $usuario->cor_usu,
            ]);

        // Detectar si es la ruta admin
        $isAdmin = request()->route()->getName() === 'admin.nutricionistas.create';
        $component = $isAdmin ? 'Admin/Nutricionistas/Create' : 'Nutricionistas/Create';

        


        return Inertia::render($component, [
            'usuarios' => $usuarios,
            'estados' => ['activo', 'inactivo'],
        ]);
    }

    public function store(StoreNutricionistaRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $nutricionista = Nutricionista::create([
                'cod_usuarios_nut' => $request->integer('cod_usuarios_nut'),
                'esp_nut' => $request->input('esp_nut'),
                'est_nut' => $request->input('est_nut'),
            ]);

            $usuario = Usuario::query()->findOrFail($nutricionista->cod_usuarios_nut);
            $rolNutricionista = Rol::query()->where('nom_rol', 'Nutricionista')->first();

            if ($rolNutricionista && ! $usuario->roles()->where('roles.cod_roles', $rolNutricionista->cod_roles)->exists()) {
                $usuario->roles()->attach($rolNutricionista->cod_roles);
            }
        });

        // Detectar si se llamó desde la ruta admin
        $isAdmin = request()->route()->getName() === 'admin.nutricionistas.store';
        $redirectRoute = $isAdmin ? 'admin.nutricionistas.index' : 'nutricionistas.index';

        return redirect()->route($redirectRoute)->with('success', 'Nutricionista registrado correctamente.');
    }

    public function show(Nutricionista $nutricionista): Response
    {
        $nutricionista->load([
            'usuario.roles',
            'pacientesActivos.usuario',
            'asignaciones.paciente.usuario',
        ]);

        return Inertia::render('Nutricionistas/Show', [
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'est_nut' => $nutricionista->est_nut,
                'fecha_registro' => optional($nutricionista->created_at)->format('Y-m-d'),
                'pacientes_activos' => $nutricionista->pacientesActivos->map(fn (Paciente $paciente) => [
                    'cod_pacientes' => $paciente->cod_pacientes,
                    'sex_pac' => $paciente->sex_pac,
                    'est_pac' => $paciente->est_pac,
                    'usuario' => $paciente->usuario ? [
                        'nom_usu' => $paciente->usuario->nom_usu,
                        'ape_usu' => $paciente->usuario->ape_usu,
                        'nombre_completo' => $paciente->usuario->nombre_completo,
                        'cor_usu' => $paciente->usuario->cor_usu,
                    ] : null,
                ]),
                'historial_asignaciones' => $nutricionista->asignaciones->map(fn ($asignacion) => [
                    'cod_asignacion_nutricionista_paciente' => $asignacion->cod_asignacion_nutricionista_paciente,
                    'fec_anp' => optional($asignacion->fec_anp)->format('Y-m-d'),
                    'est_anp' => $asignacion->est_anp,
                    'paciente' => $asignacion->paciente ? [
                        'cod_pacientes' => $asignacion->paciente->cod_pacientes,
                        'usuario' => $asignacion->paciente->usuario ? [
                            'nom_usu' => $asignacion->paciente->usuario->nom_usu,
                            'ape_usu' => $asignacion->paciente->usuario->ape_usu,
                            'cor_usu' => $asignacion->paciente->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ]),
                'usuario' => $nutricionista->usuario ? [
                    'cod_usuarios' => $nutricionista->usuario->cod_usuarios,
                    'nom_usu' => $nutricionista->usuario->nom_usu,
                    'ape_usu' => $nutricionista->usuario->ape_usu,
                    'nombre_completo' => $nutricionista->usuario->nombre_completo,
                    'ci_usu' => $nutricionista->usuario->ci_usu,
                    'ext_ci_usu' => $nutricionista->usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($nutricionista->usuario->fec_nac_usu)->format('Y-m-d'),
                    'fot_usu' => $nutricionista->usuario->fot_usu,
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
        DB::transaction(function () use ($request, $nutricionista) {
            $usuario = $nutricionista->usuario;
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

            $nutricionista->update([
                'esp_nut' => $request->input('esp_nut'),
                'est_nut' => $request->input('est_nut'),
            ]);
        });

        return redirect()->route('nutricionistas.index')->with('success', 'Nutricionista actualizado correctamente.');
    }

    public function activar(Nutricionista $nutricionista): RedirectResponse
    {
        DB::transaction(function () use ($nutricionista) {
            $nutricionista->update(['est_nut' => 'activo']);
            $nutricionista->usuario?->update(['est_usu' => 'activo']);
        });

        return back()->with('success', 'Nutricionista activado correctamente.');
    }

    public function inactivar(Nutricionista $nutricionista): RedirectResponse
    {
        if ($nutricionista->pacientesActivos()->exists()) {
            return back()->withErrors([
                'general' => 'No puedes inactivar un nutricionista que tiene pacientes activos asignados.',
            ]);
        }

        DB::transaction(function () use ($nutricionista) {
            $nutricionista->update(['est_nut' => 'inactivo']);
            $nutricionista->usuario?->update(['est_usu' => 'inactivo']);
        });

        return back()->with('success', 'Nutricionista inactivado correctamente.');
    }

    public function cambiarEstado(Nutricionista $nutricionista): RedirectResponse
    {
        return $nutricionista->estaActivo() ? $this->inactivar($nutricionista) : $this->activar($nutricionista);
    }

    public function destroy(Nutricionista $nutricionista): RedirectResponse
    {
        return $this->inactivar($nutricionista);
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

            $rolNutricionista = Rol::query()->where('nom_rol', 'Nutricionista')->first();

            if ($rolNutricionista) {
                $usuario->roles()->syncWithoutDetaching([$rolNutricionista->cod_roles]);
            }

            Nutricionista::create([
                'cod_usuarios_nut' => $usuario->cod_usuarios,
                'esp_nut' => $request->input('esp_nut'),
                'est_nut' => $request->input('est_nut'),
            ]);
        });

        return redirect()->route('nutricionistas.index')->with('success', 'Nutricionista creado correctamente desde registro rápido.');
    }

    public function misPacientes(): Response
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;

        abort_unless($nutricionista, 404);

        $pacientes = $nutricionista->pacientesActivos()
            ->with('usuario')
            ->orderByDesc('pacientes.cod_pacientes')
            ->paginate(8);

        $pacientes->getCollection()->transform(fn (Paciente $paciente) => [
            'cod_pacientes' => $paciente->cod_pacientes,
            'sex_pac' => $paciente->sex_pac,
            'est_pac' => $paciente->est_pac,
            'usuario' => $paciente->usuario ? [
                'nom_usu' => $paciente->usuario->nom_usu,
                'ape_usu' => $paciente->usuario->ape_usu,
                'cor_usu' => $paciente->usuario->cor_usu,
            ] : null,
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

    public function showPacienteAsignado(Paciente $paciente): Response|RedirectResponse
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;

        if (! $nutricionista) {
            return redirect()->route('dashboard')->withErrors([
                'general' => 'No se pudo identificar tu perfil de nutricionista.',
            ]);
        }

        $paciente->load(['usuario.roles', 'asignacionActiva.nutricionista.usuario', 'asignaciones.nutricionista.usuario', 'evaluacionInicialActiva']);

        $objetivosDisponibles = ObjetivoNutricional::query()
            ->with(['paciente.usuario'])
            ->where('est_obe', 'activo')
            ->where(function ($query) use ($nutricionista, $paciente) {
                $query->whereNull('cod_pacientes_obe')
                    ->orWhere('cod_pacientes_obe', $paciente->cod_pacientes);

                if ($nutricionista) {
                    $query->orWhere('cod_nutricionistas_obe', $nutricionista->cod_nutricionistas)
                        ->orWhereNull('cod_nutricionistas_obe');
                }
            })
            ->orderByDesc('cod_objetivos_nutricionales')
            ->get()
            ->map(fn (ObjetivoNutricional $objetivo) => [
                'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
                'des_obe' => $objetivo->des_obe,
                'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
                'est_obe' => $objetivo->est_obe,
                'paciente' => $objetivo->paciente ? [
                    'cod_pacientes' => $objetivo->paciente->cod_pacientes,
                    'usuario' => $objetivo->paciente->usuario ? [
                        'nombre_completo' => $objetivo->paciente->usuario->nombre_completo,
                    ] : null,
                ] : null,
            ])
            ->values();

        $asignadoAlUsuario = $paciente->asignacionActiva && (int) $paciente->asignacionActiva->cod_nutricionistas_anp === (int) $nutricionista->cod_nutricionistas;

        if (! $asignadoAlUsuario && ! $usuario->isAdmin()) {
            return redirect()->route('nutricionista.pacientes.index')->withErrors([
                'general' => 'No tienes permiso para ver esta ficha.',
            ]);
        }


        $contextos = ContextoPaciente::query()
            ->where('cod_pacientes_cxp', $paciente->getKey())
            ->recientes()
            ->get();

        $contextosActivos = $contextos
            ->where('est_cxp', ContextoPaciente::ESTADO_ACTIVO);

        $contextosCriticos = $contextosActivos
            ->filter(fn (ContextoPaciente $contexto) => $contexto->esClinicamenteCritico())
            ->values();

        $contextosNoCriticosRecientes = $contextosActivos
            ->reject(fn (ContextoPaciente $contexto) => $contexto->esClinicamenteCritico())
            ->take(3)
            ->values();

        $ultimoContexto = $contextos->first();

        $pacienteMapeado = [
            'cod_pacientes' => $paciente->cod_pacientes,
            'sex_pac' => $paciente->sex_pac,
            'pes_pac' => $paciente->pes_pac,
            'estatura_pac' => $paciente->estatura_pac,
            'tip_san_pac' => $paciente->tip_san_pac,
            'ale_pac' => $paciente->ale_pac,
            'est_pac' => $paciente->est_pac,
            'edad_pac' => $paciente->edad,
            'usuario' => $paciente->usuario ? [
                'nom_usu' => $paciente->usuario->nom_usu,
                'ape_usu' => $paciente->usuario->ape_usu,
                'cor_usu' => $paciente->usuario->cor_usu,
                'ci_usu' => $paciente->usuario->ci_usu,
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
            'historial_asignaciones' => $paciente->asignaciones->map(fn ($asignacion) => [
                'cod_asignacion_nutricionista_paciente' => $asignacion->cod_asignacion_nutricionista_paciente,
                'fec_anp' => optional($asignacion->fec_anp)->format('Y-m-d'),
                'est_anp' => $asignacion->est_anp,
                'nutricionista' => $asignacion->nutricionista ? [
                    'cod_nutricionistas' => $asignacion->nutricionista->cod_nutricionistas,
                    'esp_nut' => $asignacion->nutricionista->esp_nut,
                    'usuario' => $asignacion->nutricionista->usuario ? [
                        'nom_usu' => $asignacion->nutricionista->usuario->nom_usu,
                        'cor_usu' => $asignacion->nutricionista->usuario->cor_usu,
                    ] : null,
                ] : null,
            ]),
        ];

        $evaluacionInicial = $paciente->evaluacionInicialActiva;


        $objetivos = ObjetivoNutricional::query()
            ->where('cod_pacientes_obe', $paciente->getKey())
            ->with('nutricionista.usuario')
            ->latest('fec_obe')
            ->latest('cod_objetivos_nutricionales')
            ->get();

        $objetivosActivos = $objetivos
            ->where('est_obe', ObjetivoNutricional::ESTADO_ACTIVO)
            ->values();

        $objetivosPausados = $objetivos
            ->where('est_obe', ObjetivoNutricional::ESTADO_PAUSADO)
            ->values();

        $objetivosFinalizados = $objetivos
            ->whereIn('est_obe', [
                ObjetivoNutricional::ESTADO_CUMPLIDO,
                ObjetivoNutricional::ESTADO_CERRADO,
                ObjetivoNutricional::ESTADO_CANCELADO,
            ])
            ->values();

        $ultimoObjetivo = $objetivos->first();


        return Inertia::render('Nutricionistas/PacientesShow', [
            'paciente' => $pacienteMapeado,
            'historial_peso' => $paciente->getHistorialPeso(),
            'objetivos_disponibles' => $objetivosDisponibles,
            'evaluacion_inicial' => $evaluacionInicial ? [
                'cod_evaluaciones_iniciales' => $evaluacionInicial->cod_evaluaciones_iniciales,
                'fec_evi' => optional($evaluacionInicial->fec_evi)->format('Y-m-d'),
                'est_evi' => $evaluacionInicial->est_evi,
                'peso_evi' => $evaluacionInicial->peso_evi,
                'estatura_evi' => $evaluacionInicial->estatura_evi,
                'imc_evi' => $evaluacionInicial->imc_evi,
                'objetivo_inicial_evi' => $evaluacionInicial->objetivo_inicial_evi,
                'restricciones_evi' => $evaluacionInicial->restricciones_evi,
                'contexto_general_evi' => $evaluacionInicial->contexto_general_evi,
                'obs_evi' => $evaluacionInicial->obs_evi,
            ] : null,
            'contextos_resumen' => [
                'total' => $contextos->count(),
                'activos' => $contextosActivos->count(),
                'alta_prioridad' => $contextosCriticos->count(),
                'ultimo' => $contextos->first() ? $this->mapContextoPacienteFicha($contextos->first()) : null,
                'criticos' => $contextosCriticos
                    ->map(fn (ContextoPaciente $contexto) => $this->mapContextoPacienteFicha($contexto))
                    ->values(),
                'recientes' => $contextosNoCriticosRecientes
                    ->map(fn (ContextoPaciente $contexto) => $this->mapContextoPacienteFicha($contexto))
                    ->values(),
            ],

            'objetivos_resumen' => [
                'total' => $objetivos->count(),
                'activos' => $objetivosActivos->count(),
                'pausados' => $objetivosPausados->count(),
                'finalizados' => $objetivosFinalizados->count(),
                'ultimo' => $ultimoObjetivo ? [
                    'cod_objetivos_nutricionales' => $ultimoObjetivo->cod_objetivos_nutricionales,
                    'des_obe' => $ultimoObjetivo->des_obe,
                    'fec_obe' => optional($ultimoObjetivo->fec_obe)->format('Y-m-d'),
                    'est_obe' => $ultimoObjetivo->est_obe,
                    'nutricionista' => $ultimoObjetivo->nutricionista ? [
                        'cod_nutricionistas' => $ultimoObjetivo->nutricionista->cod_nutricionistas,
                        'esp_nut' => $ultimoObjetivo->nutricionista->esp_nut,
                        'usuario' => $ultimoObjetivo->nutricionista->usuario ? [
                            'nombre_completo' => $ultimoObjetivo->nutricionista->usuario->nombre_completo,
                            'cor_usu' => $ultimoObjetivo->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ] : null,
                'recientes' => $objetivos->take(3)->map(fn ($objetivo) => [
                    'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
                    'des_obe' => $objetivo->des_obe,
                    'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
                    'est_obe' => $objetivo->est_obe,
                    'nutricionista' => $objetivo->nutricionista ? [
                        'cod_nutricionistas' => $objetivo->nutricionista->cod_nutricionistas,
                        'esp_nut' => $objetivo->nutricionista->esp_nut,
                        'usuario' => $objetivo->nutricionista->usuario ? [
                            'nombre_completo' => $objetivo->nutricionista->usuario->nombre_completo,
                            'cor_usu' => $objetivo->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ])->values(),
            ],
            'analisisInteligente' => $this->analisisInteligenteService->generar($paciente),
            'motor_analisis_inteligente' => $this->motorAnalisisService->orquestarAnalisisCompleto($paciente),
            'modelo_kmeans_candidato' => $this->prepararModeloKMeansParaVista(),
            'prediccion_kmeans_paciente' => $this->prepararPrediccionPacienteParaVista($paciente),
            'modelo_dt_candidato' => $this->prepararModeloDecisionTreeParaVista(),
            'prediccion_dt_paciente' => $this->prepararPrediccionDecisionTreePacienteParaVista($paciente),
            'patrones_disponibles' => PatronAlimenticio::query()->orderBy('nom_pat')->get(['cod_patrones_alimenticios', 'nom_pat', 'des_pat']),
            'etiqueta_experimental_ml' => MlInferenceService::ETIQUETA_EXPERIMENTAL,
        ]);
    }

    private function prepararModeloKMeansParaVista(): ?array
    {
        $modelo = $this->mlInferenceService->getModeloKMeansCandidato();
        if (!$modelo) {
            return null;
        }

        return [
            'cod_modelos_ml' => $modelo->cod_modelos_ml,
            'nombre_mml' => $modelo->nombre_mml,
            'version_mml' => $modelo->version_mml,
            'version_features_mml' => $modelo->version_features_mml,
            'origen_dataset_mml' => $modelo->origen_dataset_mml,
            'est_mml' => $modelo->est_mml,
            'numero_muestras_mml' => $modelo->numero_muestras_mml,
            'random_seed_mml' => $modelo->random_seed_mml,
            'metricas_rendimiento_mml' => $modelo->metricas_rendimiento_mml,
            'features_requeridas_mml' => $modelo->features_requeridas_mml,
            'clusters' => $modelo->clusters->map(fn ($cluster) => [
                'cod_clusters_modelo' => $cluster->cod_clusters_modelo,
                'numero_cluster_clm' => $cluster->numero_cluster_clm,
                'cod_patrones_alimenticios_clm' => $cluster->cod_patrones_alimenticios_clm,
                'centroide_json_clm' => $cluster->centroide_json_clm,
                'descripcion_automatica_clm' => $cluster->descripcion_automatica_clm,
                'descripcion_profesional_clm' => $cluster->descripcion_profesional_clm,
                'estado_validacion_clm' => $cluster->estado_validacion_clm,
                'fec_validacion_clm' => optional($cluster->fec_validacion_clm)->format('Y-m-d H:i'),
                'patron_alimenticio' => $cluster->patronAlimenticio ? [
                    'cod_patrones_alimenticios' => $cluster->patronAlimenticio->cod_patrones_alimenticios,
                    'nom_pat' => $cluster->patronAlimenticio->nom_pat,
                    'des_pat' => $cluster->patronAlimenticio->des_pat,
                ] : null,
            ])->values(),
        ];
    }

    private function prepararPrediccionPacienteParaVista(Paciente $paciente): ?array
    {
        $modelo = $this->mlInferenceService->getModeloKMeansCandidato();
        if (!$modelo) {
            return null;
        }

        // Buscar última feature de 7 días del paciente
        $latestFeature = FeatureNutricional::query()
            ->where('cod_pacientes_fnu', $paciente->cod_pacientes)
            ->where('ventana_dias_fnu', 7)
            ->latest('fec_fin_fnu')
            ->first();

        if (!$latestFeature) {
            return null;
        }

        // Obtener o generar predicción
        $prediccion = PrediccionMl::query()
            ->where('cod_pacientes_pml', $paciente->cod_pacientes)
            ->where('cod_modelos_ml_pml', $modelo->cod_modelos_ml)
            ->where('cod_features_nutricionales_pml', $latestFeature->cod_features_nutricionales)
            ->first();

        if (!$prediccion) {
            try {
                $prediccion = $this->mlInferenceService->guardarPrediccion($latestFeature, $modelo);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $clusterNumero = null;
        if (preg_match('/Cluster\s+(\d+)/', (string) $prediccion->resultado_principal_pml, $m)) {
            $clusterNumero = (int) $m[1];
        }

        $clusterDef = $modelo->clusters->firstWhere('numero_cluster_clm', $clusterNumero);

        return [
            'cod_predicciones_ml' => $prediccion->cod_predicciones_ml,
            'resultado_principal_pml' => $prediccion->resultado_principal_pml,
            'fec_prediccion_pml' => optional($prediccion->fec_prediccion_pml)->format('Y-m-d H:i'),
            'score_confianza_pml' => $prediccion->score_confianza_pml,
            'explicabilidad_pml' => $prediccion->explicabilidad_pml,
            'resultado_validado_pml' => $prediccion->resultado_validado_pml,
            'estado_validacion_pml' => $prediccion->estado_validacion_pml,
            'fec_validacion_pml' => optional($prediccion->fec_validacion_pml)->format('Y-m-d H:i'),
            'obs_validacion_pml' => $prediccion->obs_validacion_pml,
            'cluster_definicion' => $clusterDef ? [
                'cod_clusters_modelo' => $clusterDef->cod_clusters_modelo,
                'numero_cluster_clm' => $clusterDef->numero_cluster_clm,
                'descripcion_automatica_clm' => $clusterDef->descripcion_automatica_clm,
                'descripcion_profesional_clm' => $clusterDef->descripcion_profesional_clm,
                'estado_validacion_clm' => $clusterDef->estado_validacion_clm,
                'patron_alimenticio' => $clusterDef->patronAlimenticio ? [
                    'cod_patrones_alimenticios' => $clusterDef->patronAlimenticio->cod_patrones_alimenticios,
                    'nom_pat' => $clusterDef->patronAlimenticio->nom_pat,
                    'des_pat' => $clusterDef->patronAlimenticio->des_pat,
                ] : null,
            ] : null,
            'snapshot' => [
                'cod_features_nutricionales' => $latestFeature->cod_features_nutricionales,
                'fec_inicio_fnu' => optional($latestFeature->fec_inicio_fnu)->format('Y-m-d'),
                'fec_fin_fnu' => optional($latestFeature->fec_fin_fnu)->format('Y-m-d'),
                'adherencia_registro_fnu' => (float) $latestFeature->adherencia_registro_fnu,
                'ratio_validacion_fnu' => (float) $latestFeature->ratio_validacion_fnu,
                'dias_registrados_fnu' => $latestFeature->dias_registrados_fnu,
            ],
        ];
    }

    private function prepararModeloDecisionTreeParaVista(): ?array
    {
        $modelo = $this->mlInferenceService->getModeloDecisionTreeCandidato();
        if (!$modelo) {
            return null;
        }

        return [
            'cod_modelos_ml' => $modelo->cod_modelos_ml,
            'nombre_mml' => $modelo->nombre_mml,
            'version_mml' => $modelo->version_mml,
            'version_features_mml' => $modelo->version_features_mml,
            'origen_dataset_mml' => $modelo->origen_dataset_mml,
            'est_mml' => $modelo->est_mml,
            'numero_muestras_mml' => $modelo->numero_muestras_mml,
            'random_seed_mml' => $modelo->random_seed_mml,
            'metricas_rendimiento_mml' => $modelo->metricas_rendimiento_mml,
            'features_requeridas_mml' => $modelo->features_requeridas_mml,
        ];
    }

    private function prepararPrediccionDecisionTreePacienteParaVista(Paciente $paciente): ?array
    {
        $modelo = $this->mlInferenceService->getModeloDecisionTreeCandidato();
        if (!$modelo) {
            return null;
        }

        $latestFeature = FeatureNutricional::query()
            ->where('cod_pacientes_fnu', $paciente->cod_pacientes)
            ->where('ventana_dias_fnu', 7)
            ->latest('fec_fin_fnu')
            ->first();

        if (!$latestFeature) {
            return null;
        }

        $prediccion = PrediccionMl::query()
            ->where('cod_pacientes_pml', $paciente->cod_pacientes)
            ->where('cod_modelos_ml_pml', $modelo->cod_modelos_ml)
            ->where('cod_features_nutricionales_pml', $latestFeature->cod_features_nutricionales)
            ->first();

        if (!$prediccion) {
            try {
                $prediccion = $this->mlInferenceService->guardarPrediccionDecisionTree($latestFeature, $modelo);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return [
            'cod_predicciones_ml' => $prediccion->cod_predicciones_ml,
            'resultado_principal_pml' => $prediccion->resultado_principal_pml,
            'fec_prediccion_pml' => optional($prediccion->fec_prediccion_pml)->format('Y-m-d H:i'),
            'score_confianza_pml' => (float) $prediccion->score_confianza_pml,
            'probabilidades_clases_pml' => $prediccion->probabilidades_clases_pml,
            'explicabilidad_pml' => $prediccion->explicabilidad_pml,
            'resultado_validado_pml' => $prediccion->resultado_validado_pml,
            'estado_validacion_pml' => $prediccion->estado_validacion_pml,
            'fec_validacion_pml' => optional($prediccion->fec_validacion_pml)->format('Y-m-d H:i'),
            'obs_validacion_pml' => $prediccion->obs_validacion_pml,
            'snapshot' => [
                'cod_features_nutricionales' => $latestFeature->cod_features_nutricionales,
                'fec_inicio_fnu' => optional($latestFeature->fec_inicio_fnu)->format('Y-m-d'),
                'fec_fin_fnu' => optional($latestFeature->fec_fin_fnu)->format('Y-m-d'),
                'adherencia_registro_fnu' => (float) $latestFeature->adherencia_registro_fnu,
                'ratio_validacion_fnu' => (float) $latestFeature->ratio_validacion_fnu,
                'dias_registrados_fnu' => $latestFeature->dias_registrados_fnu,
                'calidad_ventana' => $latestFeature->adherencia_registro_fnu >= 0.7143 ? 'Suficiente (>=5/7)' : ($latestFeature->adherencia_registro_fnu >= 0.5714 ? 'Limitada (4/7)' : 'Insuficiente (<4/7)'),
            ],
        ];
    }

    private function mapContextoPacienteFicha(ContextoPaciente $contexto): array
    {
        return [
            'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
            'cod_pacientes_cxp' => $contexto->cod_pacientes_cxp,
            'tip_cxp' => $contexto->tip_cxp,
            'pri_cxp' => $contexto->pri_cxp,
            'des_cxp' => $contexto->des_cxp,
            'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
            'est_cxp' => $contexto->est_cxp,
            'es_critico' => $contexto->esClinicamenteCritico(),
        ];
    }

    public function asignarObjetivoPaciente(Paciente $paciente, ObjetivoNutricional $objetivo): RedirectResponse
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;

        if (! $nutricionista) {
            return redirect()->route('dashboard')->withErrors([
                'general' => 'No se pudo identificar tu perfil de nutricionista.',
            ]);
        }

        if ($objetivo->cod_nutricionistas_obe && (int) $objetivo->cod_nutricionistas_obe !== (int) $nutricionista->cod_nutricionistas) {
            return back()->withErrors([
                'general' => 'No puedes asignar un objetivo creado por otro nutricionista.',
            ]);
        }

        if ($objetivo->cod_pacientes_obe && (int) $objetivo->cod_pacientes_obe !== (int) $paciente->cod_pacientes) {
            return back()->withErrors([
                'general' => 'Ese objetivo ya está asignado a otro paciente.',
            ]);
        }

        $objetivo->update([
            'cod_pacientes_obe' => $paciente->cod_pacientes,
            'cod_nutricionistas_obe' => $objetivo->cod_nutricionistas_obe ?? $nutricionista->cod_nutricionistas,
        ]);

        return back()->with('success', 'Objetivo nutricional asignado correctamente.');
    }
}
