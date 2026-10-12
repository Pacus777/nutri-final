<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features_nutricionales', function (Blueprint $table) {
            $table->id('cod_features_nutricionales');
            
            // Sujeto y control de versión de pipeline
            $table->foreignId('cod_pacientes_fnu')
                ->constrained('pacientes', 'cod_pacientes')
                ->cascadeOnDelete();
            $table->string('version_features_fnu', 30)->default('v1.0');
            
            // Período y completitud de la ventana
            $table->date('fec_inicio_fnu');
            $table->date('fec_fin_fnu');
            $table->unsignedSmallInteger('ventana_dias_fnu');              // 7, 14 o 30 días
            $table->unsignedSmallInteger('dias_registrados_fnu');          // Días reales con registros de ingesta
            $table->decimal('adherencia_registro_fnu', 5, 4)->default(0.0000); // [0-1] (dias_registrados / ventana_dias)
            $table->decimal('ratio_validacion_fnu', 5, 4)->default(0.0000);    // [0-1] (dias_validados / dias_registrados)

            // Consumo promedio diario y dispersión
            $table->decimal('promedio_calorias_fnu', 10, 2)->default(0.00);
            $table->decimal('std_calorias_fnu', 10, 2)->default(0.00);      // Desviación estándar diaria de Kcal
            $table->decimal('promedio_proteina_g_fnu', 8, 2)->default(0.00);
            $table->decimal('promedio_carbohidratos_g_fnu', 8, 2)->default(0.00);
            $table->decimal('promedio_grasas_g_fnu', 8, 2)->default(0.00);
            $table->decimal('promedio_fibra_g_fnu', 8, 2)->default(0.00);
            $table->decimal('promedio_azucar_g_fnu', 8, 2)->default(0.00);
            $table->decimal('promedio_sodio_mg_fnu', 10, 2)->default(0.00);

            // Distribución energética (% sobre total calórico diario, escala 0-100)
            $table->decimal('pct_carbohidratos_fnu', 5, 2)->default(0.00);
            $table->decimal('pct_proteina_fnu', 5, 2)->default(0.00);
            $table->decimal('pct_grasa_fnu', 5, 2)->default(0.00);

            // Conducta y regularidad de comidas (frecuencias en escala 0-1)
            $table->decimal('frecuencia_desayuno_fnu', 5, 4)->default(0.0000); // [0-1] Fracción de días con desayuno
            $table->decimal('frecuencia_cena_fnu', 5, 4)->default(0.0000);     // [0-1] Fracción de días con cena
            $table->decimal('frecuencia_snack_fnu', 5, 4)->default(0.0000);    // [0-1] Fracción de días con snacks/meriendas
            $table->decimal('promedio_comidas_dia_fnu', 4, 2)->default(0.00);   // N° eventos de comida por día
            $table->decimal('variabilidad_horaria_min_fnu', 6, 2)->default(0.00); // Dispersión horaria en minutos
            $table->decimal('cobertura_composicion_pct_fnu', 5, 2)->default(0.00); // % alimentos con datos nutricionales completos

            // Desviaciones y conteo de días críticos (banderas acumuladas según versión de extractor)
            $table->unsignedSmallInteger('dias_calorias_altas_fnu')->default(0);
            $table->unsignedSmallInteger('dias_proteina_baja_fnu')->default(0);
            $table->unsignedSmallInteger('dias_fibra_baja_fnu')->default(0);
            $table->unsignedSmallInteger('dias_azucar_alta_fnu')->default(0);

            // Contexto antropométrico en la fecha de corte
            $table->decimal('imc_fnu', 5, 2)->nullable();
            $table->decimal('cambio_peso_fnu', 6, 2)->nullable(); // Delta peso en kg durante la ventana
            $table->unsignedSmallInteger('edad_fnu')->nullable();
            $table->string('sexo_fnu', 20)->nullable();

            // Snapshot serializado para reproducibilidad exacta
            $table->json('vector_json_fnu')->nullable();

            $table->timestamps();

            // Índices analíticos
            $table->index(['cod_pacientes_fnu', 'fec_fin_fnu'], 'idx_fnu_paciente_fecha');
            $table->index(['ventana_dias_fnu', 'fec_fin_fnu'], 'idx_fnu_ventana_fecha');
            $table->index('version_features_fnu', 'idx_fnu_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features_nutricionales');
    }
};
