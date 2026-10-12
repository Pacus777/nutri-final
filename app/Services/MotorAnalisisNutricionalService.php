<?php

namespace App\Services;

use App\Models\EtiquetaRiesgoProfesional;
use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\PrediccionMl;
use App\Models\RecomendacionNutricional;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MotorAnalisisNutricionalService
{
    public const ETIQUETA_MOTOR_EXPERIMENTAL = 'Motor Inteligente Nutricional — Análisis asistido orquestado (K-Means + Decision Tree + Heurística). Orientativo, pendiente de criterio profesional.';

    public function __construct(
        protected MlInferenceService $mlInferenceService,
        protected AnalisisInteligentePacienteService $analisisInteligenteService
    ) {}

    /**
     * Orquesta el análisis inteligente completo para un paciente y snapshot específico o más reciente.
     */
    public function orquestarAnalisisCompleto(Paciente $paciente, ?FeatureNutricional $snapshot = null): array
    {
        // 1. Obtener snapshot válido de 7 días
        if (!$snapshot) {
            $snapshot = FeatureNutricional::query()
                ->where('cod_pacientes_fnu', $paciente->cod_pacientes)
                ->where('ventana_dias_fnu', 7)
                ->latest('fec_fin_fnu')
                ->first();
        }

        // Si no hay snapshot disponible, devolver estructura con estado de datos insuficientes
        if (!$snapshot) {
            return [
                'disponible' => false,
                'paciente_id' => $paciente->cod_pacientes,
                'mensaje' => 'No existen snapshots de features nutricionales v1.0 calculados para este paciente.',
                'calidad_datos' => 'insuficiente',
                'resumen_heuristico' => $this->analisisInteligenteService->generar($paciente),
                'kmeans' => null,
                'decision_tree' => null,
                'alertas_clinicas' => [],
                'recomendaciones' => [
                    'pendientes' => [],
                    'aprobadas' => [],
                    'historial' => [],
                ],
                'historial_evolucion' => [],
                'disclaimer' => self::ETIQUETA_MOTOR_EXPERIMENTAL,
            ];
        }

        // 2. Metadatos y calidad de la ventana
        $adherencia = (float) $snapshot->adherencia_registro_fnu;
        $diasRegistrados = (int) $snapshot->dias_registrados_fnu;
        $calidadVentana = $adherencia >= 0.7143 ? 'suficiente' : ($adherencia >= 0.5714 ? 'limitada' : 'insuficiente');

        // 3. Indicadores objetivos del snapshot
        $calorias = (float) $snapshot->promedio_calorias_fnu;
        $fibraG = (float) $snapshot->promedio_fibra_g_fnu;
        $azucarG = (float) $snapshot->promedio_azucar_g_fnu;
        $sodioMg = (float) $snapshot->promedio_sodio_mg_fnu;

        $fibraPor1000 = $calorias > 0 ? round($fibraG / ($calorias / 1000.0), 2) : 0.0;
        $azucarPor1000 = $calorias > 0 ? round($azucarG / ($calorias / 1000.0), 2) : 0.0;
        $sodioPor1000 = $calorias > 0 ? round($sodioMg / ($calorias / 1000.0), 2) : 0.0;

        $indicadoresObjetivos = [
            'promedio_calorias' => $calorias,
            'pct_proteina' => (float) $snapshot->pct_proteina_fnu,
            'pct_carbohidratos' => (float) $snapshot->pct_carbohidratos_fnu,
            'pct_grasa' => (float) $snapshot->pct_grasa_fnu,
            'fibra_g_dia' => $fibraG,
            'azucar_g_dia' => $azucarG,
            'sodio_mg_dia' => $sodioMg,
            'fibra_por_1000kcal' => $fibraPor1000,
            'azucar_por_1000kcal' => $azucarPor1000,
            'sodio_por_1000kcal' => $sodioPor1000,
            'frecuencia_desayuno' => (float) $snapshot->frecuencia_desayuno_fnu,
            'frecuencia_cena' => (float) $snapshot->frecuencia_cena_fnu,
            'promedio_comidas_dia' => (float) $snapshot->promedio_comidas_dia_fnu,
            'variabilidad_horaria_min' => (float) ($snapshot->variabilidad_horaria_min_fnu ?? 30.0),
            'imc' => (float) ($snapshot->imc_fnu ?? 24.0),
        ];

        // 4. Alertas analíticas y nutricionales
        $alertas = $this->generarAlertasNutricionales($indicadoresObjetivos, $calidadVentana);

        // 5. Inferencia K-Means (Patrón)
        $modeloKMeans = $this->mlInferenceService->getModeloKMeansCandidato();
        $inferenciaKMeans = null;
        if ($modeloKMeans) {
            try {
                $inferenciaKMeans = $this->mlInferenceService->ejecutarInferencia($snapshot, $modeloKMeans);
            } catch (\Throwable $e) {
                Log::warning("Fallo inferencia KMeans en Motor: " . $e->getMessage());
                $inferenciaKMeans = [
                    'disponible' => false,
                    'resultado_principal' => 'Modelo no disponible temporalmente',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // 6. Inferencia Decision Tree (Riesgo)
        $modeloDT = $this->mlInferenceService->getModeloDecisionTreeCandidato();
        $inferenciaDT = null;
        $prediccionDTModel = null;
        if ($modeloDT) {
            try {
                $inferenciaDT = $this->mlInferenceService->ejecutarInferenciaDecisionTree($snapshot, $modeloDT);
                if ($inferenciaDT['disponible'] ?? true) {
                    $prediccionDTModel = $this->mlInferenceService->guardarPrediccionDecisionTree($snapshot, $modeloDT, $inferenciaDT);
                }
            } catch (\Throwable $e) {
                Log::warning("Fallo inferencia Decision Tree en Motor: " . $e->getMessage());
                $inferenciaDT = $this->mlInferenceService->generarRespuestaDecisionTreeNoDisponible($modeloDT, $e->getMessage());
            }
        }

        // 7. Validaciones Profesionales Existentes para este Snapshot
        $etiquetaProfesional = EtiquetaRiesgoProfesional::query()
            ->where('cod_features_nutricionales_erp', $snapshot->cod_features_nutricionales)
            ->latest('cod_etiquetas_riesgo_profesional')
            ->first();

        // 8. Capa de Recomendaciones Nutricionales Asistidas
        $recomendacionesGeneradas = $this->generarRecomendacionesAsistidas(
            $paciente,
            $snapshot,
            $inferenciaKMeans,
            $inferenciaDT,
            $prediccionDTModel
        );

        $todasRecomendaciones = RecomendacionNutricional::query()
            ->where('cod_pacientes_rec', $paciente->cod_pacientes)
            ->with(['nutricionista.usuario'])
            ->latest('fec_rec')
            ->latest('cod_recomendaciones_nutricionales')
            ->get();

        $recPendientes = $todasRecomendaciones->where('estado_rec', RecomendacionNutricional::ESTADO_PENDIENTE)->values();
        $recAprobadas = $todasRecomendaciones->whereIn('estado_rec', [RecomendacionNutricional::ESTADO_APROBADA, RecomendacionNutricional::ESTADO_EDITADA])->values();

        // 9. Historial de Evolución Temporal del Paciente
        $historialEvolucion = $this->obtenerHistorialEvolucion($paciente);

        return [
            'disponible' => true,
            'paciente_id' => $paciente->cod_pacientes,
            'snapshot_id' => $snapshot->cod_features_nutricionales,
            'periodo' => [
                'fec_inicio' => optional($snapshot->fec_inicio_fnu)->format('Y-m-d'),
                'fec_fin' => optional($snapshot->fec_fin_fnu)->format('Y-m-d'),
                'ventana_dias' => $snapshot->ventana_dias_fnu,
                'dias_registrados' => $diasRegistrados,
                'adherencia' => round($adherencia * 100, 1) . '%',
                'calidad_ventana' => $calidadVentana,
                'origen_datos' => $snapshot->origen_datos_fnu,
            ],
            'indicadores' => $indicadoresObjetivos,
            'alertas_nutricionales' => $alertas,
            'alertas_analiticas' => $alertas, // Alias retrocompatible
            'analisis_heuristico' => $this->analisisInteligenteService->generar($paciente),
            'kmeans' => [
                'modelo_id' => $modeloKMeans?->cod_modelos_ml,
                'version_modelo' => $modeloKMeans?->version_mml,
                'estado_modelo' => $modeloKMeans?->est_mml,
                'origen_dataset' => $modeloKMeans?->origen_dataset_mml,
                'cluster_asignado' => $inferenciaKMeans['cluster_asignado'] ?? null,
                'resultado_principal' => $inferenciaKMeans['resultado_principal'] ?? 'Cluster no asignado',
                'distancia_centroide' => $inferenciaKMeans['distancia_centroide_asignado'] ?? null,
                'distancias_centroides' => $inferenciaKMeans['distancias_centroides'] ?? [],
                'patron_vinculado' => $this->obtenerPatronAlimenticioVinculado($modeloKMeans, $inferenciaKMeans['cluster_asignado'] ?? null),
            ],
            'decision_tree' => [
                'modelo_id' => $modeloDT?->cod_modelos_ml,
                'version_modelo' => $modeloDT?->version_mml,
                'estado_modelo' => $modeloDT?->est_mml,
                'origen_dataset' => $modeloDT?->origen_dataset_mml,
                'disponible' => $inferenciaDT['disponible'] ?? false,
                'clase_predicha' => $inferenciaDT['clase_predicha'] ?? null,
                'resultado_formateado' => $inferenciaDT['resultado_formateado'] ?? 'No disponible',
                'probabilidad_clase' => $inferenciaDT['probabilidad_clase'] ?? null,
                'probabilidades' => $inferenciaDT['probabilidades'] ?? null,
                'camino_decision' => $inferenciaDT['camino_decision'] ?? [],
                'variables_involucradas' => $inferenciaDT['variables_involucradas'] ?? [],
                'advertencia' => $inferenciaDT['advertencia'] ?? 'Modelo experimental.',
                'error_detalle' => $inferenciaDT['error_detalle'] ?? null,
            ],
            'validacion_profesional' => $etiquetaProfesional ? [
                'cod_etiquetas_riesgo_profesional' => $etiquetaProfesional->cod_etiquetas_riesgo_profesional,
                'nivel_riesgo_profesional' => $etiquetaProfesional->nivel_riesgo_erp,
                'tipo_riesgo_profesional' => $etiquetaProfesional->tipo_riesgo_erp,
                'observacion_profesional' => $etiquetaProfesional->observacion_profesional_erp,
                'fec_emision' => optional($etiquetaProfesional->fec_etiqueta_erp)->format('Y-m-d H:i'),
                'estado' => $etiquetaProfesional->estado_etiqueta_erp,
            ] : null,
            'recomendaciones' => [
                'pendientes' => $recPendientes,
                'aprobadas' => $recAprobadas,
                'total_pendientes' => $recPendientes->count(),
                'total_aprobadas' => $recAprobadas->count(),
            ],
            'historial_evolucion' => $historialEvolucion,
            'disclaimer' => self::ETIQUETA_MOTOR_EXPERIMENTAL,
        ];
    }

    /**
     * Genera alertas nutricionales/analíticas basadas en criterios versionados v1.0.
     */
    public function generarAlertasNutricionales(array $indicadores, string $calidadVentana): array
    {
        $alertas = [];

        $umbralAzucar = (float) config('criterios_nutricionales.densidades.azucar_max_por_1000kcal', 44.10);
        $umbralSodio = (float) config('criterios_nutricionales.densidades.sodio_max_por_1000kcal', 1465.71);
        $umbralFibra = (float) config('criterios_nutricionales.densidades.fibra_min_por_1000kcal', 11.95);
        $umbralVariabilidad = (float) config('criterios_nutricionales.habitos_y_horarios.variabilidad_horaria_max_min', 52.10);
        $umbralDesayuno = (float) config('criterios_nutricionales.habitos_y_horarios.frecuencia_desayuno_min', 0.60);

        if ($calidadVentana === 'limitada') {
            $alertas[] = [
                'nivel' => 'aviso',
                'titulo' => 'Calidad de Ventana Limitada',
                'descripcion' => 'La ventana cuenta con 4 de 7 días registrados (57.1% adherencia). Las estimaciones deben valorarse con precaución analítica.',
                'categoria' => 'calidad_datos',
            ];
        }

        if ($indicadores['azucar_por_1000kcal'] > $umbralAzucar) {
            $alertas[] = [
                'nivel' => 'alerta',
                'titulo' => 'Densidad de Azúcar Elevada',
                'descripcion' => "Consumo de azúcar ({$indicadores['azucar_por_1000kcal']} g/1000 kcal) supera el umbral analítico de referencia ({$umbralAzucar} g/1000 kcal).",
                'categoria' => 'azucar',
                'valor' => $indicadores['azucar_por_1000kcal'],
                'umbral' => $umbralAzucar,
            ];
        }

        if ($indicadores['sodio_por_1000kcal'] > $umbralSodio) {
            $alertas[] = [
                'nivel' => 'alerta',
                'titulo' => 'Densidad de Sodio Elevada',
                'descripcion' => "Consumo de sodio ({$indicadores['sodio_por_1000kcal']} mg/1000 kcal) excede el umbral analítico de corte ({$umbralSodio} mg/1000 kcal).",
                'categoria' => 'sodio',
                'valor' => $indicadores['sodio_por_1000kcal'],
                'umbral' => $umbralSodio,
            ];
        }

        if ($indicadores['fibra_por_1000kcal'] < $umbralFibra) {
            $alertas[] = [
                'nivel' => 'aviso',
                'titulo' => 'Aporte de Fibra Subóptimo',
                'descripcion' => "Aporte de fibra ({$indicadores['fibra_por_1000kcal']} g/1000 kcal) por debajo del umbral preventivo analítico ({$umbralFibra} g/1000 kcal).",
                'categoria' => 'fibra',
                'valor' => $indicadores['fibra_por_1000kcal'],
                'umbral' => $umbralFibra,
            ];
        }

        if ($indicadores['variabilidad_horaria_min'] > $umbralVariabilidad) {
            $alertas[] = [
                'nivel' => 'aviso',
                'titulo' => 'Dispersión en Horarios de Ingesta',
                'descripcion' => "Variabilidad horaria ({$indicadores['variabilidad_horaria_min']} min) supera el margen de regularidad referencial ({$umbralVariabilidad} min).",
                'categoria' => 'horarios',
                'valor' => $indicadores['variabilidad_horaria_min'],
                'umbral' => $umbralVariabilidad,
            ];
        }

        if ($indicadores['frecuencia_desayuno'] < $umbralDesayuno) {
            $alertas[] = [
                'nivel' => 'aviso',
                'titulo' => 'Omisión Frecuente de Desayuno',
                'descripcion' => "Frecuencia de desayuno ({$indicadores['frecuencia_desayuno']}) indica omisión recurrente de la primera ingesta.",
                'categoria' => 'habitos',
                'valor' => $indicadores['frecuencia_desayuno'],
            ];
        }

        return $alertas;
    }

    /**
     * Alias retrocompatible para generarAlertasNutricionales.
     */
    public function generarAlertasDeterministicas(array $indicadores, string $calidadVentana): array
    {
        return $this->generarAlertasNutricionales($indicadores, $calidadVentana);
    }

    /**
     * Genera sugerencias asistidas de recomendaciones nutricionales en estado pendiente de revisión profesional.
     */
    public function generarRecomendacionesAsistidas(
        Paciente $paciente,
        FeatureNutricional $snapshot,
        ?array $inferenciaKmeans = null,
        ?array $inferenciaDT = null,
        ?PrediccionMl $prediccion = null
    ): Collection {
        $calorias = (float) $snapshot->promedio_calorias_fnu;
        $fibraG = (float) $snapshot->promedio_fibra_g_fnu;
        $azucarG = (float) $snapshot->promedio_azucar_g_fnu;
        $sodioMg = (float) $snapshot->promedio_sodio_mg_fnu;
        $variabilidad = (float) ($snapshot->variabilidad_horaria_min_fnu ?? 30.0);

        $fibraPor1000 = $calorias > 0 ? round($fibraG / ($calorias / 1000.0), 2) : 0.0;
        $azucarPor1000 = $calorias > 0 ? round($azucarG / ($calorias / 1000.0), 2) : 0.0;
        $sodioPor1000 = $calorias > 0 ? round($sodioMg / ($calorias / 1000.0), 2) : 0.0;

        $umbralAzucar = (float) config('criterios_nutricionales.densidades.azucar_max_por_1000kcal', 44.10);
        $umbralSodio = (float) config('criterios_nutricionales.densidades.sodio_max_por_1000kcal', 1465.71);
        $umbralFibra = (float) config('criterios_nutricionales.densidades.fibra_min_por_1000kcal', 11.95);
        $umbralVariabilidad = (float) config('criterios_nutricionales.habitos_y_horarios.variabilidad_horaria_max_min', 52.10);

        $candidatas = [];

        // 1. Sugerencia por azúcar
        if ($azucarPor1000 > $umbralAzucar) {
            $candidatas[] = [
                'tip_rec' => 'reduccion_azucar',
                'categoria_rec' => 'control_azucar',
                'des_rec' => 'Ajustar la ingesta de azúcares libres y ultraprocesados; priorizar fruta entera, legumbres y carbohidratos complejos de absorción lenta.',
                'justificacion_rec' => "Densidad de azúcar observada ({$azucarPor1000} g/1000 kcal) excede el umbral analítico referencial de {$umbralAzucar} g/1000 kcal.",
            ];
        }

        // 2. Sugerencia por sodio
        if ($sodioPor1000 > $umbralSodio) {
            $candidatas[] = [
                'tip_rec' => 'reduccion_sodio',
                'categoria_rec' => 'reduccion_sodio',
                'des_rec' => 'Moderar el uso de sal de mesa y reducir el consumo de embutidos, caldos concentrados y snacks salados procesados.',
                'justificacion_rec' => "Densidad de sodio ({$sodioPor1000} mg/1000 kcal) excede el umbral analítico referencial de {$umbralSodio} mg/1000 kcal.",
            ];
        }

        // 3. Sugerencia por fibra
        if ($fibraPor1000 < $umbralFibra) {
            $candidatas[] = [
                'tip_rec' => 'aumento_fibra',
                'categoria_rec' => 'aumento_fibra',
                'des_rec' => 'Aumentar la ingesta diaria de verduras de hoja verde, leguminosas, semillas y granos integrales para optimizar la saciedad y salud intestinal.',
                'justificacion_rec' => "Densidad de fibra ({$fibraPor1000} g/1000 kcal) se encuentra por debajo del umbral analítico preventivo de {$umbralFibra} g/1000 kcal.",
            ];
        }

        // 4. Sugerencia por variabilidad horaria
        if ($variabilidad > $umbralVariabilidad) {
            $candidatas[] = [
                'tip_rec' => 'regularidad_horaria',
                'categoria_rec' => 'regularidad_horaria',
                'des_rec' => 'Fijar horarios estables para las comidas principales evitando ventanas de ayuno imprevistas que puedan inducir descompensación en la ingesta.',
                'justificacion_rec' => "Variabilidad de horarios entre días ({$variabilidad} min) supera la referencia de estabilidad ({$umbralVariabilidad} min).",
            ];
        }

        // 5. Sugerencia general por riesgo bajo o moderado
        $claseDT = strtolower($inferenciaDT['clase_predicha'] ?? '');
        if ($claseDT === 'bajo' && empty($candidatas)) {
            $candidatas[] = [
                'tip_rec' => 'mantenimiento_habitos',
                'categoria_rec' => 'habito_general',
                'des_rec' => 'Mantener el patrón actual de ingesta y horarios regulares, supervisando la adherencia en las próximas semanas.',
                'justificacion_rec' => 'Estimación de riesgo bajo con parámetros de ingesta y regularidad dentro de márgenes analíticos referenciales.',
            ];
        }

        $guardadas = collect();

        foreach ($candidatas as $cand) {
            // No duplicar si ya existe una idéntica pendiente para esta misma feature
            $rec = RecomendacionNutricional::firstOrCreate(
                [
                    'cod_pacientes_rec' => $paciente->cod_pacientes,
                    'cod_features_nutricionales_rec' => $snapshot->cod_features_nutricionales,
                    'tip_rec' => $cand['tip_rec'],
                ],
                [
                    'cod_predicciones_ml_rec' => $prediccion?->cod_predicciones_ml,
                    'fec_rec' => now()->toDateString(),
                    'des_rec' => $cand['des_rec'],
                    'origen_rec' => RecomendacionNutricional::ORIGEN_ASISTIDA_IA,
                    'estado_rec' => RecomendacionNutricional::ESTADO_PENDIENTE,
                    'categoria_rec' => $cand['categoria_rec'],
                    'justificacion_rec' => $cand['justificacion_rec'],
                ]
            );

            $guardadas->push($rec);
        }

        return $guardadas;
    }

    /**
     * Permite al profesional aprobar una sugerencia asistida.
     */
    public function aprobarRecomendacion(RecomendacionNutricional $recomendacion, Nutricionista $nutricionista, ?string $desEditada = null): RecomendacionNutricional
    {
        $esEditada = $desEditada && trim($desEditada) !== trim($recomendacion->des_rec);

        $recomendacion->update([
            'cod_nutricionistas_rec' => $nutricionista->cod_nutricionistas,
            'des_rec' => $esEditada ? trim($desEditada) : $recomendacion->des_rec,
            'estado_rec' => $esEditada ? RecomendacionNutricional::ESTADO_EDITADA : RecomendacionNutricional::ESTADO_APROBADA,
            'origen_rec' => $esEditada ? RecomendacionNutricional::ORIGEN_AJUSTE_PROFESIONAL : $recomendacion->origen_rec,
            'fec_aprobacion_rec' => now(),
        ]);

        return $recomendacion;
    }

    /**
     * Permite al profesional descartar una sugerencia asistida.
     */
    public function descartarRecomendacion(RecomendacionNutricional $recomendacion, Nutricionista $nutricionista): RecomendacionNutricional
    {
        $recomendacion->update([
            'cod_nutricionistas_rec' => $nutricionista->cod_nutricionistas,
            'estado_rec' => RecomendacionNutricional::ESTADO_DESCARTADA,
        ]);

        return $recomendacion;
    }

    /**
     * Permite al profesional redactar una recomendación clínica manual.
     */
    public function crearRecomendacionManual(
        Paciente $paciente,
        Nutricionista $nutricionista,
        string $descripcion,
        string $tipo = 'indicacion_clinica',
        ?string $categoria = 'criterio_profesional'
    ): RecomendacionNutricional {
        return RecomendacionNutricional::create([
            'cod_pacientes_rec' => $paciente->cod_pacientes,
            'cod_nutricionistas_rec' => $nutricionista->cod_nutricionistas,
            'fec_rec' => now()->toDateString(),
            'des_rec' => trim($descripcion),
            'tip_rec' => $tipo,
            'origen_rec' => RecomendacionNutricional::ORIGEN_MANUAL,
            'estado_rec' => RecomendacionNutricional::ESTADO_APROBADA,
            'categoria_rec' => $categoria,
            'justificacion_rec' => 'Prescripción directa redactada por el profesional de nutrición.',
            'fec_aprobacion_rec' => now(),
        ]);
    }

    /**
     * Obtiene el historial evolutivo por ventanas temporales del paciente.
     */
    public function obtenerHistorialEvolucion(Paciente $paciente): array
    {
        $snapshots = FeatureNutricional::query()
            ->where('cod_pacientes_fnu', $paciente->cod_pacientes)
            ->where('ventana_dias_fnu', 7)
            ->orderBy('fec_fin_fnu', 'asc')
            ->get();

        $modeloKMeans = $this->mlInferenceService->getModeloKMeansCandidato();
        $modeloDT = $this->mlInferenceService->getModeloDecisionTreeCandidato();

        $historial = [];

        foreach ($snapshots as $snap) {
            $predKMeans = $modeloKMeans ? PrediccionMl::query()
                ->where('cod_pacientes_pml', $paciente->cod_pacientes)
                ->where('cod_modelos_ml_pml', $modeloKMeans->cod_modelos_ml)
                ->where('cod_features_nutricionales_pml', $snap->cod_features_nutricionales)
                ->first() : null;

            $predDT = $modeloDT ? PrediccionMl::query()
                ->where('cod_pacientes_pml', $paciente->cod_pacientes)
                ->where('cod_modelos_ml_pml', $modeloDT->cod_modelos_ml)
                ->where('cod_features_nutricionales_pml', $snap->cod_features_nutricionales)
                ->first() : null;

            $etiqueta = EtiquetaRiesgoProfesional::query()
                ->where('cod_features_nutricionales_erp', $snap->cod_features_nutricionales)
                ->first();

            $recs = RecomendacionNutricional::query()
                ->where('cod_features_nutricionales_rec', $snap->cod_features_nutricionales)
                ->get();

            $historial[] = [
                'snapshot_id' => $snap->cod_features_nutricionales,
                'fec_inicio' => optional($snap->fec_inicio_fnu)->format('Y-m-d'),
                'fec_fin' => optional($snap->fec_fin_fnu)->format('Y-m-d'),
                'adherencia' => round($snap->adherencia_registro_fnu * 100, 1) . '%',
                'dias_registrados' => $snap->dias_registrados_fnu,
                'calidad_ventana' => $snap->adherencia_registro_fnu >= 0.7143 ? 'Suficiente' : 'Limitada',
                'patron_kmeans' => $predKMeans?->resultado_principal_pml ?? 'Sin calcular',
                'riesgo_decision_tree' => $predDT?->resultado_principal_pml ?? 'Sin calcular',
                'probabilidad_riesgo' => $predDT?->score_confianza_pml ? round($predDT->score_confianza_pml * 100, 1) . '%' : null,
                'etiqueta_profesional' => $etiqueta?->nivel_riesgo_erp ? ucfirst($etiqueta->nivel_riesgo_erp) : 'Sin etiquetar',
                'recomendaciones_aprobadas' => $recs->whereIn('estado_rec', [RecomendacionNutricional::ESTADO_APROBADA, RecomendacionNutricional::ESTADO_EDITADA])->count(),
                'recomendaciones_pendientes' => $recs->where('estado_rec', RecomendacionNutricional::ESTADO_PENDIENTE)->count(),
            ];
        }

        return $historial;
    }

    protected function obtenerPatronAlimenticioVinculado(?ModeloMl $modelo, ?int $clusterNum): ?array
    {
        if (!$modelo || $clusterNum === null) return null;

        $clusterDef = $modelo->clusters->firstWhere('numero_cluster_clm', $clusterNum);
        if (!$clusterDef || !$clusterDef->patronAlimenticio) return null;

        return [
            'cod_patrones_alimenticios' => $clusterDef->patronAlimenticio->cod_patrones_alimenticios,
            'nom_pat' => $clusterDef->patronAlimenticio->nom_pat,
            'des_pat' => $clusterDef->patronAlimenticio->des_pat,
        ];
    }
}
