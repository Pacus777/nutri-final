<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('features_nutricionales', function (Blueprint $table) {
            $table->string('origen_datos_fnu', 50)
                ->default('legacy_desconocido')
                ->comment('Procedencia: clinico_real, prueba_automatizada, demostracion, legacy_desconocido, sintetico_controlado');

            $table->string('referencia_origen_fnu', 255)
                ->nullable()
                ->comment('Identificador de test, seeder o proceso originador');

            $table->index('origen_datos_fnu', 'idx_features_origen_datos');
        });

        Schema::table('predicciones_ml', function (Blueprint $table) {
            $table->string('origen_datos_pml', 50)
                ->default('legacy_desconocido')
                ->comment('Procedencia: clinico_real, prueba_automatizada, demostracion, legacy_desconocido');

            $table->string('referencia_origen_pml', 255)
                ->nullable()
                ->comment('Identificador de test, seeder o proceso originador');

            $table->index('origen_datos_pml', 'idx_predicciones_origen_datos');
        });

        // Clasificar registros históricos existentes con evidencia demostrable
        $testPacientesIds = DB::table('pacientes')
            ->join('usuarios', 'pacientes.cod_usuarios_pac', '=', 'usuarios.cod_usuarios')
            ->where(function ($query) {
                $query->where('usuarios.cor_usu', 'LIKE', '%test.com')
                    ->orWhere('usuarios.cor_usu', 'LIKE', '%example.test')
                    ->orWhere('usuarios.nom_usu', 'LIKE', '%Prueba%')
                    ->orWhere('usuarios.ape_usu', 'LIKE', '%Evaluado%')
                    ->orWhere('usuarios.ape_usu', 'LIKE', '%ExportTest%')
                    ->orWhere('usuarios.ape_usu', 'LIKE', '%Prueba%');
            })
            ->pluck('pacientes.cod_pacientes')
            ->toArray();

        if (!empty($testPacientesIds)) {
            DB::table('features_nutricionales')
                ->whereIn('cod_pacientes_fnu', $testPacientesIds)
                ->update([
                    'origen_datos_fnu' => 'prueba_automatizada',
                    'referencia_origen_fnu' => 'phpunit_test_fixture',
                ]);

            DB::table('predicciones_ml')
                ->whereIn('cod_pacientes_pml', $testPacientesIds)
                ->update([
                    'origen_datos_pml' => 'prueba_automatizada',
                    'referencia_origen_pml' => 'phpunit_test_fixture',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predicciones_ml', function (Blueprint $table) {
            $table->dropIndex('idx_predicciones_origen_datos');
            $table->dropColumn(['origen_datos_pml', 'referencia_origen_pml']);
        });

        Schema::table('features_nutricionales', function (Blueprint $table) {
            $table->dropIndex('idx_features_origen_datos');
            $table->dropColumn(['origen_datos_fnu', 'referencia_origen_fnu']);
        });
    }
};
