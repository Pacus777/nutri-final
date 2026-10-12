<?php

namespace App\Console\Commands;

use App\Models\EtiquetaRiesgoProfesional;
use App\Models\FeatureNutricional;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportSupervisedDatasetCommand extends Command
{
    protected $signature = 'ml:export-supervised-dataset
                            {--window=7 : Ventana de días de los snapshots (default: 7)}
                            {--features-version=v1.0 : Versión de features de los snapshots}
                            {--output= : Ruta personalizada para guardar el archivo CSV}
                            {--stats-only : Mostrar solo estadísticas sin generar archivo CSV}';

    protected $description = 'Exporta el dataset supervisado de riesgo para ML con anonimización GroupKFold y filtrado estricto de calidad clínica.';

    public function handle(): int
    {
        $window = (int) $this->option('window');
        $version = (string) $this->option('features-version');
        $statsOnly = (bool) $this->option('stats-only');
        $outputPath = $this->option('output');

        $this->info("=== EXPORTACIÓN DE DATASET SUPERVISADO (ML FASE 2D.1) ===");
        $this->line("Criterios de inclusión: origen = clinico_real | estado = valida | nivel_riesgo IS NOT NULL");
        $this->line("Ventana: {$window} días | Versión Features: {$version}");

        // Consultar etiquetas válidas con sus features clínicos
        $query = EtiquetaRiesgoProfesional::query()
            ->with(['featureNutricional'])
            ->where('estado_etiqueta_erp', EtiquetaRiesgoProfesional::ESTADO_VALIDA)
            ->whereNotNull('nivel_riesgo_erp')
            ->whereHas('featureNutricional', function ($q) use ($window, $version) {
                $q->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
                  ->where('ventana_dias_fnu', $window)
                  ->where('version_features_fnu', $version);
            });

        $etiquetasValidas = $query->get();

        // Conteo de etiquetas insuficientes en las mismas condiciones
        $totalInsuficientes = EtiquetaRiesgoProfesional::query()
            ->where('estado_etiqueta_erp', EtiquetaRiesgoProfesional::ESTADO_INSUFICIENTE)
            ->whereHas('featureNutricional', function ($q) use ($window, $version) {
                $q->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
                  ->where('ventana_dias_fnu', $window)
                  ->where('version_features_fnu', $version);
            })
            ->count();

        // Conteo de etiquetas anuladas
        $totalAnuladas = EtiquetaRiesgoProfesional::query()
            ->where('estado_etiqueta_erp', EtiquetaRiesgoProfesional::ESTADO_ANULADA)
            ->whereHas('featureNutricional', function ($q) use ($window, $version) {
                $q->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
                  ->where('ventana_dias_fnu', $window)
                  ->where('version_features_fnu', $version);
            })
            ->count();

        $salt = config('app.key') ?: 'nutri_group_anonymize_salt_2026';

        // Procesar conteos y distribución por paciente
        $conteoBajo = 0;
        $conteoModerado = 0;
        $conteoAlto = 0;
        $distribucionPacientes = [];

        foreach ($etiquetasValidas as $etiqueta) {
            $nivel = strtolower((string) $etiqueta->nivel_riesgo_erp);
            if ($nivel === 'bajo') $conteoBajo++;
            elseif ($nivel === 'moderado') $conteoModerado++;
            elseif ($nivel === 'alto') $conteoAlto++;

            $patientGroupId = 'GRP_' . substr(hash_hmac('sha256', (string) $etiqueta->cod_pacientes_erp, $salt), 0, 12);
            if (!isset($distribucionPacientes[$patientGroupId])) {
                $distribucionPacientes[$patientGroupId] = [
                    'group_id' => $patientGroupId,
                    'total_ventanas' => 0,
                    'bajo' => 0,
                    'moderado' => 0,
                    'alto' => 0,
                ];
            }
            $distribucionPacientes[$patientGroupId]['total_ventanas']++;
            $distribucionPacientes[$patientGroupId][$nivel] = ($distribucionPacientes[$patientGroupId][$nivel] ?? 0) + 1;
        }

        $totalPacientesUnicos = count($distribucionPacientes);
        $totalVentanasEtiquetadas = $etiquetasValidas->count();

        $this->newLine();
        $this->info("--- ESTADÍSTICAS DEL DATASET SUPERVISADO ---");
        $this->table(
            ['Métrica de Dataset', 'Cantidad'],
            [
                ['Pacientes únicos (GroupKFold clusters)', $totalPacientesUnicos],
                ['Ventanas clínicas etiquetadas (Válidas)', $totalVentanasEtiquetadas],
                ['Nivel de Riesgo: BAJO (Target)', $conteoBajo],
                ['Nivel de Riesgo: MODERADO (Target)', $conteoModerado],
                ['Nivel de Riesgo: ALTO (Target)', $conteoAlto],
                ['Etiquetas con Información INSUFICIENTE (Excluidas)', $totalInsuficientes],
                ['Etiquetas ANULADAS (Excluidas)', $totalAnuladas],
            ]
        );

        if ($totalPacientesUnicos > 0) {
            $this->newLine();
            $this->info("--- DISTRIBUCIÓN POR PACIENTE (GroupKFold groups) ---");
            $rowsPacientes = [];
            foreach ($distribucionPacientes as $grp) {
                $rowsPacientes[] = [
                    $grp['group_id'],
                    $grp['total_ventanas'],
                    $grp['bajo'],
                    $grp['moderado'],
                    $grp['alto'],
                ];
            }
            $this->table(
                ['patient_group_id', 'Total Ventanas', 'Bajo', 'Moderado', 'Alto'],
                $rowsPacientes
            );
        }

        if ($statsOnly) {
            return self::SUCCESS;
        }

        if ($totalVentanasEtiquetadas === 0) {
            $this->warn('No hay registros válidos para exportar a CSV.');
            return self::SUCCESS;
        }

        // Definir ruta de salida si no se especificó
        if (empty($outputPath)) {
            $dir = base_path('nutrition-analytics/datasets/supervised');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
            $timestamp = now()->format('Ymd_His');
            $outputPath = "{$dir}/supervised_dataset_w{$window}_{$version}_{$timestamp}.csv";
        } else {
            $dir = dirname($outputPath);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
        }

        $headers = [
            'patient_group_id',
            'cod_etiquetas_riesgo_profesional',
            'cod_features_nutricionales',
            'nivel_riesgo_erp',
            'tipo_riesgo_erp',
            'version_criterio_erp',
            'fec_evaluacion_erp',
            'promedio_calorias_fnu',
            'std_calorias_fnu',
            'promedio_proteina_g_fnu',
            'promedio_carbohidratos_g_fnu',
            'promedio_grasas_g_fnu',
            'promedio_fibra_g_fnu',
            'promedio_azucar_g_fnu',
            'promedio_sodio_mg_fnu',
            'pct_proteina_fnu',
            'pct_carbohidratos_fnu',
            'pct_grasa_fnu',
            'promedio_comidas_dia_fnu',
            'frecuencia_desayuno_fnu',
            'frecuencia_cena_fnu',
            'frecuencia_snack_fnu',
            'variabilidad_horaria_min_fnu',
            'cobertura_composicion_pct_fnu',
            'adherencia_registro_fnu',
            'ratio_validacion_fnu',
            'edad_fnu',
            'sexo_fnu',
            'imc_fnu',
            'cambio_peso_fnu',
            'fec_inicio_fnu',
            'fec_fin_fnu',
            'ventana_dias_fnu',
            'version_features_fnu',
            'origen_datos_fnu',
        ];

        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo para escritura: {$outputPath}");
            return self::FAILURE;
        }

        fputcsv($handle, $headers);

        foreach ($etiquetasValidas as $etiqueta) {
            $snap = $etiqueta->featureNutricional;
            $patientGroupId = 'GRP_' . substr(hash_hmac('sha256', (string) $etiqueta->cod_pacientes_erp, $salt), 0, 12);

            $row = [
                $patientGroupId,
                $etiqueta->cod_etiquetas_riesgo_profesional,
                $snap?->cod_features_nutricionales,
                $etiqueta->nivel_riesgo_erp,
                $etiqueta->tipo_riesgo_erp,
                $etiqueta->version_criterio_erp,
                $etiqueta->fec_evaluacion_erp ? $etiqueta->fec_evaluacion_erp->toDateTimeString() : null,
                $snap?->promedio_calorias_fnu,
                $snap?->std_calorias_fnu,
                $snap?->promedio_proteina_g_fnu,
                $snap?->promedio_carbohidratos_g_fnu,
                $snap?->promedio_grasas_g_fnu,
                $snap?->promedio_fibra_g_fnu,
                $snap?->promedio_azucar_g_fnu,
                $snap?->promedio_sodio_mg_fnu,
                $snap?->pct_proteina_fnu,
                $snap?->pct_carbohidratos_fnu,
                $snap?->pct_grasa_fnu,
                $snap?->promedio_comidas_dia_fnu,
                $snap?->frecuencia_desayuno_fnu,
                $snap?->frecuencia_cena_fnu,
                $snap?->frecuencia_snack_fnu,
                $snap?->variabilidad_horaria_min_fnu,
                $snap?->cobertura_composicion_pct_fnu,
                $snap?->adherencia_registro_fnu,
                $snap?->ratio_validacion_fnu,
                $snap?->edad_fnu,
                $snap?->sexo_fnu,
                $snap?->imc_fnu,
                $snap?->cambio_peso_fnu,
                $snap?->fec_inicio_fnu ? $snap->fec_inicio_fnu->toDateString() : null,
                $snap?->fec_fin_fnu ? $snap->fec_fin_fnu->toDateString() : null,
                $snap?->ventana_dias_fnu,
                $snap?->version_features_fnu,
                $snap?->origen_datos_fnu,
            ];

            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->newLine();
        $this->info("Dataset supervisado exportado exitosamente a:");
        $this->line($outputPath);

        return self::SUCCESS;
    }
}
