<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Services\PlanificacionNutricionalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanificacionNutricionalController extends Controller
{
    public function show(Paciente $paciente, PlanificacionNutricionalService $service): Response
    {
        $planificacion = $service->obtenerPlanificacionCompleta($paciente->cod_pacientes);
        $pacienteCompleto = $planificacion['paciente'];

        $pacienteCompleto->load([
            'usuario',
            'asignacionActiva.nutricionista.usuario',
            'evaluacionInicialActiva.nutricionista.usuario',
            'planNutricionalVigente.nutricionista.usuario',
        ]);

        $contextosHistorial = $pacienteCompleto->contextosPaciente()
            ->latest('fec_cxp')
            ->get()
            ->map(function ($contexto) {
                return [
                    'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
                    'des_cxp' => $contexto->des_cxp,
                    'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
                    'tip_cxp' => $contexto->tip_cxp,
                    'est_cxp' => $contexto->est_cxp,
                ];
            });

        $objetivosHistorial = $pacienteCompleto->objetivosNutricionales()
            ->with(['nutricionista.usuario', 'ajustes.nutricionista.usuario'])
            ->latest('fec_obe')
            ->get()
            ->map(function ($objetivo) {
                return [
                    'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
                    'des_obe' => $objetivo->des_obe,
                    'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
                    'est_obe' => $objetivo->est_obe,
                    'nutricionista' => $objetivo->nutricionista ? [
                        'cod_nutricionistas' => $objetivo->nutricionista->cod_nutricionistas,
                        'usuario' => $objetivo->nutricionista->usuario ? [
                            'nombre_completo' => $objetivo->nutricionista->usuario->nombre_completo,
                            'cor_usu' => $objetivo->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                    'ajustes' => $objetivo->ajustes->map(function ($ajuste) {
                        return [
                            'cod_ajustes_objetivos_nutricionales' => $ajuste->cod_ajustes_objetivos_nutricionales,
                            'fec_ajn' => optional($ajuste->fec_ajn)->format('Y-m-d'),
                            'des_ajn' => $ajuste->des_ajn,
                            'motivo_ajn' => $ajuste->motivo_ajn,
                            'estado_anterior_ajn' => $ajuste->estado_anterior_ajn,
                            'estado_nuevo_ajn' => $ajuste->estado_nuevo_ajn,
                        ];
                    }),
                ];
            });

        $planesHistorial = $pacienteCompleto->planesNutricionales()
            ->with('nutricionista.usuario')
            ->latest('fec_pan')
            ->get()
            ->map(function ($plan) {
                return [
                    'cod_planes_nutricionales' => $plan->cod_planes_nutricionales,
                    'fec_pan' => optional($plan->fec_pan)->format('Y-m-d'),
                    'des_pan' => $plan->des_pan,
                    'est_pan' => $plan->est_pan,
                    'nutricionista' => $plan->nutricionista ? [
                        'cod_nutricionistas' => $plan->nutricionista->cod_nutricionistas,
                        'usuario' => $plan->nutricionista->usuario ? [
                            'nombre_completo' => $plan->nutricionista->usuario->nombre_completo,
                            'cor_usu' => $plan->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('PlanificacionNutricional/Show', [
            'paciente' => [
                'cod_pacientes' => $pacienteCompleto->cod_pacientes,
                'est_pac' => $pacienteCompleto->est_pac,
                'edad' => $pacienteCompleto->edad,
                'usuario' => $pacienteCompleto->usuario ? [
                    'nombre_completo' => $pacienteCompleto->usuario->nombre_completo,
                    'cor_usu' => $pacienteCompleto->usuario->cor_usu,
                    'ci_usu' => $pacienteCompleto->usuario->ci_usu,
                    'ext_ci_usu' => $pacienteCompleto->usuario->ext_ci_usu,
                    'fec_nac_usu' => optional($pacienteCompleto->usuario->fec_nac_usu)->format('Y-m-d'),
                    'sex_usu' => $pacienteCompleto->usuario->sex_usu,
                ] : null,
            ],
            'nutricionista_activo' => $pacienteCompleto->asignacionActiva && $pacienteCompleto->asignacionActiva->nutricionista ? [
                'cod_nutricionistas' => $pacienteCompleto->asignacionActiva->nutricionista->cod_nutricionistas,
                'esp_nut' => $pacienteCompleto->asignacionActiva->nutricionista->esp_nut,
                'usuario' => $pacienteCompleto->asignacionActiva->nutricionista->usuario ? [
                    'nombre_completo' => $pacienteCompleto->asignacionActiva->nutricionista->usuario->nombre_completo,
                    'cor_usu' => $pacienteCompleto->asignacionActiva->nutricionista->usuario->cor_usu,
                ] : null,
            ] : null,
            'evaluacion_inicial_activa' => $planificacion['evaluacion_inicial_activa'] ? [
                'cod_evaluaciones_iniciales' => $planificacion['evaluacion_inicial_activa']->cod_evaluaciones_iniciales,
                'fec_evi' => optional($planificacion['evaluacion_inicial_activa']->fec_evi)->format('Y-m-d'),
                'est_evi' => $planificacion['evaluacion_inicial_activa']->est_evi,
                'peso_evi' => $planificacion['evaluacion_inicial_activa']->peso_evi,
                'estatura_evi' => $planificacion['evaluacion_inicial_activa']->estatura_evi,
                'imc_evi' => $planificacion['evaluacion_inicial_activa']->imc_evi,
                'obs_evi' => $planificacion['evaluacion_inicial_activa']->obs_evi,
                'objetivo_inicial_evi' => $planificacion['evaluacion_inicial_activa']->objetivo_inicial_evi,
                'restricciones_evi' => $planificacion['evaluacion_inicial_activa']->restricciones_evi,
                'contexto_general_evi' => $planificacion['evaluacion_inicial_activa']->contexto_general_evi,
            ] : null,
            'contextos' => $contextosHistorial,
            'objetivos_activos' => $planificacion['objetivos_activos']->map(function ($objetivo) {
                return [
                    'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
                    'des_obe' => $objetivo->des_obe,
                    'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
                    'est_obe' => $objetivo->est_obe,
                    'nutricionista' => $objetivo->nutricionista ? [
                        'cod_nutricionistas' => $objetivo->nutricionista->cod_nutricionistas,
                        'usuario' => $objetivo->nutricionista->usuario ? [
                            'nombre_completo' => $objetivo->nutricionista->usuario->nombre_completo,
                            'cor_usu' => $objetivo->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                    'ajustes' => $objetivo->ajustes->map(function ($ajuste) {
                        return [
                            'cod_ajustes_objetivos_nutricionales' => $ajuste->cod_ajustes_objetivos_nutricionales,
                            'fec_ajn' => optional($ajuste->fec_ajn)->format('Y-m-d'),
                            'des_ajn' => $ajuste->des_ajn,
                            'estado_anterior_ajn' => $ajuste->estado_anterior_ajn,
                            'estado_nuevo_ajn' => $ajuste->estado_nuevo_ajn,
                        ];
                    }),
                ];
            }),
            'objetivos_historial' => $objetivosHistorial,
            'plan_vigente' => $planificacion['plan_vigente'] ? [
                'cod_planes_nutricionales' => $planificacion['plan_vigente']->cod_planes_nutricionales,
                'fec_pan' => optional($planificacion['plan_vigente']->fec_pan)->format('Y-m-d'),
                'des_pan' => $planificacion['plan_vigente']->des_pan,
                'est_pan' => $planificacion['plan_vigente']->est_pan,
            ] : null,
            'planes_historial' => $planesHistorial,
            'acciones_disponibles' => [
                'puede_registrar_contexto' => true,
                'puede_registrar_evaluacion' => true,
                'puede_registrar_objetivo' => true,
                'puede_registrar_plan' => true,
            ],
        ]);
    }
}
