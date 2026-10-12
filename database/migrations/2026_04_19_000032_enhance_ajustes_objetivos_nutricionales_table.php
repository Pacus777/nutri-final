<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes_objetivos_nutricionales', function (Blueprint $table) {
            if (!Schema::hasColumn('ajustes_objetivos_nutricionales', 'motivo_ajn')) {
                $table->string('motivo_ajn', 255)->nullable()->after('des_ajn')
                    ->comment('Motivo del ajuste');
            }

            if (!Schema::hasColumn('ajustes_objetivos_nutricionales', 'estado_anterior_ajn')) {
                $table->string('estado_anterior_ajn', 20)->nullable()->after('motivo_ajn')
                    ->comment('Estado anterior del objetivo');
            }

            if (!Schema::hasColumn('ajustes_objetivos_nutricionales', 'estado_nuevo_ajn')) {
                $table->string('estado_nuevo_ajn', 20)->nullable()->after('estado_anterior_ajn')
                    ->comment('Estado nuevo del objetivo tras el ajuste');
            }

            if (!Schema::hasColumn('ajustes_objetivos_nutricionales', 'descripcion_anterior_ajn')) {
                $table->text('descripcion_anterior_ajn')->nullable()->after('estado_nuevo_ajn')
                    ->comment('Descripción anterior del objetivo');
            }

            if (!Schema::hasColumn('ajustes_objetivos_nutricionales', 'descripcion_nueva_ajn')) {
                $table->text('descripcion_nueva_ajn')->nullable()->after('descripcion_anterior_ajn')
                    ->comment('Descripción nueva del objetivo tras el ajuste');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ajustes_objetivos_nutricionales', function (Blueprint $table) {
            $columns = [
                'motivo_ajn',
                'estado_anterior_ajn',
                'estado_nuevo_ajn',
                'descripcion_anterior_ajn',
                'descripcion_nueva_ajn',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ajustes_objetivos_nutricionales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
