<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riesgos_nutricionales', function (Blueprint $table) {
            if (! Schema::hasColumn('riesgos_nutricionales', 'origen_rnu')) {
                // Posibles valores: 'legacy', 'heuristico', 'algoritmo_ml', 'nutricionista'
                $table->string('origen_rnu', 40)->default('legacy')->after('des_rnu');
            }
            if (! Schema::hasColumn('riesgos_nutricionales', 'cod_predicciones_ml_rnu')) {
                $table->foreignId('cod_predicciones_ml_rnu')
                    ->nullable()
                    ->after('origen_rnu')
                    ->constrained('predicciones_ml', 'cod_predicciones_ml')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('riesgos_nutricionales', 'confianza_rnu')) {
                $table->decimal('confianza_rnu', 5, 4)->nullable()->after('cod_predicciones_ml_rnu');
            }
            if (! Schema::hasColumn('riesgos_nutricionales', 'estado_validacion_rnu')) {
                // Posibles valores: 'pendiente', 'confirmado', 'ajustado', 'descartado'
                $table->string('estado_validacion_rnu', 30)->default('pendiente')->after('confianza_rnu');
            }
            if (! Schema::hasColumn('riesgos_nutricionales', 'cod_nutricionistas_rnu')) {
                $table->foreignId('cod_nutricionistas_rnu')
                    ->nullable()
                    ->after('estado_validacion_rnu')
                    ->constrained('nutricionistas', 'cod_nutricionistas')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('riesgos_nutricionales', 'fec_validacion_rnu')) {
                $table->timestamp('fec_validacion_rnu')->nullable()->after('cod_nutricionistas_rnu');
            }
            if (! Schema::hasColumn('riesgos_nutricionales', 'obs_profesional_rnu')) {
                $table->text('obs_profesional_rnu')->nullable()->after('fec_validacion_rnu');
            }

            // Índices adicionales
            $table->index(['cod_pacientes_rnu', 'fec_rnu'], 'idx_rnu_paciente_fecha');
            $table->index(['niv_rnu', 'estado_validacion_rnu'], 'idx_rnu_nivel_validacion');
            $table->index('origen_rnu', 'idx_rnu_origen');
        });
    }

    public function down(): void
    {
        Schema::table('riesgos_nutricionales', function (Blueprint $table) {
            $table->dropIndex('idx_rnu_paciente_fecha');
            $table->dropIndex('idx_rnu_nivel_validacion');
            $table->dropIndex('idx_rnu_origen');

            $table->dropConstrainedForeignId('cod_predicciones_ml_rnu');
            $table->dropConstrainedForeignId('cod_nutricionistas_rnu');

            $table->dropColumn([
                'origen_rnu',
                'confianza_rnu',
                'estado_validacion_rnu',
                'fec_validacion_rnu',
                'obs_profesional_rnu',
            ]);
        });
    }
};
