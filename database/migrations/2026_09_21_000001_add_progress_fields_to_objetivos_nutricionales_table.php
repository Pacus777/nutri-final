<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objetivos_nutricionales', function (Blueprint $table) {
            if (! Schema::hasColumn('objetivos_nutricionales', 'progreso_actual')) {
                $table->unsignedInteger('progreso_actual')->default(0)->after('des_obe');
            }
            if (! Schema::hasColumn('objetivos_nutricionales', 'meta_progreso')) {
                $table->unsignedInteger('meta_progreso')->default(7)->after('progreso_actual');
            }
            if (! Schema::hasColumn('objetivos_nutricionales', 'unidad_progreso')) {
                $table->string('unidad_progreso', 50)->default('veces')->after('meta_progreso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('objetivos_nutricionales', function (Blueprint $table) {
            $table->dropColumn(['progreso_actual', 'meta_progreso', 'unidad_progreso']);
        });
    }
};
