<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recomendaciones_nutricionales', function (Blueprint $table) {
            $table->unsignedBigInteger('cod_nutricionistas_rec')->nullable()->change();
            $table->unsignedBigInteger('cod_features_nutricionales_rec')->nullable()->after('cod_nutricionistas_rec');
            $table->unsignedBigInteger('cod_predicciones_ml_rec')->nullable()->after('cod_features_nutricionales_rec');
            $table->string('origen_rec', 40)->default('asistida_ia')->after('tip_rec');
            $table->string('estado_rec', 30)->default('pendiente')->after('origen_rec');
            $table->string('categoria_rec', 60)->nullable()->after('estado_rec');
            $table->text('justificacion_rec')->nullable()->after('categoria_rec');
            $table->timestamp('fec_aprobacion_rec')->nullable()->after('justificacion_rec');

            $table->foreign('cod_features_nutricionales_rec', 'fk_rec_nutricionales_features')
                ->references('cod_features_nutricionales')
                ->on('features_nutricionales')
                ->nullOnDelete();

            $table->foreign('cod_predicciones_ml_rec', 'fk_rec_nutricionales_predicciones')
                ->references('cod_predicciones_ml')
                ->on('predicciones_ml')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recomendaciones_nutricionales', function (Blueprint $table) {
            $table->dropForeign('fk_rec_nutricionales_features');
            $table->dropForeign('fk_rec_nutricionales_predicciones');
            $table->dropColumn([
                'cod_features_nutricionales_rec',
                'cod_predicciones_ml_rec',
                'origen_rec',
                'estado_rec',
                'categoria_rec',
                'justificacion_rec',
                'fec_aprobacion_rec',
            ]);
        });
    }
};
