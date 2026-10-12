<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabla_composicion_nutricional', function (Blueprint $table) {
            // PK: Código Tabla Composición Nutricional
            $table->bigIncrements('cod_tabla_composicion_nutricional');

            // FK: Código Alimentos Composición
            $table->unsignedBigInteger('cod_alimentos_tcn')
                ->comment('Código del alimento asociado a la composición nutricional.');

            // CAMPO: Porción de referencia
            $table->string('por_ref_tcn', 120)
                ->nullable()
                ->comment('Descripción textual de la porción de referencia.');

            // CAMPO: Cantidad referencia
            $table->decimal('cant_ref_tcn', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Cantidad numérica de la porción de referencia.');

            // CAMPO: Unidad referencia
            $table->string('uni_ref_tcn', 50)
                ->nullable()
                ->comment('Unidad de medida de la porción de referencia.');

            // MACRONUTRIENTES
            $table->decimal('cal_tcn', 10, 2)->default(0)->comment('Calorías.');
            $table->decimal('pro_tcn', 10, 2)->default(0)->comment('Proteínas.');
            $table->decimal('gra_tcn', 10, 2)->default(0)->comment('Grasas totales.');
            $table->decimal('car_tcn', 10, 2)->default(0)->comment('Carbohidratos.');
            $table->decimal('fib_tcn', 10, 2)->default(0)->comment('Fibra.');
            $table->decimal('azu_tcn', 10, 2)->default(0)->comment('Azúcares.');
            $table->decimal('gra_sat_tcn', 10, 2)->default(0)->comment('Grasas saturadas.');
            $table->decimal('gra_mono_tcn', 10, 2)->default(0)->comment('Grasas monoinsaturadas.');
            $table->decimal('gra_poli_tcn', 10, 2)->default(0)->comment('Grasas poliinsaturadas.');

            // MINERALES / OTROS
            $table->decimal('sod_tcn', 10, 2)->default(0)->comment('Sodio.');
            $table->decimal('pot_tcn', 10, 2)->default(0)->comment('Potasio.');
            $table->decimal('col_tcn', 10, 2)->default(0)->comment('Colesterol.');
            $table->decimal('calcio_tcn', 10, 2)->default(0)->comment('Calcio.');
            $table->decimal('hierro_tcn', 10, 2)->default(0)->comment('Hierro.');

            // VITAMINAS
            $table->decimal('vit_a_tcn', 10, 2)->default(0)->comment('Vitamina A.');
            $table->decimal('vit_c_tcn', 10, 2)->default(0)->comment('Vitamina C.');
            $table->decimal('vit_d_tcn', 10, 2)->default(0)->comment('Vitamina D.');

            // CAMPO: Fecha composición
            $table->date('fec_tcn')
                ->comment('Fecha de vigencia o referencia de la composición.');

            // CAMPO: Estado composición
            $table->string('est_tcn', 20)
                ->default('vigente')
                ->comment('Estado de la composición: vigente o archivada.');

            $table->timestamps();

            $table->foreign('cod_alimentos_tcn', 'fk_tabla_composicion_nutricional_alimentos')
                ->references('cod_alimentos')
                ->on('alimentos');
        });

        DB::statement("ALTER TABLE tabla_composicion_nutricional COMMENT = 'Composición nutricional local de referencia para alimentos cacheados o registrados manualmente en el sistema.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('tabla_composicion_nutricional');
    }
};