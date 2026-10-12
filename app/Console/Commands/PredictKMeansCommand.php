<?php

namespace App\Console\Commands;

use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Services\MlInferenceService;
use Illuminate\Console\Command;

class PredictKMeansCommand extends Command
{
    protected $signature = 'ml:predict-snapshot 
                            {feature_id? : ID de feature_nutricional a inferir} 
                            {--all-eligible : Ejecutar inferencia sobre todos los snapshots elegibles}';

    protected $description = 'Ejecuta inferencia K-Means offline sobre snapshots de features_nutricionales sin reentrenar el modelo.';

    public function handle(MlInferenceService $inferenceService): int
    {
        $featureId = $this->argument('feature_id');
        $allEligible = (bool) $this->option('all-eligible');

        $this->info("Iniciando flujo de inferencia K-Means (Fase 2C.2)...");

        $modelo = $inferenceService->getModeloKMeansCandidato();
        if (!$modelo) {
            $this->error("No se encontró ningún modelo K-Means en estado de validación o activo.");
            return self::FAILURE;
        }

        $this->line("Modelo candidato: <comment>{$modelo->nombre_mml}</comment> ({$modelo->version_mml})");
        $this->line("Origen dataset: <comment>{$modelo->origen_dataset_mml}</comment>");
        $this->line("Estado: <comment>{$modelo->est_mml}</comment>");
        $this->line("Advertencia: <comment>" . MlInferenceService::ETIQUETA_EXPERIMENTAL . "</comment>");

        if ($featureId) {
            $feature = FeatureNutricional::find($featureId);
            if (!$feature) {
                $this->error("No se encontró el snapshot con ID: {$featureId}");
                return self::FAILURE;
            }
            $snapshots = collect([$feature]);
        } else {
            // Buscar snapshots elegibles (ventana=7, adherencia >= 0.7143)
            $query = FeatureNutricional::query()
                ->where('ventana_dias_fnu', 7)
                ->where('adherencia_registro_fnu', '>=', 0.7143)
                ->orderByDesc('cod_features_nutricionales');

            $snapshots = $query->get();
            $totalReales = $snapshots->count();

            $this->info("Snapshots clínicos reales elegibles encontrados en BD: {$totalReales}");
            if ($totalReales === 0) {
                $this->warn("No hay snapshots clínicos reales en BD que cumplan los criterios de suficiencia (ventana=7, adherencia>=0.7143).");
                return self::SUCCESS;
            }
        }

        foreach ($snapshots as $snap) {
            $this->newLine();
            $this->info("Procesando Snapshot #{$snap->cod_features_nutricionales} (Paciente ID: {$snap->cod_pacientes_fnu})...");

            $prediccion = $inferenceService->guardarPrediccion($snap, $modelo);
            $res = $inferenceService->ejecutarInferencia($snap, $modelo);

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['ID Predicción', $prediccion->cod_predicciones_ml],
                    ['Snapshot ID', $snap->cod_features_nutricionales],
                    ['Paciente ID', $snap->cod_pacientes_fnu],
                    ['Resultado Asignado', $res['resultado_principal']],
                    ['Distancia al Centroide', $res['distancia_centroide_asignado']],
                    ['Distancia C0', $res['distancias_centroides']['Cluster 0'] ?? '-'],
                    ['Distancia C1', $res['distancias_centroides']['Cluster 1'] ?? '-'],
                    ['Distancia C2', $res['distancias_centroides']['Cluster 2'] ?? '-'],
                    ['Distancia C3', $res['distancias_centroides']['Cluster 3'] ?? '-'],
                    ['Calorías (kcal)', $snap->promedio_calorias_fnu],
                    ['% Proteína / % Carbo / % Grasa', "{$snap->pct_proteina_fnu}% / {$snap->pct_carbohidratos_fnu}% / {$snap->pct_grasa_fnu}%"],
                    ['Fibra / Azúcar / Sodio (por 1000kcal)', "{$res['indicadores_principales']['fibra_por_1000kcal']}g / {$res['indicadores_principales']['azucar_por_1000kcal']}g / {$res['indicadores_principales']['sodio_por_1000kcal']}mg"],
                    ['Score de Confianza', 'NULL (No aplica a K-Means)'],
                    ['Estado Validación', $prediccion->estado_validacion_pml],
                ]
            );
        }

        return self::SUCCESS;
    }
}
