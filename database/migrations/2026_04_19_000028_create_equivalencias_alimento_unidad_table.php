<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equivalencias_alimento_unidad', function (Blueprint $table) {
            // PK: Código Equivalencia Alimento Unidad
            $table->bigIncrements('cod_equivalencia');

            // FK: Código Alimento
            $table->unsignedBigInteger('cod_alimentos_eau')
                ->comment('Alimento al que pertenece la equivalencia.');

            // CAMPO: Unidad
            $table->string('uni_eau', 60)
                ->comment('Unidad usada por el paciente: taza, pieza, cucharada, porción, etc.');

            // CAMPO: Cantidad de referencia
            $table->decimal('cant_ref_eau', 10, 2)
                ->default(1)
                ->comment('Cantidad de referencia para la unidad indicada.');

            // CAMPO: Gramos equivalentes
            $table->decimal('gramos_eq_eau', 10, 2)
                ->comment('Equivalencia estimada en gramos o mililitros.');

            // CAMPO: Observación
            $table->text('obs_eau')
                ->nullable()
                ->comment('Observaciones sobre la equivalencia.');

            // CAMPO: Estado
            $table->string('est_eau', 20)
                ->default('activo')
                ->comment('Estado de la equivalencia.');

            $table->timestamps();

            $table->foreign('cod_alimentos_eau', 'fk_eau_alimentos')
                ->references('cod_alimentos')
                ->on('alimentos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['cod_alimentos_eau', 'uni_eau', 'cant_ref_eau'], 'uq_eau_alimento_unidad');
            $table->index('cod_alimentos_eau', 'idx_eau_alimentos');
        });

        DB::statement("ALTER TABLE equivalencias_alimento_unidad COMMENT = 'Equivalencias de unidades alimentarias para convertir tazas, piezas o porciones a gramos o mililitros.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('equivalencias_alimento_unidad');
    }
};
