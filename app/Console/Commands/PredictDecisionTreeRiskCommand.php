<?php

namespace App\Console\Commands;

use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\PrediccionMl;
use App\Services\MlInferenceService;
use Illuminate\Console\Command;

class PredictDecisionTreeRiskCommand extends Command
{
    protected $signature = 'ml:predict-risk
                            {feature_id? : ID del snapshot de features_nutricionales a evaluar}
                            {--all-clinical : Ejecutar inferencia sobre todos los snapshots clínicos reales}
                            {--model= : ID específico del modelo de Decision Tree (por defecto candidato ID 23)}';

    protected $description = 'Ejecuta inferencia del Decision Tree experimental de riesgo nutricional y persiste la predicción con explicabilidad del camino de decisión.';

    public function __construct(
        protected MlInferenceService $inferenceService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelId = $this->option('model');
        $allClinical = (bool) $this->option('all-clinical');
        $featureId = $this->argument('feature_id');

        // 1. Obtener Modelo Decision Tree
        if ($modelId) {
            $modelo = ModeloMl::find($modelId);
        } else {
            $modelo = $this->inferenceService->getModeloDecisionTreeCandidato();
        }

        if (!$modelo) {
            $this->error('No se encontró un modelo Decision Tree registrado en estado de validación o activo.');
            return self::FAILURE;
        }

        $this->info("=== INFERENCIA EXPERIMENTAL: DECISION TREE (FASE 2F) ===");
        $this->line("Modelo: [ID {$modelo->cod_modelos_ml}] {$modelo->nombre_mml} ({$modelo->version_mml})");
        $this->line("Estado: " . strtoupper($modelo->est_mml) . " | Origen: {$modelo->origen_dataset_mml}");
        $this->warn("Aviso: {$modelo->origen_dataset_mml} — Estimación orientativa experimental, no constituye diagnóstico.");

        // 2. Obtener snapshots a procesar
        $query = FeatureNutricional::query();

        if ($allClinical) {
            $query->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
                  ->orderBy('fec_fin_fnu', 'asc');
        } elseif ($featureId) {
            $query->where('cod_features_nutricionales', $featureId);
        } else {
            // Tomar el más reciente clínico o disponible
            $query->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
                  ->latest('cod_features_nutricionales');
        }

        $snapshots = $query->get();

        if ($snapshots->isEmpty()) {
            $this->warn('No se encontraron snapshots de features que cumplan con los criterios.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Procesando inferencia para {$snapshots->count()} snapshot(s)...");

        $conteoBajo = 0;
        $conteoModerado = 0;
        $conteoAlto = 0;
        $resumenFilas = [];

        foreach ($snapshots as $snap) {
            $inferencia = $this->inferenceService->ejecutarInferenciaDecisionTree($snap, $modelo);
            $prediccion = $this->inferenceService->guardarPrediccionDecisionTree($snap, $modelo, $inferencia);

            $clase = strtolower($inferencia['clase_predicha']);
            if ($clase === 'bajo') $conteoBajo++;
            elseif ($clase === 'moderado') $conteoModerado++;
            elseif ($clase === 'alto') $conteoAlto++;

            $this->line(" -> [Snap #{$snap->cod_features_nutricionales}] {$inferencia['resultado_formateado']} (Conf: " . round($inferencia['probabilidad_clase'] * 100, 1) . "%)");

            $resumenFilas[] = [
                'ID Snap' => "#{$snap->cod_features_nutricionales}",
                'Paciente' => "#{$snap->cod_pacientes_fnu}",
                'Período' => "{$snap->fec_inicio_fnu?->format('d/m/Y')} - {$snap->fec_fin_fnu?->format('d/m/Y')}",
                'Adherencia' => ($snap->adherencia_registro_fnu * 100) . '%',
                'Predicción' => $inferencia['resultado_formateado'],
                'Probabilidad' => round($inferencia['probabilidad_clase'] * 100, 1) . '%',
                'Prob (B/M/A)' => sprintf("%.2f / %.2f / %.2f", $inferencia['probabilidades']['bajo'], $inferencia['probabilidades']['moderado'], $inferencia['probabilidades']['alto']),
                'ID Pred' => $prediccion->cod_predicciones_ml,
            ];

            // Si es evaluación individual, imprimir detalles completos del camino
            if ($snapshots->count() === 1) {
                $this->newLine();
                $this->info("--- DETALLE DE INFERENCIA Y EXPLICABILIDAD ---");
                $this->table(
                    ['Atributo', 'Valor'],
                    [
                        ['Snapshot ID', "#{$snap->cod_features_nutricionales} (Paciente #{$snap->cod_pacientes_fnu})"],
                        ['Resultado Principal', $inferencia['resultado_formateado']],
                        ['Probabilidad Estimada', round($inferencia['probabilidad_clase'] * 100, 1) . '%'],
                        ['Distribución de Probabilidades', json_encode($inferencia['probabilidades'])],
                        ['Variables Involucradas', json_encode($inferencia['variables_involucradas'])],
                        ['ID Registro predicciones_ml', $prediccion->cod_predicciones_ml],
                        ['Estado Validación', $prediccion->estado_validacion_pml],
                    ]
                );

                $this->newLine();
                $this->info("Camino de Decisión en el Árbol (Reglas Evaluadas):");
                foreach ($inferencia['camino_decision'] as $idx => $paso) {
                    $this->line("  [" . ($idx + 1) . "] {$paso['explicacion']}");
                }
            }
        }

        if ($snapshots->count() > 1) {
            $this->newLine();
            $this->info("--- RESUMEN DE PREDICCIONES POR SNAPSHOT ---");
            $this->table(
                ['ID Snap', 'Paciente', 'Período', 'Adherencia', 'Predicción', 'Prob.', 'Prob (B/M/A)', 'ID Pred'],
                $resumenFilas
            );

            $this->newLine();
            $this->info("--- DISTRIBUCIÓN TOTAL DE ESTIMACIONES EXPERIMENTALES ---");
            $this->table(
                ['Nivel de Riesgo Predicho', 'Cantidad', 'Porcentaje'],
                [
                    ['Riesgo BAJO', $conteoBajo, round(($conteoBajo / $snapshots->count()) * 100, 1) . '%'],
                    ['Riesgo MODERADO', $conteoModerado, round(($conteoModerado / $snapshots->count()) * 100, 1) . '%'],
                    ['Riesgo ALTO', $conteoAlto, round(($conteoAlto / $snapshots->count()) * 100, 1) . '%'],
                ]
            );
        }

        $this->newLine();
        $this->info("Inferencia Decision Tree completada exitosamente.");

        return self::SUCCESS;
    }
}
