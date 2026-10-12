<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureNutricional extends Model
{
    use HasFactory;

    protected $table = 'features_nutricionales';
    protected $primaryKey = 'cod_features_nutricionales';

    public const ORIGEN_CLINICO_REAL = 'clinico_real';
    public const ORIGEN_PRUEBA_AUTOMATIZADA = 'prueba_automatizada';
    public const ORIGEN_DEMOSTRACION = 'demostracion';
    public const ORIGEN_LEGACY_DESCONOCIDO = 'legacy_desconocido';
    public const ORIGEN_SINTETICO_CONTROLADO = 'sintetico_controlado';

    protected $fillable = [
        'cod_pacientes_fnu',
        'version_features_fnu',
        'fec_inicio_fnu',
        'fec_fin_fnu',
        'ventana_dias_fnu',
        'dias_registrados_fnu',
        'adherencia_registro_fnu',
        'ratio_validacion_fnu',
        'promedio_calorias_fnu',
        'std_calorias_fnu',
        'promedio_proteina_g_fnu',
        'promedio_carbohidratos_g_fnu',
        'promedio_grasas_g_fnu',
        'promedio_fibra_g_fnu',
        'promedio_azucar_g_fnu',
        'promedio_sodio_mg_fnu',
        'pct_carbohidratos_fnu',
        'pct_proteina_fnu',
        'pct_grasa_fnu',
        'frecuencia_desayuno_fnu',
        'frecuencia_cena_fnu',
        'frecuencia_snack_fnu',
        'promedio_comidas_dia_fnu',
        'variabilidad_horaria_min_fnu',
        'cobertura_composicion_pct_fnu',
        'dias_calorias_altas_fnu',
        'dias_proteina_baja_fnu',
        'dias_fibra_baja_fnu',
        'dias_azucar_alta_fnu',
        'imc_fnu',
        'cambio_peso_fnu',
        'edad_fnu',
        'sexo_fnu',
        'vector_json_fnu',
        'origen_datos_fnu',
        'referencia_origen_fnu',
    ];

    protected function casts(): array
    {
        return [
            'fec_inicio_fnu' => 'date',
            'fec_fin_fnu' => 'date',
            'ventana_dias_fnu' => 'integer',
            'dias_registrados_fnu' => 'integer',
            'adherencia_registro_fnu' => 'decimal:4',
            'ratio_validacion_fnu' => 'decimal:4',
            'promedio_calorias_fnu' => 'decimal:2',
            'std_calorias_fnu' => 'decimal:2',
            'promedio_proteina_g_fnu' => 'decimal:2',
            'promedio_carbohidratos_g_fnu' => 'decimal:2',
            'promedio_grasas_g_fnu' => 'decimal:2',
            'promedio_fibra_g_fnu' => 'decimal:2',
            'promedio_azucar_g_fnu' => 'decimal:2',
            'promedio_sodio_mg_fnu' => 'decimal:2',
            'pct_carbohidratos_fnu' => 'decimal:2',
            'pct_proteina_fnu' => 'decimal:2',
            'pct_grasa_fnu' => 'decimal:2',
            'frecuencia_desayuno_fnu' => 'decimal:4',
            'frecuencia_cena_fnu' => 'decimal:4',
            'frecuencia_snack_fnu' => 'decimal:4',
            'promedio_comidas_dia_fnu' => 'decimal:2',
            'variabilidad_horaria_min_fnu' => 'decimal:2',
            'cobertura_composicion_pct_fnu' => 'decimal:2',
            'dias_calorias_altas_fnu' => 'integer',
            'dias_proteina_baja_fnu' => 'integer',
            'dias_fibra_baja_fnu' => 'integer',
            'dias_azucar_alta_fnu' => 'integer',
            'imc_fnu' => 'decimal:2',
            'cambio_peso_fnu' => 'decimal:2',
            'edad_fnu' => 'integer',
            'vector_json_fnu' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_fnu', 'cod_pacientes');
    }

    public function predicciones()
    {
        return $this->hasMany(PrediccionMl::class, 'cod_features_nutricionales_pml', 'cod_features_nutricionales');
    }

    public function etiquetasRiesgo()
    {
        return $this->hasMany(EtiquetaRiesgoProfesional::class, 'cod_features_nutricionales_erp', 'cod_features_nutricionales');
    }
}
