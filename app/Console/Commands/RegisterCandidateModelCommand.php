<?php

namespace App\Console\Commands;

use App\Models\ClusterModelo;
use App\Models\ModeloMl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RegisterCandidateModelCommand extends Command
{
    protected $signature = 'ml:register-candidate {--file=}';

    protected $description = 'Registra un modelo candidato de clustering y sus centroides en modelos_ml y clusters_modelo.';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('nutrition-analytics/artifacts/candidate_model_metadata.json');

        if (!File::exists($filePath)) {
            $this->error("No se encontró el archivo de metadatos en: {$filePath}");
            return self::FAILURE;
        }

        $jsonContent = File::get($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['modelo'])) {
            $this->error("El archivo JSON de metadatos no tiene un formato válido.");
            return self::FAILURE;
        }

        $modelData = $data['modelo'];
        $clustersData = $data['clusters'] ?? [];

        $this->info("Registrando modelo candidato: {$modelData['nombre_mml']} ({$modelData['version_mml']})...");

        DB::beginTransaction();
        try {
            // Crear o actualizar registro en modelos_ml
            $modelo = ModeloMl::updateOrCreate(
                [
                    'nombre_mml' => $modelData['nombre_mml'],
                    'version_mml' => $modelData['version_mml'],
                ],
                [
                    'tipo_algoritmo_mml' => $modelData['tipo_algoritmo_mml'] ?? 'kmeans',
                    'tarea_mml' => $modelData['tarea_mml'] ?? 'clustering',
                    'version_features_mml' => $modelData['version_features_mml'] ?? 'v1.0',
                    'ruta_artefacto_mml' => $modelData['ruta_artefacto_mml'] ?? null,
                    'origen_dataset_mml' => $modelData['origen_dataset_mml'] ?? 'sintetico_controlado',
                    'hash_dataset_mml' => $modelData['hash_dataset_mml'] ?? null,
                    'numero_muestras_mml' => $modelData['numero_muestras_mml'] ?? 0,
                    'numero_pacientes_mml' => $modelData['numero_pacientes_mml'] ?? 0,
                    'fec_inicio_dataset_mml' => $modelData['fec_inicio_dataset_mml'] ?? null,
                    'fec_fin_dataset_mml' => $modelData['fec_fin_dataset_mml'] ?? null,
                    'random_seed_mml' => $modelData['random_seed_mml'] ?? 42,
                    'parametros_mml' => $modelData['parametros_mml'] ?? null,
                    'metricas_rendimiento_mml' => $modelData['metricas_rendimiento_mml'] ?? null,
                    'features_requeridas_mml' => $modelData['features_requeridas_mml'] ?? null,
                    'est_mml' => $modelData['est_mml'] ?? ModeloMl::ESTADO_VALIDACION,
                    'entrenado_en_mml' => $modelData['entrenado_en_mml'] ?? now(),
                ]
            );

            // Eliminar clusters anteriores del modelo si existieran
            ClusterModelo::where('cod_modelos_ml_clm', $modelo->cod_modelos_ml)->delete();

            // Insertar clusters del modelo
            foreach ($clustersData as $cluster) {
                ClusterModelo::create([
                    'cod_modelos_ml_clm' => $modelo->cod_modelos_ml,
                    'numero_cluster_clm' => $cluster['numero_cluster'],
                    'cod_patrones_alimenticios_clm' => null, // Sin mapeo definitivo aún
                    'centroide_json_clm' => $cluster['centroide'],
                    'descripcion_automatica_clm' => $cluster['descripcion_automatica'] ?? 'Cluster ' . $cluster['numero_cluster'],
                    'descripcion_profesional_clm' => null,
                    'estado_validacion_clm' => ClusterModelo::ESTADO_PENDIENTE,
                ]);
            }

            DB::commit();

            $this->info("Modelo y " . count($clustersData) . " clusters registrados con éxito (ID: {$modelo->cod_modelos_ml}).");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID Modelo', $modelo->cod_modelos_ml],
                    ['Nombre', $modelo->nombre_mml],
                    ['Versión', $modelo->version_mml],
                    ['Algoritmo', $modelo->tipo_algoritmo_mml],
                    ['Estado', $modelo->est_mml],
                    ['Origen Dataset', $modelo->origen_dataset_mml],
                    ['Muestras', $modelo->numero_muestras_mml],
                    ['Pacientes', $modelo->numero_pacientes_mml],
                    ['Hash SHA256 Dataset', substr($modelo->hash_dataset_mml ?? '', 0, 16) . '...'],
                    ['Clusters Creados', count($clustersData)],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error al registrar modelo candidato: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
