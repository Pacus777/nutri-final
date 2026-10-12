<?php

namespace App\Console\Commands;

use App\Models\FeatureNutricional;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportFeaturesCommand extends Command
{
    protected $signature = 'ml:export-features 
                            {--window=7 : Ventana de dias a exportar} 
                            {--features-version=v1.0 : Version de features (ej: v1.0)} 
                            {--ver= : Alias de version de features}
                            {--output= : Ruta opcional de destino del archivo CSV} 
                            {--origin= : Filtrar por procedencia (clinico_real, prueba_automatizada, demostracion, legacy_desconocido)}
                            {--origen= : Alias de origin}
                            {--eligible-only : Exportar solo snapshots elegibles (adherencia >= 0.7143)}';

    protected $description = 'Exporta snapshots analíticos de features_nutricionales a CSV para entrenamiento y experimentación offline de ML con filtrado estricto por procedencia.';

    public function handle(): int
    {
        $window = (int) $this->option('window');
        $version = (string) ($this->option('ver') ?: $this->option('features-version') ?: 'v1.0');
        $eligibleOnly = (bool) $this->option('eligible-only');
        $origin = (string) ($this->option('origen') ?: $this->option('origin') ?: '');
        $outputPath = $this->option('output');

        $this->info("Iniciando exportación de features (Ventana: {$window} días, Versión: {$version}, Origen: " . ($origin ?: 'TODOS') . ")...");

        // Construir consulta Eloquent
        $query = FeatureNutricional::query()
            ->where('ventana_dias_fnu', $window)
            ->where('version_features_fnu', $version);

        if ($origin !== '') {
            $query->where('origen_datos_fnu', $origin);
        }

        if ($eligibleOnly) {
            $query->where('adherencia_registro_fnu', '>=', 0.7143);
        }

        $totalRegistros = $query->count();
        if ($totalRegistros === 0) {
            $this->warn('No se encontraron snapshots de features que cumplan con los criterios especificados.');
            return self::SUCCESS;
        }

        // Definir ruta de salida si no se especificó
        if (empty($outputPath)) {
            $dir = base_path('nutrition-analytics/datasets/exported');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $timestamp = now()->format('Ymd_His');
            $outputPath = "{$dir}/features_export_w{$window}_{$version}_{$timestamp}.csv";
        } else {
            $dir = dirname($outputPath);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
        }
        $this->line("Escribiendo a: {$outputPath}");

        // Columnas a exportar
        $headers = [
            'paciente_anonimo_id',
            'snapshot_id',
            'version_features',
            'fec_inicio',
            'fec_fin',
            'ventana_dias',
            'dias_registrados',
            'adherencia_registro_fnu',
            'ratio_validacion_fnu',
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
            'frecuencia_desayuno_fnu',
            'frecuencia_cena_fnu',
            'frecuencia_snack_fnu',
            'promedio_comidas_dia_fnu',
            'variabilidad_horaria_min_fnu',
            'cobertura_composicion_pct_fnu',
            'dias_calorias_altas_fnu',
            'dias_proteina_baja_fnu',
            'dias_fibra_baja_fnu',
            'dias_azucar_alta_fnu',
            'edad_fnu',
            'sexo_fnu',
            'imc_fnu',
            'cambio_peso_fnu',
            'origen_datos',
            'referencia_origen',
        ];

        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo de salida para escritura: {$outputPath}");
            return self::FAILURE;
        }

        fputcsv($handle, $headers);

        $salt = config('app.key') ?: 'nutri_anonymize_salt_2026';
        $pacientesUnicos = [];
        $contador = 0;

        $query->chunk(500, function ($snapshots) use ($handle, $salt, &$pacientesUnicos, &$contador) {
            foreach ($snapshots as $snap) {
                // Anonimización determinista del ID de paciente
                $anonId = 'PAC_' . substr(hash_hmac('sha256', (string) $snap->cod_pacientes_fnu, $salt), 0, 12);
                $pacientesUnicos[$anonId] = true;

                $row = [
                    $anonId,
                    $snap->cod_features_nutricionales,
                    $snap->version_features_fnu,
                    $snap->fec_inicio_fnu ? $snap->fec_inicio_fnu->format('Y-m-d') : null,
                    $snap->fec_fin_fnu ? $snap->fec_fin_fnu->format('Y-m-d') : null,
                    $snap->ventana_dias_fnu,
                    $snap->dias_registrados_fnu,
                    $snap->adherencia_registro_fnu,
                    $snap->ratio_validacion_fnu,
                    $snap->promedio_calorias_fnu,
                    $snap->std_calorias_fnu,
                    $snap->promedio_proteina_g_fnu,
                    $snap->promedio_carbohidratos_g_fnu,
                    $snap->promedio_grasas_g_fnu,
                    $snap->promedio_fibra_g_fnu,
                    $snap->promedio_azucar_g_fnu,
                    $snap->promedio_sodio_mg_fnu,
                    $snap->pct_proteina_fnu,
                    $snap->pct_carbohidratos_fnu,
                    $snap->pct_grasa_fnu,
                    $snap->frecuencia_desayuno_fnu,
                    $snap->frecuencia_cena_fnu,
                    $snap->frecuencia_snack_fnu,
                    $snap->promedio_comidas_dia_fnu,
                    $snap->variabilidad_horaria_min_fnu,
                    $snap->cobertura_composicion_pct_fnu,
                    $snap->dias_calorias_altas_fnu,
                    $snap->dias_proteina_baja_fnu,
                    $snap->dias_fibra_baja_fnu,
                    $snap->dias_azucar_alta_fnu,
                    $snap->edad_fnu,
                    $snap->sexo_fnu,
                    $snap->imc_fnu,
                    $snap->cambio_peso_fnu,
                    $snap->origen_datos_fnu ?? 'legacy_desconocido',
                    $snap->referencia_origen_fnu ?? null,
                ];

                fputcsv($handle, $row);
                $contador++;
            }
        });

        fclose($handle);

        $totalPacientes = count($pacientesUnicos);
        $this->info("Exportación completada exitosamente.");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total registros exportados', $contador],
                ['Pacientes anonimizados únicos', $totalPacientes],
                ['Ventana de días', $window],
                ['Versión de features', $version],
                ['Filtro origen de datos', $origin ?: 'TODOS'],
                ['Filtro elegibilidad (>=0.7143)', $eligibleOnly ? 'Sí' : 'No'],
                ['Ruta de archivo', $outputPath],
            ]
        );

        return self::SUCCESS;
    }
}
