<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE objetivos_nutricionales MODIFY cod_pacientes_obe BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE objetivos_nutricionales MODIFY cod_nutricionistas_obe BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE objetivos_nutricionales MODIFY cod_pacientes_obe BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE objetivos_nutricionales MODIFY cod_nutricionistas_obe BIGINT UNSIGNED NOT NULL');
    }
};