<?php

namespace Tests\Feature;

use App\Models\FeatureNutricional;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MlExportFeaturesCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_testing_database_is_isolated_from_production(): void
    {
        $currentDb = config('database.connections.' . config('database.default') . '.database');
        $this->assertEquals('nutricional_testing_isolated', $currentDb, 'Los tests deben ejecutarse exclusivamente sobre la base aislada de testing.');
        $this->assertNotEquals('nutricional-dilan-sistema-final', $currentDb, 'Los tests nunca deben apuntar a la base de datos operativa.');
    }

    public function test_artisan_export_features_to_csv_with_anonymization_and_provenance(): void
    {
        $uniq = Str::random(6);

        $usuario = Usuario::create([
            'nom_usu' => 'Paciente',
            'ape_usu' => 'ExportTest ' . $uniq,
            'ci_usu' => '8899' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1995-03-20',
            'cor_usu' => 'export_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('secret123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuario->cod_usuarios,
            'sex_pac' => 'femenino',
            'pes_pac' => 62.0,
            'estatura_pac' => 1.65,
            'est_pac' => 'activo',
        ]);

        $snap1 = FeatureNutricional::create([
            'cod_pacientes_fnu' => $paciente->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => '2026-06-01',
            'fec_fin_fnu' => '2026-06-07',
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 6,
            'adherencia_registro_fnu' => 0.8571,
            'ratio_validacion_fnu' => 0.8333,
            'promedio_calorias_fnu' => 1950.00,
            'std_calorias_fnu' => 120.00,
            'promedio_proteina_g_fnu' => 75.00,
            'promedio_carbohidratos_g_fnu' => 240.00,
            'promedio_grasas_g_fnu' => 55.00,
            'promedio_fibra_g_fnu' => 22.00,
            'promedio_azucar_g_fnu' => 35.00,
            'promedio_sodio_mg_fnu' => 1800.00,
            'pct_proteina_fnu' => 18.00,
            'pct_carbohidratos_fnu' => 55.00,
            'pct_grasa_fnu' => 27.00,
            'frecuencia_desayuno_fnu' => 0.9000,
            'frecuencia_cena_fnu' => 0.8500,
            'frecuencia_snack_fnu' => 0.5000,
            'promedio_comidas_dia_fnu' => 3.50,
            'variabilidad_horaria_min_fnu' => 25.00,
            'cobertura_composicion_pct_fnu' => 98.00,
            'edad_fnu' => 31,
            'sexo_fnu' => 'femenino',
            'imc_fnu' => 22.77,
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA,
            'referencia_origen_fnu' => 'MlExportFeaturesCommandTest',
        ]);

        $tempCsv = storage_path('framework/testing/test_export_' . $uniq . '.csv');

        $this->artisan('ml:export-features', [
            '--window' => 7,
            '--features-version' => 'v1.0',
            '--output' => $tempCsv,
            '--eligible-only' => true,
        ])->assertSuccessful();

        $this->assertTrue(File::exists($tempCsv));

        $rows = array_map('str_getcsv', file($tempCsv));
        $header = $rows[0];

        $this->assertEquals('paciente_anonimo_id', $header[0]);
        $this->assertContains('promedio_calorias_fnu', $header);
        $this->assertContains('origen_datos', $header);
        $this->assertContains('referencia_origen', $header);

        if (File::exists($tempCsv)) {
            File::delete($tempCsv);
        }
    }

    public function test_export_features_with_origin_clinico_real_strictly_excludes_fixtures(): void
    {
        $uniq = Str::random(6);

        $usuarioReal = Usuario::create([
            'nom_usu' => 'PacienteClinico',
            'ape_usu' => 'Real ' . $uniq,
            'ci_usu' => '7766' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1990-01-15',
            'cor_usu' => 'paciente_real_' . $uniq . '@hospital.gob.bo',
            'cla_usu' => Hash::make('secret123'),
            'est_usu' => 'activo',
        ]);

        $pacienteReal = Paciente::create([
            'cod_usuarios_pac' => $usuarioReal->cod_usuarios,
            'sex_pac' => 'masculino',
            'pes_pac' => 70.0,
            'estatura_pac' => 1.72,
            'est_pac' => 'activo',
        ]);

        $snapClinico = FeatureNutricional::create([
            'cod_pacientes_fnu' => $pacienteReal->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => '2026-06-01',
            'fec_fin_fnu' => '2026-06-07',
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 7,
            'adherencia_registro_fnu' => 1.0000,
            'promedio_calorias_fnu' => 2050.00,
            'pct_proteina_fnu' => 20.00,
            'pct_carbohidratos_fnu' => 50.00,
            'pct_grasa_fnu' => 30.00,
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_CLINICO_REAL,
            'referencia_origen_fnu' => 'consulta_nutricion_2026',
        ]);

        $snapTest = FeatureNutricional::create([
            'cod_pacientes_fnu' => $pacienteReal->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => '2026-06-08',
            'fec_fin_fnu' => '2026-06-14',
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 6,
            'adherencia_registro_fnu' => 0.8571,
            'promedio_calorias_fnu' => 2200.00,
            'pct_proteina_fnu' => 15.00,
            'pct_carbohidratos_fnu' => 55.00,
            'pct_grasa_fnu' => 30.00,
            'origen_datos_fnu' => FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA,
            'referencia_origen_fnu' => 'fixture_mock',
        ]);

        $tempCsv = storage_path('framework/testing/test_export_clinico_' . $uniq . '.csv');

        $this->artisan('ml:export-features', [
            '--window' => 7,
            '--features-version' => 'v1.0',
            '--origin' => 'clinico_real',
            '--output' => $tempCsv,
        ])->assertSuccessful();

        $this->assertTrue(File::exists($tempCsv));

        $rows = array_map('str_getcsv', file($tempCsv));
        $header = $rows[0];
        $dataRows = array_slice($rows, 1);

        $origenColIdx = array_search('origen_datos', $header);
        $this->assertNotFalse($origenColIdx);

        // Validar que todos los registros exportados tengan origen 'clinico_real'
        foreach ($dataRows as $r) {
            $this->assertEquals('clinico_real', $r[$origenColIdx]);
            $this->assertNotEquals('prueba_automatizada', $r[$origenColIdx]);
            $this->assertNotEquals('demostracion', $r[$origenColIdx]);
        }

        if (File::exists($tempCsv)) {
            File::delete($tempCsv);
        }
    }
}
