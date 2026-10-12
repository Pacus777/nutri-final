<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features_nutricionales', function (Blueprint $table) {
            $table->decimal('variabilidad_horaria_min_fnu', 6, 2)->nullable()->default(null)->change();
            $table->unsignedSmallInteger('dias_calorias_altas_fnu')->nullable()->default(null)->change();
            $table->unsignedSmallInteger('dias_proteina_baja_fnu')->nullable()->default(null)->change();
            $table->unsignedSmallInteger('dias_fibra_baja_fnu')->nullable()->default(null)->change();
            $table->unsignedSmallInteger('dias_azucar_alta_fnu')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('features_nutricionales', function (Blueprint $table) {
            $table->decimal('variabilidad_horaria_min_fnu', 6, 2)->default(0.00)->nullable(false)->change();
            $table->unsignedSmallInteger('dias_calorias_altas_fnu')->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('dias_proteina_baja_fnu')->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('dias_fibra_baja_fnu')->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('dias_azucar_alta_fnu')->default(0)->nullable(false)->change();
        });
    }
};
