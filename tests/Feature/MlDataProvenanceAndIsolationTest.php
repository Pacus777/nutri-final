<?php

namespace Tests\Feature;

use App\Models\FeatureNutricional;
use App\Models\ModeloMl;
use App\Models\Paciente;
use App\Models\PrediccionMl;
use App\Models\Usuario;
use App\Services\MlInferenceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MlDataProvenanceAndIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private function crearPacienteYSnapshot(string $origen = FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA, ?string $referencia = null): array
    {
        $uniq = Str::random(6);

        $usuario = Usuario::create([
            'nom_usu' => 'Paciente',
            'ape_usu' => 'Provenance ' . $uniq,
            'ci_usu' => '6655' . rand(100, 999),
            'ext_ci_usu' => 'LP',
            'fec_nac_usu' => '1992-08-10',
            'cor_usu' => 'prov_' . $uniq . '@test.com',
            'cla_usu' => Hash::make('secret123'),
            'est_usu' => 'activo',
        ]);

        $paciente = Paciente::create([
            'cod_usuarios_pac' => $usuario->cod_usuarios,
            'sex_pac' => 'masculino',
            'pes_pac' => 72.0,
            'estatura_pac' => 1.74,
            'est_pac' => 'activo',
        ]);

        $snapshot = FeatureNutricional::create([
            'cod_pacientes_fnu' => $paciente->cod_pacientes,
            'version_features_fnu' => 'v1.0',
            'fec_inicio_fnu' => now()->subDays(7)->format('Y-m-d'),
            'fec_fin_fnu' => now()->subDay()->format('Y-m-d'),
            'ventana_dias_fnu' => 7,
            'dias_registrados_fnu' => 7,
            'adherencia_registro_fnu' => 1.0000,
            'promedio_calorias_fnu' => 2100.0,
            'pct_proteina_fnu' => 18.0,
            'pct_carbohidratos_fnu' => 52.0,
            'pct_grasa_fnu' => 30.0,
            'promedio_fibra_g_fnu' => 22.0,
            'promedio_azucar_g_fnu' => 40.0,
            'promedio_sodio_mg_fnu' => 2000.0,
            'std_calorias_fnu' => 110.0,
            'frecuencia_desayuno_fnu' => 0.90,
            'frecuencia_cena_fnu' => 0.90,
            'frecuencia_snack_fnu' => 0.50,
            'promedio_comidas_dia_fnu' => 3.5,
            'origen_datos_fnu' => $origen,
            'referencia_origen_fnu' => $referencia,
        ]);

        return compact('usuario', 'paciente', 'snapshot');
    }

    public function test_testing_database_is_strictly_isolated(): void
    {
        $currentDb = config('database.connections.' . config('database.default') . '.database');
        $this->assertEquals('nutricional_testing_isolated', $currentDb);
        $this->assertNotEquals('nutricional-dilan-sistema-final', $currentDb);
    }

    public function test_snapshot_retains_assigned_provenance_and_does_not_mutate_to_clinico_real(): void
    {
        $eco = $this->crearPacienteYSnapshot(
            FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA,
            'MlDataProvenanceAndIsolationTest::test_snapshot_retains_assigned_provenance'
        );

        $snap = FeatureNutricional::find($eco['snapshot']->cod_features_nutricionales);
        $this->assertEquals(FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA, $snap->origen_datos_fnu);
        $this->assertEquals('MlDataProvenanceAndIsolationTest::test_snapshot_retains_assigned_provenance', $snap->referencia_origen_fnu);
        $this->assertNotEquals(FeatureNutricional::ORIGEN_CLINICO_REAL, $snap->origen_datos_fnu);
    }

    public function test_prediction_inherits_provenance_and_reference_from_source_snapshot(): void
    {
        $eco = $this->crearPacienteYSnapshot(
            FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA,
            'test_fixture_ml'
        );

        $service = app(MlInferenceService::class);
        $modelo = $service->getModeloKMeansCandidato();

        $pred = $service->guardarPrediccion($eco['snapshot'], $modelo);

        $this->assertEquals(FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA, $pred->origen_datos_pml);
        $this->assertEquals('test_fixture_ml', $pred->referencia_origen_pml);
        $this->assertNotEquals(FeatureNutricional::ORIGEN_CLINICO_REAL, $pred->origen_datos_pml);
    }

    public function test_export_features_with_origin_filter_clinico_real_strictly_excludes_fixtures(): void
    {
        $ecoTest = $this->crearPacienteYSnapshot(FeatureNutricional::ORIGEN_PRUEBA_AUTOMATIZADA, 'fixture_test');
        $ecoReal = $this->crearPacienteYSnapshot(FeatureNutricional::ORIGEN_CLINICO_REAL, 'consulta_externa_hospital');

        $uniq = Str::random(6);
        $tempCsv = storage_path('framework/testing/export_prov_test_' . $uniq . '.csv');

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

        $origenIdx = array_search('origen_datos', $header);
        $this->assertNotFalse($origenIdx);

        foreach ($dataRows as $row) {
            $this->assertEquals('clinico_real', $row[$origenIdx]);
            $this->assertNotEquals('prueba_automatizada', $row[$origenIdx]);
        }

        if (File::exists($tempCsv)) {
            File::delete($tempCsv);
        }
    }
}
