<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignacion_patron_paciente', function (Blueprint $table) {
            if (! Schema::hasColumn('asignacion_patron_paciente', 'cod_predicciones_ml_app')) {
                $table->foreignId('cod_predicciones_ml_app')
                    ->nullable()
                    ->after('fec_app')
                    ->constrained('predicciones_ml', 'cod_predicciones_ml')
                    ->nullOnDelete();
            }

            $table->index(['cod_pacientes_app', 'fec_app'], 'idx_app_paciente_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('asignacion_patron_paciente', function (Blueprint $table) {
            $table->dropIndex('idx_app_paciente_fecha');
            $table->dropConstrainedForeignId('cod_predicciones_ml_app');
        });
    }
};
