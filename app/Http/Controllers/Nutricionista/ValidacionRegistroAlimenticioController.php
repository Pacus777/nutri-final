<?php

namespace App\Http\Controllers\Nutricionista;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreValidacionInformacionRequest;
use App\Models\ContextoPaciente;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\ValidacionInformacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ValidacionRegistroAlimenticioController extends Controller
{
    public function pendientes(Request $request): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $registros = RegistroAlimenticio::query()
            ->with(['paciente.usuario'])
            ->withCount('detalles')
            ->where('est_ral', RegistroAlimenticio::ESTADO_CERRADO)
            ->has('detalles')
            ->whereHas('paciente.asignacionesNutricionista', function ($query) use ($nutricionista) {
                $query->where('cod_nutricionistas_anp', $nutricionista->cod_nutricionistas)
                    ->where('est_anp', 'activa');
            })
            ->whereDoesntHave('validacionesInformacion', function ($query) use ($nutricionista) {
                $query->where('cod_nutricionistas_vin', $nutricionista->cod_nutricionistas);
            })
            ->when($request->filled('fecha'), function ($query) use ($request) {
                $query->whereDate('fec_ral', $request->input('fecha'));
            })
            ->latest('fec_ral')
            ->get()
            ->map(fn ($registro) => [
                'cod_registros_alimenticios' => $registro->cod_registros_alimenticios,
                'fec_ral' => optional($registro->fec_ral)->format('Y-m-d'),
                'est_ral' => $registro->est_ral,
                'detalles_count' => $registro->detalles_count,
                'paciente' => $registro->paciente ? [
                    'cod_pacientes' => $registro->paciente->cod_pacientes,
                    'usuario' => $registro->paciente->usuario ? [
                        'nombre_completo' => $registro->paciente->usuario->nombre_completo,
                        'cor_usu' => $registro->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
            ]);

        return Inertia::render('ValidacionesInformacion/Pendientes', [
            'filtroFecha' => $request->input('fecha'),
            'registrosPendientes' => $registros,
        ]);
    }

    public function validar(RegistroAlimenticio $registroAlimenticio): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAsignacionActiva($registroAlimenticio, $nutricionista);
        $this->verificarRegistroEnviadoARevision($registroAlimenticio);

        $registroAlimenticio->load([
            'paciente.usuario',
            'paciente.contextosPaciente' => function ($query) {
                $query->activos()
                    ->altaPrioridad()
                    ->recientes();
            },
            'detalles.alimento.composicionVigente',
        ]);

        $validacionExistente = $registroAlimenticio->validacionesInformacion()
            ->where('cod_nutricionistas_vin', $nutricionista->cod_nutricionistas)
            ->first();

        $resumen = $this->calcularResumenRegistro($registroAlimenticio);
        $contextosCriticos = $registroAlimenticio->paciente?->contextosPaciente ?? collect();

        return Inertia::render('ValidacionesInformacion/ValidarRegistro', [
            'registro' => [
                'cod_registros_alimenticios' => $registroAlimenticio->cod_registros_alimenticios,
                'fec_ral' => optional($registroAlimenticio->fec_ral)->format('Y-m-d'),
                'est_ral' => $registroAlimenticio->est_ral,
                'paciente' => $registroAlimenticio->paciente ? [
                    'cod_pacientes' => $registroAlimenticio->paciente->cod_pacientes,
                    'usuario' => $registroAlimenticio->paciente->usuario ? [
                        'nombre_completo' => $registroAlimenticio->paciente->usuario->nombre_completo,
                        'cor_usu' => $registroAlimenticio->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
                'detalles' => $registroAlimenticio->detalles->map(fn (DetalleRegistroAlimenticio $detalle) => [
                    'cod_detalle_registro_alimenticio' => $detalle->cod_detalle_registro_alimenticio,
                    'tip_dra' => $detalle->tip_dra,
                    'can_dra' => $detalle->can_dra,
                    'uni_dra' => $detalle->uni_dra,
                    'hor_dra' => substr((string) $detalle->hor_dra, 0, 5),
                    'aporte' => $this->mapAporteDetalle($detalle),
                    'alimento' => $detalle->alimento ? [
                        'cod_alimentos' => $detalle->alimento->cod_alimentos,
                        'nom_ali' => $detalle->alimento->nom_ali,
                        'nom_ext_ali' => $detalle->alimento->nom_ext_ali,
                        'mar_ali' => $detalle->alimento->mar_ali,
                        'tip_ali' => $detalle->alimento->tip_ali,
                        'src_ali' => $detalle->alimento->src_ali,
                    ] : null,
                ])->values(),
            ],
            'resumen' => $resumen,
            'contextosCriticos' => $contextosCriticos
                ->map(fn (ContextoPaciente $contexto) => [
                    'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
                    'tip_cxp' => $contexto->tip_cxp,
                    'pri_cxp' => $contexto->pri_cxp,
                    'des_cxp' => $contexto->des_cxp,
                    'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
                    'est_cxp' => $contexto->est_cxp,
                    'es_critico' => $contexto->esClinicamenteCritico(),
                ])
                ->values(),
            'validacionExistente' => $validacionExistente ? [
                'cod_validaciones_informacion' => $validacionExistente->cod_validaciones_informacion,
                'fec_vin' => optional($validacionExistente->fec_vin)->format('Y-m-d'),
                'est_vin' => $validacionExistente->est_vin,
                'obs_vin' => $validacionExistente->obs_vin,
            ] : null,
            'estadosValidacion' => ValidacionInformacion::ESTADOS,
        ]);
    }

    public function guardar(
        StoreValidacionInformacionRequest $request,
        RegistroAlimenticio $registroAlimenticio
    ): RedirectResponse {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAsignacionActiva($registroAlimenticio, $nutricionista);
        $this->verificarRegistroEnviadoARevision($registroAlimenticio);

        $observacion = $request->string('obs_vin')->toString();
        $observacion = trim($observacion) === '' ? null : trim($observacion);

        $validacion = ValidacionInformacion::updateOrCreate(
            [
                'cod_registros_alimenticios_vin' => $registroAlimenticio->cod_registros_alimenticios,
                'cod_nutricionistas_vin' => $nutricionista->cod_nutricionistas,
            ],
            [
                'fec_vin' => $request->fec_vin,
                'est_vin' => $request->est_vin,
                'obs_vin' => $request->est_vin === ValidacionInformacion::ESTADO_ACEPTADO
                    ? null
                    : $observacion,
            ]
        );

        // Notificación al paciente
        $paciente = $registroAlimenticio->paciente;
        $usuarioPaciente = $paciente?->usuario;
        if ($paciente && $usuarioPaciente) {
            $estado = $request->est_vin; // 'aceptado', 'observado', 'rechazado'
            $fecha = optional($registroAlimenticio->fec_ral)->format('Y-m-d') ?? 'de hoy';
            
            $titulo = 'Registro alimenticio validado';
            $tipo = 'registro_validado';
            $descripcion = 'Tu nutricionista ha aprobado con éxito tu registro alimenticio de la fecha ' . $fecha . '.';
            
            if ($estado === ValidacionInformacion::ESTADO_OBSERVADO) {
                $titulo = 'Registro alimenticio observado';
                $tipo = 'registro_observado';
                $descripcion = 'Tu nutricionista ha dejado observaciones en tu registro del ' . $fecha . ': ' . ($observacion ?? 'Ver observaciones');
            } elseif ($estado === ValidacionInformacion::ESTADO_RECHAZADO) {
                $titulo = 'Registro alimenticio rechazado';
                $tipo = 'registro_observado';
                $descripcion = 'Tu nutricionista ha rechazado tu registro alimenticio de la fecha ' . $fecha . '.';
            }

            \App\Models\Notificacion::create([
                'cod_usuarios_not' => $usuarioPaciente->cod_usuarios,
                'tit_not' => $titulo,
                'des_not' => $descripcion,
                'tip_not' => $tipo,
                'url_not' => route('paciente.registros-alimenticios.historial', $paciente->cod_pacientes),
                'est_not' => 'no_leido',
            ]);
        }

        $nutriUser = auth()->user();
        $nutriName = $nutriUser ? $nutriUser->nom_usu : 'Nutricionista #' . $nutricionista->cod_nutricionistas;
        $pacName = $paciente && $paciente->usuario ? $paciente->usuario->nombre_completo : 'Paciente #' . $registroAlimenticio->cod_pacientes_ral;
        Log::info("ACTIVIDAD: El nutricionista '{$nutriName}' ha validado el registro alimenticio del paciente '{$pacName}' para la fecha {$registroAlimenticio->fec_ral} (Estado: {$request->est_vin}, Observaciones: " . ($observacion ?? 'Ninguna') . ").");

        return redirect()
            ->route('nutricionista.validaciones-informacion.pendientes')
            ->with('success', 'Validacion registrada correctamente.');
    }

    public function historialPaciente(Paciente $paciente): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAccesoPaciente($paciente, $nutricionista);

        $paciente->load('usuario');

        $historial = ValidacionInformacion::query()
            ->with('registroAlimenticio')
            ->where('cod_nutricionistas_vin', $nutricionista->cod_nutricionistas)
            ->whereHas('registroAlimenticio', function ($query) use ($paciente) {
                $query->where('cod_pacientes_ral', $paciente->cod_pacientes);
            })
            ->orderByDesc('fec_vin')
            ->orderByDesc('cod_validaciones_informacion')
            ->get()
            ->map(fn ($validacion) => [
                'cod_validaciones_informacion' => $validacion->cod_validaciones_informacion,
                'fec_vin' => optional($validacion->fec_vin)->format('Y-m-d'),
                'est_vin' => $validacion->est_vin,
                'obs_vin' => $validacion->obs_vin,
                'registro_alimenticio' => $validacion->registroAlimenticio ? [
                    'cod_registros_alimenticios' => $validacion->registroAlimenticio->cod_registros_alimenticios,
                    'fec_ral' => optional($validacion->registroAlimenticio->fec_ral)->format('Y-m-d'),
                    'est_ral' => $validacion->registroAlimenticio->est_ral,
                ] : null,
            ]);

        return Inertia::render('ValidacionesInformacion/HistorialPaciente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ] : null,
            ],
            'historial' => $historial,
        ]);
    }

    public function observacionesPaciente(Paciente $paciente): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAccesoPaciente($paciente, $nutricionista);

        $paciente->load('usuario');

        $observaciones = ValidacionInformacion::query()
            ->with('registroAlimenticio')
            ->where('cod_nutricionistas_vin', $nutricionista->cod_nutricionistas)
            ->conObservacion()
            ->whereHas('registroAlimenticio', function ($query) use ($paciente) {
                $query->where('cod_pacientes_ral', $paciente->cod_pacientes);
            })
            ->orderByDesc('fec_vin')
            ->orderByDesc('cod_validaciones_informacion')
            ->get()
            ->map(fn ($validacion) => [
                'cod_validaciones_informacion' => $validacion->cod_validaciones_informacion,
                'fec_vin' => optional($validacion->fec_vin)->format('Y-m-d'),
                'est_vin' => $validacion->est_vin,
                'obs_vin' => $validacion->obs_vin,
                'registro_alimenticio' => $validacion->registroAlimenticio ? [
                    'cod_registros_alimenticios' => $validacion->registroAlimenticio->cod_registros_alimenticios,
                    'fec_ral' => optional($validacion->registroAlimenticio->fec_ral)->format('Y-m-d'),
                ] : null,
            ]);

        return Inertia::render('ValidacionesInformacion/ObservacionesPaciente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ] : null,
            ],
            'observaciones' => $observaciones,
        ]);
    }

    protected function obtenerNutricionistaAutenticado(): Nutricionista
    {
        $usuario = Auth::user();

        if (! $usuario) {
            abort(403, 'No existe una sesion de usuario activa.');
        }

        $nutricionista = Nutricionista::where('cod_usuarios_nut', $usuario->cod_usuarios)
            ->where('est_nut', 'activo')
            ->first();

        if (! $nutricionista) {
            abort(403, 'No existe un perfil de nutricionista activo para el usuario autenticado.');
        }

        return $nutricionista;
    }

    protected function verificarAsignacionActiva(RegistroAlimenticio $registroAlimenticio, Nutricionista $nutricionista): void
    {
        $tieneAsignacionActiva = $registroAlimenticio->paciente
            ? $registroAlimenticio->paciente->asignacionesNutricionista()
                ->where('cod_nutricionistas_anp', $nutricionista->cod_nutricionistas)
                ->where('est_anp', 'activa')
                ->exists()
            : false;

        if (! $tieneAsignacionActiva) {
            abort(403, 'No tienes una asignacion activa para validar este registro alimenticio.');
        }
    }

    protected function verificarAccesoPaciente(Paciente $paciente, Nutricionista $nutricionista): void
    {
        $tieneAsignacionActiva = $paciente->asignacionesNutricionista()
            ->where('cod_nutricionistas_anp', $nutricionista->cod_nutricionistas)
            ->where('est_anp', 'activa')
            ->exists();

        if (! $tieneAsignacionActiva) {
            abort(403, 'No tienes una asignacion activa para visualizar este paciente.');
        }
    }

    protected function verificarRegistroEnviadoARevision(RegistroAlimenticio $registroAlimenticio): void
    {
        if ($registroAlimenticio->est_ral !== RegistroAlimenticio::ESTADO_CERRADO) {
            abort(403, 'Este registro aún está como borrador y no fue enviado a revisión.');
        }

        if (! $registroAlimenticio->detalles()->exists()) {
            abort(403, 'Este registro no tiene alimentos cargados para validar.');
        }
    }

    protected function calcularResumenRegistro(RegistroAlimenticio $registroAlimenticio): array
    {
        $resumen = [
            'total_alimentos' => $registroAlimenticio->detalles->count(),
            'con_datos' => 0,
            'sin_datos' => 0,
            'cal' => 0,
            'pro' => 0,
            'car' => 0,
            'gra' => 0,
        ];

        foreach ($registroAlimenticio->detalles as $detalle) {
            $aporte = $this->mapAporteDetalle($detalle);

            if (! $aporte) {
                $resumen['sin_datos']++;
                continue;
            }

            $resumen['con_datos']++;
            $resumen['cal'] += $aporte['cal'];
            $resumen['pro'] += $aporte['pro'];
            $resumen['car'] += $aporte['car'];
            $resumen['gra'] += $aporte['gra'];
        }

        return [
            'total_alimentos' => $resumen['total_alimentos'],
            'con_datos' => $resumen['con_datos'],
            'sin_datos' => $resumen['sin_datos'],
            'cal' => round($resumen['cal'], 2),
            'pro' => round($resumen['pro'], 2),
            'car' => round($resumen['car'], 2),
            'gra' => round($resumen['gra'], 2),
        ];
    }

    protected function mapAporteDetalle(DetalleRegistroAlimenticio $detalle): ?array
    {
        $composicion = $detalle->alimento?->composicionVigente;

        if (! $composicion) {
            return null;
        }

        $factor = $this->factorCantidadDetalle($detalle);

        return [
            'cal' => round((float) $composicion->cal_tcn * $factor, 2),
            'pro' => round((float) $composicion->pro_tcn * $factor, 2),
            'car' => round((float) $composicion->car_tcn * $factor, 2),
            'gra' => round((float) $composicion->gra_tcn * $factor, 2),
        ];
    }

    protected function factorCantidadDetalle(DetalleRegistroAlimenticio $detalle): float
    {
        $cantidad = max((float) $detalle->can_dra, 0);
        $unidad = (string) $detalle->uni_dra;

        if ($cantidad <= 0) {
            return 0;
        }

        if (in_array($unidad, ['gramos', 'mililitros'], true)) {
            return $cantidad / 100;
        }

        return 1;
    }
}