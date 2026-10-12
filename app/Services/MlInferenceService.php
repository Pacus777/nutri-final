<?php

namespace App\Services;

use App\Models\AsignacionPatronPaciente;
use App\Models\ClusterModelo;
use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\PrediccionMl;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class MlInferenceService
{
    public const ETIQUETA_EXPERIMENTAL = 'Modelo experimental — entrenado con datos sintéticos controlados. Resultado orientativo, pendiente de validación con datos clínicos reales.';

    /**
     * Obtiene el modelo K-Means candidato vigente en validación.
     */
    public function getModeloKMeansCandidato(): ?ModeloMl
    {
        return ModeloMl::query()
            ->where('tipo_algoritmo_mml', 'kmeans')
            ->where('tarea_mml', 'clustering')
            ->whereIn('est_mml', [ModeloMl::ESTADO_VALIDACION, ModeloMl::ESTADO_ACTIVO])
            ->with(['clusters.patronAlimenticio'])
            ->latest('cod_modelos_ml')
            ->first();
    }

    /**
     * Ejecuta inferencia K-Means sobre un snapshot de features nutricionales.
     */
    public function ejecutarInferencia(FeatureNutricional $feature, ?ModeloMl $modelo = null): array
    {
        $modelo = $modelo ?: $this->getModeloKMeansCandidato();
        if (!$modelo) {
            throw new \RuntimeException("No existe un modelo K-Means registrado en estado de validación o activo.");
        }

        // Si ya existe una predicción persistida para este snapshot y modelo, recuperarla instantáneamente
        $prediccionExistente = PrediccionMl::query()
            ->where('cod_features_nutricionales_pml', $feature->cod_features_nutricionales)
            ->where('cod_modelos_ml_pml', $modelo->cod_modelos_ml)
            ->first();

        if ($prediccionExistente && !empty($prediccionExistente->explicabilidad_pml)) {
            $exp = $prediccionExistente->explicabilidad_pml;
            $clusterNum = (int) filter_var($prediccionExistente->resultado_principal_pml, FILTER_SANITIZE_NUMBER_INT);
            return [
                'cluster_asignado' => $clusterNum,
                'resultado_principal' => $prediccionExistente->resultado_principal_pml,
                'distancia_centroide_asignado' => $exp['distancia_centroide_asignado'] ?? null,
                'distancias_centroides' => $exp['distancias_centroides'] ?? ($exp['distancias_a_centroides'] ?? []),
                'version_modelo' => $exp['version_modelo'] ?? $modelo->version_mml,
                'version_features' => $exp['version_features'] ?? $modelo->version_features_mml,
                'origen_dataset' => $exp['origen_dataset'] ?? $modelo->origen_dataset_mml,
                'score_confianza' => $prediccionExistente->score_confianza_pml,
                'probabilidades_clases' => $prediccionExistente->probabilidades_clases_pml,
                'etiqueta_experimental' => $exp['etiqueta_experimental'] ?? self::ETIQUETA_EXPERIMENTAL,
                'indicadores_principales' => $exp['indicadores_principales'] ?? [],
                'centroide_asignado_valores' => $exp['centroide_asignado_valores'] ?? [],
            ];
        }

        // Preparar vector en formato JSON para el script Python
        $calorias = (float) $feature->promedio_calorias_fnu;
        $fibraG = (float) $feature->promedio_fibra_g_fnu;
        $azucarG = (float) $feature->promedio_azucar_g_fnu;
        $sodioMg = (float) $feature->promedio_sodio_mg_fnu;

        $fibraPor1000 = $calorias > 0 ? ($fibraG / $calorias) * 1000 : 0.0;
        $azucarPor1000 = $calorias > 0 ? ($azucarG / $calorias) * 1000 : 0.0;
        $sodioPor1000 = $calorias > 0 ? ($sodioMg / $calorias) * 1000 : 0.0;

        $vectorInput = [
            'promedio_calorias_fnu' => $calorias,
            'std_calorias_fnu' => (float) $feature->std_calorias_fnu,
            'pct_proteina_fnu' => (float) $feature->pct_proteina_fnu,
            'pct_carbohidratos_fnu' => (float) $feature->pct_carbohidratos_fnu,
            'pct_grasa_fnu' => (float) $feature->pct_grasa_fnu,
            'fibra_por_1000kcal' => round($fibraPor1000, 4),
            'azucar_por_1000kcal' => round($azucarPor1000, 4),
            'sodio_por_1000kcal' => round($sodioPor1000, 4),
            'frecuencia_desayuno_fnu' => (float) $feature->frecuencia_desayuno_fnu,
            'frecuencia_cena_fnu' => (float) $feature->frecuencia_cena_fnu,
            'frecuencia_snack_fnu' => (float) $feature->frecuencia_snack_fnu,
            'promedio_comidas_dia_fnu' => (float) $feature->promedio_comidas_dia_fnu,
            'variabilidad_horaria_min_fnu' => (float) ($feature->variabilidad_horaria_min_fnu ?? 0.0),
        ];

        // Ejecutar script Python de inferencia pasando JSON por STDIN
        $scriptPath = base_path('nutrition-analytics/scripts/predict_kmeans.py');
        $scalerPath = base_path('nutrition-analytics/artifacts/scaler_v1.joblib');
        $modelPath = base_path('nutrition-analytics/artifacts/kmeans_v1_k4_candidate.joblib');

        if (!File::exists($scriptPath) || !File::exists($scalerPath) || !File::exists($modelPath)) {
            return $this->calcularInferenciaPorCentroidesBD($vectorInput, $modelo);
        }

        $pythonBinary = env('PYTHON_BINARY') ?: (File::exists('C:/Users/THINK PAD/AppData/Local/Programs/Python/Python312/python.exe') ? 'C:/Users/THINK PAD/AppData/Local/Programs/Python/Python312/python.exe' : 'python');

        $process = new Process([
            $pythonBinary,
            $scriptPath,
            '--scaler=' . $scalerPath,
            '--model=' . $modelPath,
            '--stdin',
        ], base_path());

        $process->setInput(json_encode($vectorInput));
        $process->run();

        if (!$process->isSuccessful()) {
            \Illuminate\Support\Facades\Log::warning("Python inference process error: " . $process->getErrorOutput());
            return $this->calcularInferenciaPorCentroidesBD($vectorInput, $modelo);
        }

        $rawOutput = trim($process->getOutput());
        $output = json_decode($rawOutput, true);
        if (!$output || !isset($output['cluster_asignado'])) {
            // Extraer bloque JSON si hay texto adicional
            if (preg_match('/\{[\s\S]*\}/', $rawOutput, $matches)) {
                $output = json_decode($matches[0], true);
            }
        }

        if (!$output || !isset($output['cluster_asignado'])) {
            \Illuminate\Support\Facades\Log::warning("Python inference JSON invalid: " . $rawOutput);
            return $this->calcularInferenciaPorCentroidesBD($vectorInput, $modelo);
        }

        return $output;
    }

    /**
     * Fallback determinista en PHP calculando distancias a centroides si Python no está disponible en el entorno.
     */
    protected function calcularInferenciaPorCentroidesBD(array $vectorInput, ModeloMl $modelo): array
    {
        $clusters = $modelo->clusters()->orderBy('numero_cluster_clm')->get();
        if ($clusters->isEmpty()) {
            throw new \RuntimeException("El modelo no tiene centroides de clusters registrados.");
        }

        $distancias = [];
        $minDist = PHP_FLOAT_MAX;
        $asignado = 0;

        foreach ($clusters as $cl) {
            $centroide = $cl->centroide_json_clm ?? [];
            $distSq = 0.0;
            foreach ($vectorInput as $k => $v) {
                $cVal = (float) ($centroide[$k] ?? 0.0);
                // Normalización aproximada para distancia en fallback
                $distSq += pow($v - $cVal, 2);
            }
            $dist = round(sqrt($distSq), 4);
            $distancias["Cluster {$cl->numero_cluster_clm}"] = $dist;
            if ($dist < $minDist) {
                $minDist = $dist;
                $asignado = $cl->numero_cluster_clm;
            }
        }

        return [
            'cluster_asignado' => $asignado,
            'resultado_principal' => "Cluster {$asignado}",
            'distancia_centroide_asignado' => $minDist,
            'distancias_centroides' => $distancias,
            'version_modelo' => $modelo->version_mml,
            'version_features' => $modelo->version_features_mml,
            'origen_dataset' => $modelo->origen_dataset_mml,
            'score_confianza' => null,
            'probabilidades_clases' => null,
            'etiqueta_experimental' => self::ETIQUETA_EXPERIMENTAL,
            'indicadores_principales' => $vectorInput,
            'centroide_asignado_valores' => $clusters->firstWhere('numero_cluster_clm', $asignado)?->centroide_json_clm ?? [],
        ];
    }

    /**
     * Persiste el resultado de inferencia K-Means en predicciones_ml y vincula asignacion_patron_paciente solo si existe patrón asignado.
     */
    public function guardarPrediccion(FeatureNutricional $feature, ?ModeloMl $modelo = null): PrediccionMl
    {
        $modelo = $modelo ?: $this->getModeloKMeansCandidato();
        $resultado = $this->ejecutarInferencia($feature, $modelo);

        $clusterNum = $resultado['cluster_asignado'];

        $explicabilidad = [
            'distancia_centroide_asignado' => $resultado['distancia_centroide_asignado'],
            'distancias_centroides' => $resultado['distancias_centroides'],
            'distancias_a_centroides' => $resultado['distancias_centroides'],
            'indicadores_principales' => $resultado['indicadores_principales'],
            'centroide_asignado_valores' => $resultado['centroide_asignado_valores'] ?? null,
            'etiqueta_experimental' => self::ETIQUETA_EXPERIMENTAL,
            'version_modelo' => $resultado['version_modelo'],
            'origen_dataset' => $resultado['origen_dataset'],
            'origen_dataset_modelo' => $resultado['origen_dataset'],
        ];

        $prediccion = PrediccionMl::updateOrCreate(
            [
                'cod_pacientes_pml' => $feature->cod_pacientes_fnu,
                'cod_modelos_ml_pml' => $modelo->cod_modelos_ml,
                'cod_features_nutricionales_pml' => $feature->cod_features_nutricionales,
            ],
            [
                'fec_prediccion_pml' => now(),
                'resultado_principal_pml' => $resultado['resultado_principal'],
                'score_confianza_pml' => null,       // Sin score falso para K-Means
                'probabilidades_clases_pml' => null, // Sin probabilidades inventadas
                'explicabilidad_pml' => $explicabilidad,
                'estado_validacion_pml' => PrediccionMl::ESTADO_PENDIENTE,
                'origen_datos_pml' => $feature->origen_datos_fnu ?? 'legacy_desconocido',
                'referencia_origen_pml' => $feature->referencia_origen_fnu ?? null,
            ]
        );

        // Verificar si el cluster asignado tiene un patrón alimenticio vinculado en clusters_modelo
        $clusterModelo = ClusterModelo::query()
            ->where('cod_modelos_ml_clm', $modelo->cod_modelos_ml)
            ->where('numero_cluster_clm', $clusterNum)
            ->first();

        if ($clusterModelo && $clusterModelo->cod_patrones_alimenticios_clm) {
            AsignacionPatronPaciente::updateOrCreate(
                [
                    'cod_pacientes_app' => $feature->cod_pacientes_fnu,
                    'cod_predicciones_ml_app' => $prediccion->cod_predicciones_ml,
                ],
                [
                    'cod_patrones_alimenticios_app' => $clusterModelo->cod_patrones_alimenticios_clm,
                    'fec_app' => now()->toDateString(),
                ]
            );
        }

        return $prediccion;
    }

    /**
     * Obtiene el modelo Decision Tree candidato vigente en validación.
     */
    public function getModeloDecisionTreeCandidato(): ?ModeloMl
    {
        return ModeloMl::query()
            ->where('tipo_algoritmo_mml', 'decision_tree')
            ->where('tarea_mml', 'clasificacion_riesgo')
            ->whereIn('est_mml', [ModeloMl::ESTADO_VALIDACION, ModeloMl::ESTADO_ACTIVO])
            ->latest('cod_modelos_ml')
            ->first();
    }

    /**
     * Ejecuta inferencia Decision Tree sobre un snapshot de features nutricionales v1.0.
     */
    public function ejecutarInferenciaDecisionTree(FeatureNutricional $feature, ?ModeloMl $modelo = null): array
    {
        $modelo = $modelo ?: $this->getModeloDecisionTreeCandidato();
        if (!$modelo) {
            throw new \RuntimeException("No existe un modelo Decision Tree registrado en estado de validación o activo.");
        }

        // Si ya existe una predicción persistida para este snapshot y modelo, recuperarla instantáneamente
        $prediccionExistente = PrediccionMl::query()
            ->where('cod_features_nutricionales_pml', $feature->cod_features_nutricionales)
            ->where('cod_modelos_ml_pml', $modelo->cod_modelos_ml)
            ->first();

        if ($prediccionExistente && !empty($prediccionExistente->explicabilidad_pml)) {
            $exp = $prediccionExistente->explicabilidad_pml;
            $clase = $exp['clase_predicha'] ?? strtolower(str_replace('Riesgo ', '', $prediccionExistente->resultado_principal_pml));
            return [
                'disponible' => true,
                'clase_predicha' => $clase,
                'resultado_formateado' => $exp['resultado_formateado'] ?? $prediccionExistente->resultado_principal_pml,
                'probabilidades' => $prediccionExistente->probabilidades_clases_pml,
                'probabilidad_clase' => (float) $prediccionExistente->score_confianza_pml,
                'version_modelo' => $exp['version_modelo'] ?? $modelo->version_mml,
                'version_features' => $exp['version_features'] ?? $modelo->version_features_mml,
                'origen_dataset' => $exp['origen_dataset_modelo'] ?? $modelo->origen_dataset_mml,
                'protocolo_sintetico' => $exp['protocolo_sintetico'] ?? 'protocolo_sintetico_riesgo_v1.0',
                'camino_decision' => $exp['camino_decision'] ?? [],
                'variables_involucradas' => $exp['variables_involucradas'] ?? [],
                'advertencia' => $exp['advertencia'] ?? 'Modelo experimental orientativo.',
            ];
        }

        $calorias = (float) $feature->promedio_calorias_fnu;
        $fibraG = (float) $feature->promedio_fibra_g_fnu;
        $azucarG = (float) $feature->promedio_azucar_g_fnu;
        $sodioMg = (float) $feature->promedio_sodio_mg_fnu;

        $fibraPor1000 = $calorias > 0 ? ($fibraG / ($calorias / 1000.0)) : 0.0;
        $azucarPor1000 = $calorias > 0 ? ($azucarG / ($calorias / 1000.0)) : 0.0;
        $sodioPor1000 = $calorias > 0 ? ($sodioMg / ($calorias / 1000.0)) : 0.0;

        $vectorInput = [
            'promedio_calorias_fnu' => $calorias,
            'pct_proteina_fnu' => (float) $feature->pct_proteina_fnu,
            'pct_carbohidratos_fnu' => (float) $feature->pct_carbohidratos_fnu,
            'pct_grasa_fnu' => (float) $feature->pct_grasa_fnu,
            'fibra_por_1000kcal' => round($fibraPor1000, 2),
            'azucar_por_1000kcal' => round($azucarPor1000, 2),
            'sodio_por_1000kcal' => round($sodioPor1000, 2),
            'frecuencia_desayuno_fnu' => (float) $feature->frecuencia_desayuno_fnu,
            'frecuencia_cena_fnu' => (float) $feature->frecuencia_cena_fnu,
            'promedio_comidas_dia_fnu' => (float) $feature->promedio_comidas_dia_fnu,
            'variabilidad_horaria_min_fnu' => (float) ($feature->variabilidad_horaria_min_fnu ?? 30.0),
            'imc_fnu' => (float) ($feature->imc_fnu ?? 24.0),
        ];

        $scriptPath = base_path('nutrition-analytics/scripts/predict_decision_tree.py');
        $modelPath = base_path($modelo->ruta_artefacto_mml ?: 'nutrition-analytics/artifacts/decision_tree_v1_candidate.joblib');
        $metadataPath = base_path('nutrition-analytics/artifacts/decision_tree_v1_metadata.json');

        if (!File::exists($scriptPath) || !File::exists($modelPath)) {
            \Illuminate\Support\Facades\Log::warning("Python Decision Tree script or model not found: {$scriptPath} / {$modelPath}");
            return $this->generarRespuestaDecisionTreeNoDisponible($modelo, 'Artefacto del modelo o script de inferencia no disponible.');
        }

        $pythonBinary = env('PYTHON_BINARY') ?: (File::exists('C:/Users/THINK PAD/AppData/Local/Programs/Python/Python312/python.exe') ? 'C:/Users/THINK PAD/AppData/Local/Programs/Python/Python312/python.exe' : 'python');

        $tempFile = base_path('nutrition-analytics/artifacts/temp_dt_vec_' . uniqid() . '.json');
        File::put($tempFile, json_encode($vectorInput));

        $process = new Process([
            $pythonBinary,
            $scriptPath,
            '--model=' . $modelPath,
            '--metadata=' . $metadataPath,
            '--file=' . $tempFile,
        ], base_path());

        $process->run();
        if (File::exists($tempFile)) {
            @unlink($tempFile);
        }

        if (!$process->isSuccessful()) {
            \Illuminate\Support\Facades\Log::warning("Python Decision Tree inference error: " . $process->getErrorOutput());
            return $this->generarRespuestaDecisionTreeNoDisponible($modelo, 'Error en la ejecución del proceso de inferencia Python.');
        }

        $rawOutput = trim($process->getOutput());
        if (!mb_check_encoding($rawOutput, 'UTF-8')) {
            $rawOutput = mb_convert_encoding($rawOutput, 'UTF-8', 'ISO-8859-1, Windows-1252');
        }
        $output = json_decode($rawOutput, true);
        if (!$output || !isset($output['clase_predicha'])) {
            if (preg_match('/\{[\s\S]*\}/', $rawOutput, $matches)) {
                $output = json_decode($matches[0], true);
            }
        }

        if (!$output || !isset($output['clase_predicha'])) {
            \Illuminate\Support\Facades\Log::warning("Python Decision Tree JSON invalid: " . $rawOutput);
            return $this->generarRespuestaDecisionTreeNoDisponible($modelo, 'La salida del modelo no produjo un formato JSON estructurado válido.');
        }

        $output['disponible'] = true;
        return $output;
    }

    /**
     * Respuesta controlada cuando el modelo o Python no están disponibles.
     * NUNCA genera una predicción falsa ni simula un árbol de decisión.
     */
    public function generarRespuestaDecisionTreeNoDisponible(ModeloMl $modelo, string $motivo = 'Modelo no disponible'): array
    {
        return [
            'disponible' => false,
            'estado_modelo' => 'no_disponible',
            'clase_predicha' => null,
            'resultado_formateado' => 'Modelo no disponible temporalmente',
            'probabilidades' => null,
            'probabilidad_clase' => null,
            'version_modelo' => $modelo->version_mml,
            'version_features' => $modelo->version_features_mml,
            'origen_dataset' => $modelo->origen_dataset_mml,
            'protocolo_sintetico' => 'protocolo_sintetico_riesgo_v1.0',
            'camino_decision' => [],
            'variables_involucradas' => [],
            'error_detalle' => $motivo,
            'advertencia' => 'El modelo experimental no está disponible temporalmente. No se ha generado ninguna estimación artificial ni diagnóstico.',
        ];
    }

    /**
     * Persiste la predicción experimental del Decision Tree en predicciones_ml.
     */
    public function guardarPrediccionDecisionTree(FeatureNutricional $feature, ?ModeloMl $modelo = null, ?array $inferencia = null): PrediccionMl
    {
        $modelo = $modelo ?: $this->getModeloDecisionTreeCandidato();
        $inferencia = $inferencia ?: $this->ejecutarInferenciaDecisionTree($feature, $modelo);

        if (isset($inferencia['disponible']) && $inferencia['disponible'] === false) {
            throw new \RuntimeException("No se puede persistir predicción: " . ($inferencia['error_detalle'] ?? 'Modelo no disponible.'));
        }

        $clasePredicha = $inferencia['clase_predicha'];
        $scoreConfianza = (float) $inferencia['probabilidad_clase'];
        $probabilidades = $inferencia['probabilidades'];

        $explicabilidad = [
            'clase_predicha' => $clasePredicha,
            'resultado_formateado' => $inferencia['resultado_formateado'] ?? ('Riesgo ' . ucfirst($clasePredicha)),
            'camino_decision' => $inferencia['camino_decision'] ?? [],
            'variables_involucradas' => $inferencia['variables_involucradas'] ?? [],
            'advertencia' => $inferencia['advertencia'] ?? 'Modelo experimental orientativo.',
            'version_modelo' => $inferencia['version_modelo'] ?? $modelo->version_mml,
            'version_features' => $inferencia['version_features'] ?? $modelo->version_features_mml,
            'origen_dataset_modelo' => $inferencia['origen_dataset'] ?? $modelo->origen_dataset_mml,
            'protocolo_sintetico' => $inferencia['protocolo_sintetico'] ?? 'protocolo_sintetico_riesgo_v1.0',
        ];

        return PrediccionMl::updateOrCreate(
            [
                'cod_pacientes_pml' => $feature->cod_pacientes_fnu,
                'cod_modelos_ml_pml' => $modelo->cod_modelos_ml,
                'cod_features_nutricionales_pml' => $feature->cod_features_nutricionales,
            ],
            [
                'fec_prediccion_pml' => now(),
                'resultado_principal_pml' => 'Riesgo ' . ucfirst($clasePredicha),
                'score_confianza_pml' => $scoreConfianza,
                'probabilidades_clases_pml' => $probabilidades,
                'explicabilidad_pml' => $explicabilidad,
                'estado_validacion_pml' => PrediccionMl::ESTADO_PENDIENTE,
                'origen_datos_pml' => $feature->origen_datos_fnu ?? 'legacy_desconocido',
                'referencia_origen_pml' => $feature->referencia_origen_fnu ?? null,
            ]
        );
    }
}

