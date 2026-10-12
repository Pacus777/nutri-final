<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\AsignacionNutricionistaPaciente;
use App\Models\ContextoPaciente;
use App\Models\Nutricionista;
use App\Models\ObjetivoNutricional;
use App\Models\Paciente;
use App\Models\PlanNutricional;
use App\Models\RegistroAlimenticio;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $resumen = $this->obtenerResumenGeneral();

        $nutricionistas = Nutricionista::with('usuario')->get()->map(fn($n) => [
            'cod_nutricionistas' => $n->cod_nutricionistas,
            'nombre' => $n->usuario->nombre_completo,
        ])->sortBy('nombre')->values()->all();

        $pacientes = Paciente::with('usuario')->get()->map(fn($p) => [
            'cod_pacientes' => $p->cod_pacientes,
            'nombre' => $p->usuario->nombre_completo,
        ])->sortBy('nombre')->values()->all();

        $nutricionistaReport = null;
        if ($request->has('nutricionista_id') && $request->input('nutricionista_id')) {
            $nutricionistaReport = $this->obtenerDatosNutricionista($request->input('nutricionista_id'));
        }

        $pacienteReport = null;
        if ($request->has('paciente_id') && $request->input('paciente_id')) {
            $pacienteReport = $this->obtenerDatosPaciente($request->input('paciente_id'));
        }

        $alimentacionReport = null;
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $alimentacionReport = $this->obtenerDatosAlimentacion($request->input('fecha_inicio'), $request->input('fecha_fin'));
        }

        return Inertia::render('Reportes/Index', [
            'resumen' => $resumen,
            'nutricionistas' => $nutricionistas,
            'pacientes' => $pacientes,
            'nutricionistaReport' => $nutricionistaReport,
            'pacienteReport' => $pacienteReport,
            'alimentacionReport' => $alimentacionReport,
        ]);
    }

    public function generalPdf(): Response
    {
        $resumen = $this->obtenerResumenGeneral();
        $generadoEn = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reportes.general', compact('resumen', 'generadoEn'))
            ->setPaper('a4', 'portrait');

        $nombreArchivo = 'reporte-general-sistema-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    public function nutricionistaPdf(Request $request): Response
    {
        $nutricionistaId = $request->input('nutricionista_id');
        $report = $this->obtenerDatosNutricionista($nutricionistaId);
        if (!$report) {
            abort(404, 'Nutricionista no encontrado.');
        }

        $generadoEn = now()->format('d/m/Y H:i');
        $pdf = Pdf::loadView('reportes.nutricionista', compact('report', 'generadoEn'))
            ->setPaper('a4', 'portrait');

        $nombreArchivo = 'reporte-nutricionista-' . str_replace(' ', '-', strtolower($report['info']['nombre'])) . '-' . now()->format('Ymd-His') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function alimentacionPdf(Request $request): Response
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $report = $this->obtenerDatosAlimentacion($fechaInicio, $fechaFin);

        $generadoEn = now()->format('d/m/Y H:i');
        $pdf = Pdf::loadView('reportes.registros_alimenticios', compact('report', 'generadoEn'))
            ->setPaper('a4', 'portrait');

        $nombreArchivo = 'reporte-alimentacion-' . now()->format('Ymd-His') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function pacientePdf(Request $request): Response
    {
        $pacienteId = $request->input('paciente_id');
        $report = $this->obtenerDatosPaciente($pacienteId);
        if (!$report) {
            abort(404, 'Paciente no encontrado.');
        }

        $generadoEn = now()->format('d/m/Y H:i');
        $pdf = Pdf::loadView('reportes.paciente', compact('report', 'generadoEn'))
            ->setPaper('a4', 'portrait');

        $nombreArchivo = 'reporte-paciente-' . str_replace(' ', '-', strtolower($report['info']['nombre'])) . '-' . now()->format('Ymd-His') . '.pdf';
        return $pdf->download($nombreArchivo);
    }

    private function obtenerDatosNutricionista($nutricionistaId)
    {
        $nutricionista = Nutricionista::with('usuario')->find($nutricionistaId);
        if (!$nutricionista) return null;

        $asignaciones = AsignacionNutricionistaPaciente::with(['paciente.usuario'])
            ->where('cod_nutricionistas_anp', $nutricionistaId)
            ->get();

        $pacientesData = [];
        $totalPacientes = $asignaciones->count();
        $pacientesActivos = 0;
        $adherenciaStats = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0];

        foreach ($asignaciones as $asig) {
            $paciente = $asig->paciente;
            if ($paciente) {
                $adherencia = $paciente->calcularEstadoAdherencia();
                $adherenciaStats[$adherencia['estado']]++;

                if ($asig->est_anp === 'activa') {
                    $pacientesActivos++;
                }

                $pacientesData[] = [
                    'cod_pacientes' => $paciente->cod_pacientes,
                    'nombre' => $paciente->usuario->nombre_completo,
                    'email' => $paciente->usuario->cor_usu,
                    'sexo' => $paciente->sex_pac?->value ?? 'No definido',
                    'edad' => $paciente->edad,
                    'fecha_asignacion' => $asig->fec_anp ? $asig->fec_anp->format('Y-m-d') : '—',
                    'estado_asignacion' => $asig->est_anp,
                    'adherencia' => $adherencia,
                ];
            }
        }

        // Breakdown of plans
        $planesBreakdown = PlanNutricional::where('cod_nutricionistas_pan', $nutricionistaId)
            ->selectRaw('est_pan, count(*) as count')
            ->groupBy('est_pan')
            ->pluck('count', 'est_pan')
            ->toArray();

        // Breakdown of goals
        $objetivosBreakdown = ObjetivoNutricional::where('cod_nutricionistas_obe', $nutricionistaId)
            ->selectRaw('est_obe, count(*) as count')
            ->groupBy('est_obe')
            ->pluck('count', 'est_obe')
            ->toArray();

        $totalObjetivos = array_sum($objetivosBreakdown);
        $totalPlanes = array_sum($planesBreakdown);

        return [
            'info' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'nombre' => $nutricionista->usuario->nombre_completo,
                'especialidad' => $nutricionista->esp_nut ?? 'General',
                'estado' => $nutricionista->est_nut->value ?? 'activo',
                'email' => $nutricionista->usuario->cor_usu,
            ],
            'stats' => [
                'total_pacientes' => $totalPacientes,
                'pacientes_activos' => $pacientesActivos,
                'total_objetivos' => $totalObjetivos,
                'total_planes' => $totalPlanes,
                'adherencia' => $adherenciaStats,
                'planes_breakdown' => $planesBreakdown,
                'objetivos_breakdown' => $objetivosBreakdown,
            ],
            'pacientes' => $pacientesData,
        ];
    }

    private function obtenerDatosPaciente($pacienteId)
    {
        $paciente = Paciente::with(['usuario', 'contextosPaciente', 'objetivosNutricionales', 'planesNutricionales'])->find($pacienteId);
        if (!$paciente) return null;

        // Nutricionista actual
        $nutriActual = $paciente->nutricionistaActual()->first();
        $nutricionistaInfo = null;
        if ($nutriActual) {
            $nutri = Nutricionista::with('usuario')->find($nutriActual->cod_nutricionistas_anp);
            if ($nutri) {
                $nutricionistaInfo = [
                    'nombre' => $nutri->usuario->nombre_completo,
                    'email' => $nutri->usuario->cor_usu,
                    'especialidad' => $nutri->esp_nut ?? 'General',
                ];
            }
        }

        // Contextos clínicos
        $contextos = $paciente->contextosPaciente->map(fn($c) => [
            'descripcion' => $c->des_cxp,
            'fecha' => $c->fec_cxp ? $c->fec_cxp->format('Y-m-d') : '—',
            'tipo' => $c->tip_cxp,
            'prioridad' => $c->pri_cxp,
            'estado' => $c->est_cxp,
        ])->toArray();

        // High priority clinical alerts
        $alertasClinicas = $paciente->contextosPaciente()
            ->where('est_cxp', 'activo')
            ->where('pri_cxp', 'alta')
            ->get()
            ->map(fn($c) => [
                'tipo' => $c->tip_cxp,
                'descripcion' => $c->des_cxp,
            ])->toArray();

        // Objetivos
        $objetivos = $paciente->objetivosNutricionales->map(fn($o) => [
            'descripcion' => $o->des_obe,
            'fecha' => $o->fec_obe ? $o->fec_obe->format('Y-m-d') : '—',
            'estado' => $o->est_obe,
        ])->toArray();

        // Plan nutricional vigente
        $planVigente = $paciente->planNutricionalVigente;
        $planData = null;
        if ($planVigente) {
            $planData = [
                'fecha' => $planVigente->fec_pan ? $planVigente->fec_pan->format('Y-m-d') : '—',
                'descripcion' => $planVigente->des_pan,
                'estado' => $planVigente->est_pan,
            ];
        }

        // Historial de peso e IMC
        $historialPeso = $paciente->getHistorialPeso();

        // Calculate weight loss / change
        $firstWeight = count($historialPeso) > 0 ? $historialPeso[0]['peso'] : null;
        $currentWeight = (float) $paciente->pes_pac;
        $cambioPeso = ($firstWeight && $currentWeight) ? round($currentWeight - $firstWeight, 1) : 0;

        // Compliance rate (logs in the last 30 days)
        $logsUltimos30Dias = RegistroAlimenticio::where('cod_pacientes_ral', $pacienteId)
            ->whereDate('fec_ral', '>=', now()->subDays(30))
            ->count();
        $loggingRate = min(100, round(($logsUltimos30Dias / 30) * 100));

        // Actual food averages
        $registrosPaciente = RegistroAlimenticio::with('composicionNutricionalRegistrada')
            ->where('cod_pacientes_ral', $pacienteId)
            ->get();
        $avgCal = 0; $avgPro = 0; $avgGra = 0; $avgCar = 0;
        $regsWithComp = 0;
        foreach ($registrosPaciente as $rp) {
            if ($rp->composicionNutricionalRegistrada) {
                $avgCal += (float) $rp->composicionNutricionalRegistrada->cal_cnr;
                $avgPro += (float) $rp->composicionNutricionalRegistrada->pro_cnr;
                $avgGra += (float) $rp->composicionNutricionalRegistrada->gra_cnr;
                $avgCar += (float) $rp->composicionNutricionalRegistrada->car_cnr;
                $regsWithComp++;
            }
        }

        $pacienteAverages = null;
        if ($regsWithComp > 0) {
            $pacienteAverages = [
                'calorias' => round($avgCal / $regsWithComp, 1),
                'proteinas' => round($avgPro / $regsWithComp, 1),
                'grasas' => round($avgGra / $regsWithComp, 1),
                'carbohidratos' => round($avgCar / $regsWithComp, 1),
                'total_dias' => $regsWithComp,
            ];
        }

        // Adherencia
        $adherencia = $paciente->calcularEstadoAdherencia();

        // Total de registros alimenticios
        $totalRegistros = $paciente->registrosAlimenticios()->count();

        return [
            'info' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'nombre' => $paciente->usuario->nombre_completo,
                'email' => $paciente->usuario->cor_usu,
                'sexo' => $paciente->sex_pac?->value ?? 'No definido',
                'edad' => $paciente->edad,
                'peso_actual' => $paciente->pes_pac,
                'estatura' => $paciente->estatura_pac,
                'tipo_sangre' => $paciente->tip_san_pac ?? 'Sin registrar',
                'alergias' => $paciente->ale_pac ?? 'Ninguna registrada',
                'estado' => $paciente->est_pac->value ?? 'activo',
            ],
            'nutricionista' => $nutricionistaInfo,
            'contextos' => $contextos,
            'alertas_clinicas' => $alertasClinicas,
            'objetivos' => $objetivos,
            'plan' => $planData,
            'historial_peso' => $historialPeso,
            'cambio_peso' => $cambioPeso,
            'logging_rate' => $loggingRate,
            'paciente_averages' => $pacienteAverages,
            'adherencia' => $adherencia,
            'total_registros' => $totalRegistros,
        ];
    }

    private function obtenerDatosAlimentacion($fechaInicio, $fechaFin)
    {
        try {
            $start = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
            $end = \Carbon\Carbon::parse($fechaFin)->endOfDay();
        } catch (\Exception $e) {
            $start = now()->subDays(30)->startOfDay();
            $end = now()->endOfDay();
        }

        $registros = RegistroAlimenticio::with(['paciente.usuario', 'composicionNutricionalRegistrada'])
            ->whereBetween('fec_ral', [$start->toDateString(), $end->toDateString()])
            ->get();

        $totalRegistros = $registros->count();
        if ($totalRegistros === 0) {
            return [
                'rango' => ['inicio' => $start->format('Y-m-d'), 'fin' => $end->format('Y-m-d')],
                'total_registros' => 0,
                'calorias_promedio' => 0,
                'proteinas_promedio' => 0,
                'grasas_promedio' => 0,
                'carbohidratos_promedio' => 0,
                'pacientes_activos_conteo' => 0,
                'comidas_frecuentes' => [],
                'distribucion_comidas' => [],
                'top_pacientes' => [],
                'pacientes_bajos' => [],
            ];
        }

        $caloriasSum = 0;
        $proteinasSum = 0;
        $grasasSum = 0;
        $carbohidratosSum = 0;
        $registrosConComposicion = 0;
        $pacientesConRegistros = [];

        foreach ($registros as $reg) {
            $pacientesConRegistros[$reg->cod_pacientes_ral] = true;
            $comp = $reg->composicionNutricionalRegistrada;
            if ($comp) {
                $caloriasSum += (float) $comp->cal_cnr;
                $proteinasSum += (float) $comp->pro_cnr;
                $grasasSum += (float) $comp->gra_cnr;
                $carbohidratosSum += (float) $comp->car_cnr;
                $registrosConComposicion++;
            }
        }

        $comidasFrecuentes = \DB::table('detalle_registro_alimenticio')
            ->join('registros_alimenticios', 'detalle_registro_alimenticio.cod_registros_alimenticios_dra', '=', 'registros_alimenticios.cod_registros_alimenticios')
            ->join('alimentos', 'detalle_registro_alimenticio.cod_alimentos_dra', '=', 'alimentos.cod_alimentos')
            ->whereBetween('registros_alimenticios.fec_ral', [$start->toDateString(), $end->toDateString()])
            ->select('alimentos.nom_ali', \DB::raw('count(*) as total'))
            ->groupBy('alimentos.nom_ali')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => ['nombre' => $row->nom_ali, 'total' => $row->total])
            ->toArray();

        $distribucionComidas = \DB::table('detalle_registro_alimenticio')
            ->join('registros_alimenticios', 'detalle_registro_alimenticio.cod_registros_alimenticios_dra', '=', 'registros_alimenticios.cod_registros_alimenticios')
            ->whereBetween('registros_alimenticios.fec_ral', [$start->toDateString(), $end->toDateString()])
            ->select('detalle_registro_alimenticio.tip_dra', \DB::raw('count(*) as total'))
            ->groupBy('detalle_registro_alimenticio.tip_dra')
            ->get()
            ->pluck('total', 'tip_dra')
            ->toArray();

        // Top active patients (most logs)
        $topPacientes = \DB::table('registros_alimenticios')
            ->join('pacientes', 'registros_alimenticios.cod_pacientes_ral', '=', 'pacientes.cod_pacientes')
            ->join('usuarios', 'pacientes.cod_usuarios_pac', '=', 'usuarios.cod_usuarios')
            ->whereBetween('registros_alimenticios.fec_ral', [$start->toDateString(), $end->toDateString()])
            ->select('usuarios.nom_usu', 'usuarios.ape_usu', \DB::raw('count(*) as total'))
            ->groupBy('usuarios.nom_usu', 'usuarios.ape_usu')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => ['nombre' => trim($row->nom_usu . ' ' . $row->ape_usu), 'total' => $row->total])
            ->toArray();

        // At-risk active patients (fewer than 3 logs in the range)
        $allPacientes = Paciente::with('usuario')->where('est_pac', 'activo')->get();
        $pacientesBajos = [];
        foreach ($allPacientes as $p) {
            $count = RegistroAlimenticio::where('cod_pacientes_ral', $p->cod_pacientes)
                ->whereBetween('fec_ral', [$start->toDateString(), $end->toDateString()])
                ->count();
            if ($count < 3) {
                $pacientesBajos[] = [
                    'nombre' => $p->usuario->nombre_completo,
                    'logs' => $count,
                    'email' => $p->usuario->cor_usu,
                ];
            }
        }
        usort($pacientesBajos, fn($a, $b) => $a['logs'] <=> $b['logs']);
        $pacientesBajos = array_slice($pacientesBajos, 0, 5);

        return [
            'rango' => [
                'inicio' => $start->format('Y-m-d'),
                'fin' => $end->format('Y-m-d'),
            ],
            'total_registros' => $totalRegistros,
            'calorias_promedio' => $registrosConComposicion > 0 ? round($caloriasSum / $registrosConComposicion, 1) : 0,
            'proteinas_promedio' => $registrosConComposicion > 0 ? round($proteinasSum / $registrosConComposicion, 1) : 0,
            'grasas_promedio' => $registrosConComposicion > 0 ? round($grasasSum / $registrosConComposicion, 1) : 0,
            'carbohidratos_promedio' => $registrosConComposicion > 0 ? round($carbohidratosSum / $registrosConComposicion, 1) : 0,
            'pacientes_activos_conteo' => count($pacientesConRegistros),
            'comidas_frecuentes' => $comidasFrecuentes,
            'distribucion_comidas' => $distribucionComidas,
            'top_pacientes' => $topPacientes,
            'pacientes_bajos' => $pacientesBajos,
        ];
    }

    private function obtenerResumenGeneral(): array
    {
        // Usuarios
        $totalUsuarios = Usuario::count();
        $usuariosActivos = Usuario::where('est_usu', 'activo')->count();
        $usuariosInactivos = $totalUsuarios - $usuariosActivos;

        // Pacientes
        $totalPacientes = Paciente::count();
        $pacientesActivos = Paciente::where('est_pac', 'activo')->count();
        $pacientesInactivos = $totalPacientes - $pacientesActivos;

        // Distribución por sexo
        $pacientesTodos = Paciente::with('usuario')->get();
        $sexo = ['Masculino' => 0, 'Femenino' => 0, 'Otro/Sin dato' => 0];
        $edades = [];
        foreach ($pacientesTodos as $p) {
            $sVal = $p->sex_pac;
            $s = trim((string) ($sVal instanceof \App\Enums\SexoPaciente ? $sVal->value : ($sVal ?? '')));
            if (in_array($s, ['M', 'Masculino', 'm'], true)) {
                $sexo['Masculino']++;
            } elseif (in_array($s, ['F', 'Femenino', 'f'], true)) {
                $sexo['Femenino']++;
            } else {
                $sexo['Otro/Sin dato']++;
            }
            if ($p->edad !== null) {
                $edades[] = $p->edad;
            }
        }

        $rangosEdad = ['0-17' => 0, '18-29' => 0, '30-44' => 0, '45-59' => 0, '60+' => 0, 'Sin dato' => 0];
        foreach ($pacientesTodos as $p) {
            $edad = $p->edad;
            if ($edad === null) {
                $rangosEdad['Sin dato']++;
            } elseif ($edad <= 17) {
                $rangosEdad['0-17']++;
            } elseif ($edad <= 29) {
                $rangosEdad['18-29']++;
            } elseif ($edad <= 44) {
                $rangosEdad['30-44']++;
            } elseif ($edad <= 59) {
                $rangosEdad['45-59']++;
            } else {
                $rangosEdad['60+']++;
            }
        }

        // Nutricionistas
        $totalNutricionistas = Nutricionista::count();
        $nutricionistasActivos = Nutricionista::where('est_nut', 'activo')->count();

        // Asignaciones
        $totalAsignaciones = AsignacionNutricionistaPaciente::count();
        $asignacionesActivas = AsignacionNutricionistaPaciente::where('est_anp', 'activa')->count();
        $asignacionesCerradas = $totalAsignaciones - $asignacionesActivas;

        // Contextos
        $totalContextos = ContextoPaciente::count();
        $contextosActivos = ContextoPaciente::where('est_cxp', 'activo')->count();

        // Objetivos nutricionales
        $totalObjetivos = ObjetivoNutricional::count();
        $objetivosPorEstado = ObjetivoNutricional::selectRaw('est_obe, count(*) as total')
            ->groupBy('est_obe')
            ->pluck('total', 'est_obe')
            ->toArray();

        // Planes nutricionales
        $totalPlanes = PlanNutricional::count();
        $planesPorEstado = PlanNutricional::selectRaw('est_pan, count(*) as total')
            ->groupBy('est_pan')
            ->pluck('total', 'est_pan')
            ->toArray();

        // Registros alimenticios
        $totalRegistros = RegistroAlimenticio::count();

        return [
            'usuarios' => [
                'total' => $totalUsuarios,
                'activos' => $usuariosActivos,
                'inactivos' => $usuariosInactivos,
            ],
            'pacientes' => [
                'total' => $totalPacientes,
                'activos' => $pacientesActivos,
                'inactivos' => $pacientesInactivos,
                'edad_promedio' => count($edades) > 0 ? round(array_sum($edades) / count($edades), 1) : null,
                'por_sexo' => $sexo,
                'por_rango_edad' => $rangosEdad,
            ],
            'nutricionistas' => [
                'total' => $totalNutricionistas,
                'activos' => $nutricionistasActivos,
                'inactivos' => $totalNutricionistas - $nutricionistasActivos,
            ],
            'asignaciones' => [
                'total' => $totalAsignaciones,
                'activas' => $asignacionesActivas,
                'cerradas' => $asignacionesCerradas,
            ],
            'contextos' => [
                'total' => $totalContextos,
                'activos' => $contextosActivos,
                'inactivos' => $totalContextos - $contextosActivos,
            ],
            'objetivos' => [
                'total' => $totalObjetivos,
                'por_estado' => $objetivosPorEstado,
            ],
            'planes' => [
                'total' => $totalPlanes,
                'por_estado' => $planesPorEstado,
            ],
            'registros_alimenticios' => [
                'total' => $totalRegistros,
            ],
            'generado_en' => now()->format('d/m/Y H:i'),
        ];
    }
}
