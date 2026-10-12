<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_composicion_nutricional', function (Blueprint $table) {
            // PK: Código Auditoría Composición Nutricional
            $table->bigIncrements('cod_auditoria_composicion');

            // FK: Código Alimento
            $table->unsignedBigInteger('cod_alimentos_acn')
                ->comment('Alimento cuya composición nutricional fue corregida o revisada.');

            // FK: Código Nutricionista
            $table->unsignedBigInteger('cod_nutricionistas_acn')
                ->nullable()
                ->comment('Nutricionista responsable de la corrección o revisión.');

            // FK opcional: composición afectada
            $table->unsignedBigInteger('cod_tabla_composicion_nutricional_acn')
                ->nullable()
                ->comment('Registro de composición nutricional afectado por el cambio.');

            // CAMPO: Campo modificado
            $table->string('cam_mod_acn', 80)
                ->comment('Campo nutricional modificado, por ejemplo: pro_tcn, cal_tcn, gra_tcn.');

            // CAMPO: Valor anterior
            $table->string('val_ant_acn', 120)
                ->nullable()
                ->comment('Valor anterior antes de la corrección.');

            // CAMPO: Valor nuevo
            $table->string('val_nue_acn', 120)
                ->nullable()
                ->comment('Nuevo valor después de la corrección.');

            // CAMPO: Motivo del cambio
            $table->text('mot_acn')
                ->nullable()
                ->comment('Motivo de la corrección realizada.');

            // CAMPO: Estado auditoría
            $table->string('est_acn', 20)
                ->default('registrado')
                ->comment('Estado del registro de auditoría.');

            $table->timestamps();

            $table->foreign('cod_alimentos_acn', 'fk_acn_alimentos')
                ->references('cod_alimentos')
                ->on('alimentos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('cod_nutricionistas_acn', 'fk_acn_nutricionistas')
                ->references('cod_nutricionistas')
                ->on('nutricionistas')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('cod_tabla_composicion_nutricional_acn', 'fk_acn_tcn')
                ->references('cod_tabla_composicion_nutricional')
                ->on('tabla_composicion_nutricional')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('cod_alimentos_acn', 'idx_acn_alimentos');
            $table->index('cod_nutricionistas_acn', 'idx_acn_nutricionistas');
            $table->index('cod_tabla_composicion_nutricional_acn', 'idx_acn_tcn');
        });

        DB::statement("ALTER TABLE auditoria_composicion_nutricional COMMENT = 'Auditoría de correcciones realizadas sobre la composición nutricional de alimentos.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_composicion_nutricional');
    }
};
