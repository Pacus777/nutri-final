<?php

namespace App\Services\Dashboard;

use App\Models\AsignacionNutricionistaPaciente;
use App\Models\Nutricionista;
use App\Models\ObjetivoNutricional;
use App\Models\PlanNutricional;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\ValidacionInformacion;
use App\Models\Usuario;
use Carbon\Carbon;

class DashboardNutricionistaService
{
    /**
     * Obtiene todos los datos requeridos para el Dashboard de Nutricionista.
     */
    public function getDatos(Usuario $usuario): array
    {
        $nutricionista = Nutricionista::with('usuario')
            ->where('cod_usuarios_nut', $usuario->cod_usuarios)
            ->where('est_nut', 'activo')
            ->firstOrFail();

        $asignacionesActivas = AsignacionNutricionistaPaciente::query()
            ->with('paciente.usuario')
            ->where('cod_nutricionistas_anp', $nutricionista->cod_nutricionistas)
            ->where('est_anp', 'activa')
            ->orderByDesc('fec_anp')
            ->get();

        $pacientesAsignadosIds = $asignacionesActivas
            ->pluck('cod_pacientes_anp')
            ->unique()
            ->values();

        $registrosPendientes = RegistroAlimenticio::query()
            ->whereIn('cod_pacientes_ral', $pacientesAsignadosIds)
            ->whereDoesntHave('validacionesInformacion', function ($query) use ($nutricionista) {
                $query->where('cod_nutricionistas_vin', $nutricionista->cod_nutricionistas);
            })
            ->count();

        $validacionesMesActual = ValidacionInformacion::query()
            ->where('cod_nutricionistas_vin', $nutricionista->cod_nutricionistas)
            ->whereMonth('fec_vin', now()->month)
            ->whereYear('fec_vin', now()->year)
            ->count();

        $objetivosPorEstado = ObjetivoNutricional::query()
            ->where('cod_nutricionistas_obe', $nutricionista->cod_nutricionistas)
            ->selectRaw('est_obe, count(*) as total')
            ->groupBy('est_obe')
            ->pluck('total', 'est_obe');

        $planesPorEstado = PlanNutricional::query()
            ->where('cod_nutricionistas_pan', $nutricionista->cod_nutricionistas)
            ->selectRaw('est_pan, count(*) as total')
            ->groupBy('est_pan')
            ->pluck('total', 'est_pan');

        $pacientesConPlanVigente = PlanNutricional::query()
            ->where('cod_nutricionistas_pan', $nutricionista->cod_nutricionistas)
            ->whereIn('est_pan', ['vigente', 'activo'])
            ->distinct('cod_pacientes_pan')
            ->count('cod_pacientes_pan');

        // Optimización 7: limit(8) en lugar de get()->take(8)
        $objetivosNutricionales = ObjetivoNutricional::query()
            ->with(['paciente.usuario'])
            ->where('cod_nutricionistas_obe', $nutricionista->cod_nutricionistas)
            ->orderByDesc('cod_objetivos_nutricionales')
            ->limit(8)
            ->get()
            ->map(fn ($objetivo) => $this->formatearObjetivo($objetivo))
            ->values();

        $todosObjetivos = ObjetivoNutricional::query()
            ->with(['paciente.usuario'])
            ->where('cod_nutricionistas_obe', $nutricionista->cod_nutricionistas)
            ->orderByDesc('cod_objetivos_nutricionales')
            ->get()
            ->map(fn ($objetivo) => $this->formatearObjetivo($objetivo))
            ->values();

        $todosPlanes = PlanNutricional::query()
            ->with(['paciente.usuario'])
            ->where('cod_nutricionistas_pan', $nutricionista->cod_nutricionistas)
            ->orderByDesc('cod_planes_nutricionales')
            ->get()
            ->map(fn ($plan) => [
                'cod_planes_nutricionales' => $plan->cod_planes_nutricionales,
                'fec_pan' => optional($plan->fec_pan)->format('Y-m-d'),
                'des_pan' => $plan->des_pan,
                'est_pan' => $plan->est_pan,
                'paciente' => $plan->paciente ? [
                    'cod_pacientes' => $plan->paciente->cod_pacientes,
                    'usuario' => $plan->paciente->usuario ? [
                        'nombre_completo' => $plan->paciente->usuario->nombre_completo,
                        'cor_usu' => $plan->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
            ])
            ->values();

        $pacientesAsignadosPlanes = $asignacionesActivas
            ->map(fn ($asignacion) => $asignacion->paciente)
            ->filter()
            ->unique('cod_pacientes')
            ->values()
            ->map(fn ($paciente) => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ] : null,
            ]);

        $tresDiasAtras = now()->subDays(3)->startOfDay()->format('Y-m-d');

        // Optimización 6: Resolución de la consulta N+1 usando subquery con addSelect
        $pacientesSinRegistrosRecientes = Paciente::query()
            ->with('usuario')
            ->whereIn('cod_pacientes', $pacientesAsignadosIds)
            ->where(function ($query) use ($tresDiasAtras) {
                $query->whereDoesntHave('registrosAlimenticios')
                    ->orWhereNotExists(function ($subquery) use ($tresDiasAtras) {
                        $subquery->selectRaw(1)
                            ->from('registros_alimenticios')
                            ->whereColumn('registros_alimenticios.cod_pacientes_ral', 'pacientes.cod_pacientes')
                            ->where('registros_alimenticios.fec_ral', '>=', $tresDiasAtras);
                    });
            })
            ->addSelect([
                'ultimo_registro_fecha' => RegistroAlimenticio::select('fec_ral')
                    ->whereColumn('cod_pacientes_ral', 'pacientes.cod_pacientes')
                    ->orderByDesc('fec_ral')
                    ->limit(1)
            ])
            ->get()
            ->map(function ($paciente) {
                $diasTranscurridos = null;
                if ($paciente->ultimo_registro_fecha) {
                    $fechaUltimo = Carbon::parse($paciente->ultimo_registro_fecha)->startOfDay();
                    $diasTranscurridos = (int) $fechaUltimo->diffInDays(now()->startOfDay());
                }

                return [
                    'cod_pacientes' => $paciente->cod_pacientes,
                    'nombre_completo' => $paciente->usuario?->nombre_completo,
                    'cor_usu' => $paciente->usuario?->cor_usu,
                    'ultimo_registro_fecha' => $paciente->ultimo_registro_fecha,
                    'dias_sin_registrar' => $diasTranscurridos,
                ];
            })
            ->values();

        return [
            'pacientesSinRegistrosRecientes' => $pacientesSinRegistrosRecientes,
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'esp_nut' => $nutricionista->esp_nut,
                'usuario' => $nutricionista->usuario ? [
                    'nombre_completo' => $nutricionista->usuario->nombre_completo,
                    'cor_usu' => $nutricionista->usuario->cor_usu,
                ] : null,
            ],
            'resumen' => [
                'pacientes_asignados' => $pacientesAsignadosIds->count(),
                'registros_pendientes' => $registrosPendientes,
                'validaciones_mes_actual' => $validacionesMesActual,
                'objetivos_activos' => (int) ($objetivosPorEstado->get('activo', 0) + $objetivosPorEstado->get('vigente', 0)),
                'objetivos_cumplidos' => (int) $objetivosPorEstado->get('cumplido', 0),
                'planes_vigentes' => (int) ($planesPorEstado->get('vigente', 0) + $planesPorEstado->get('activo', 0)),
                'planes_cerrados' => (int) $planesPorEstado->get('cerrado', 0),
                'planes_reemplazados' => (int) $planesPorEstado->get('reemplazado', 0),
                'pacientes_con_plan_vigente' => $pacientesConPlanVigente,
            ],
            'estadisticasNutricionista' => [
                'objetivos_por_estado' => [
                    'activo' => (int) $objetivosPorEstado->get('activo', 0),
                    'vigente' => (int) $objetivosPorEstado->get('vigente', 0),
                    'cumplido' => (int) $objetivosPorEstado->get('cumplido', 0),
                    'pausado' => (int) $objetivosPorEstado->get('pausado', 0),
                    'cancelado' => (int) $objetivosPorEstado->get('cancelado', 0),
                    'cerrado' => (int) $objetivosPorEstado->get('cerrado', 0),
                ],
                'planes_por_estado' => [
                    'vigente' => (int) $planesPorEstado->get('vigente', 0),
                    'activo' => (int) $planesPorEstado->get('activo', 0),
                    'cerrado' => (int) $planesPorEstado->get('cerrado', 0),
                    'reemplazado' => (int) $planesPorEstado->get('reemplazado', 0),
                ],
            ],
            'pacientesAsignados' => $asignacionesActivas
                ->take(6)
                ->map(fn ($asignacion) => [
                    'cod_pacientes' => $asignacion->paciente?->cod_pacientes,
                    'fec_anp' => optional($asignacion->fec_anp)->format('Y-m-d'),
                    'paciente' => $asignacion->paciente?->usuario ? [
                        'nombre_completo' => $asignacion->paciente->usuario->nombre_completo,
                        'cor_usu' => $asignacion->paciente->usuario->cor_usu,
                    ] : null,
                    'adherencia' => $asignacion->paciente ? $asignacion->paciente->calcularEstadoAdherencia() : null,
                ])
                ->values(),
            'pacientesAsignadosPlanes' => $pacientesAsignadosPlanes,
            'objetivosNutricionales' => $objetivosNutricionales,
            'todosObjetivos' => $todosObjetivos,
            'todosPlanes' => $todosPlanes,
        ];
    }

    /**
     * Formatea un objetivo individualmente para evitar duplicidad de código.
     */
    private function formatearObjetivo(ObjetivoNutricional $objetivo): array
    {
        return [
            'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
            'des_obe' => $objetivo->des_obe,
            'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
            'est_obe' => $objetivo->est_obe,
            'paciente' => $objetivo->paciente ? [
                'cod_pacientes' => $objetivo->paciente->cod_pacientes,
                'usuario' => $objetivo->paciente->usuario ? [
                    'nombre_completo' => $objetivo->paciente->usuario->nombre_completo,
                    'cor_usu' => $objetivo->paciente->usuario->cor_usu,
                ] : null,
            ] : null,
        ];
    }
}
