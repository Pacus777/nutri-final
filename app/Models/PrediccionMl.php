<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrediccionMl extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_VALIDADO_CORRECTO = 'validado_correcto';
    public const ESTADO_CORREGIDO = 'corregido';
    public const ESTADO_DESCARTADO = 'descartado';

    public const ESTADOS_VALIDACION = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_VALIDADO_CORRECTO,
        self::ESTADO_CORREGIDO,
        self::ESTADO_DESCARTADO,
    ];

    protected $table = 'predicciones_ml';
    protected $primaryKey = 'cod_predicciones_ml';

    protected $fillable = [
        'cod_pacientes_pml',
        'cod_modelos_ml_pml',
        'cod_features_nutricionales_pml',
        'fec_prediccion_pml',
        'resultado_principal_pml',
        'score_confianza_pml',
        'probabilidades_clases_pml',
        'explicabilidad_pml',
        'resultado_validado_pml',
        'cod_nutricionistas_validador_pml',
        'estado_validacion_pml',
        'fec_validacion_pml',
        'obs_validacion_pml',
        'origen_datos_pml',
        'referencia_origen_pml',
    ];

    protected function casts(): array
    {
        return [
            'fec_prediccion_pml' => 'datetime',
            'score_confianza_pml' => 'decimal:4',
            'probabilidades_clases_pml' => 'array',
            'explicabilidad_pml' => 'array',
            'fec_validacion_pml' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_pml', 'cod_pacientes');
    }

    public function modeloMl()
    {
        return $this->belongsTo(ModeloMl::class, 'cod_modelos_ml_pml', 'cod_modelos_ml');
    }

    public function featureNutricional()
    {
        return $this->belongsTo(FeatureNutricional::class, 'cod_features_nutricionales_pml', 'cod_features_nutricionales');
    }

    public function nutricionistaValidador()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_validador_pml', 'cod_nutricionistas');
    }

    public function riesgosNutricionales()
    {
        return $this->hasMany(RiesgoNutricional::class, 'cod_predicciones_ml_rnu', 'cod_predicciones_ml');
    }

    public function asignacionPatron()
    {
        return $this->hasOne(AsignacionPatronPaciente::class, 'cod_predicciones_ml_app', 'cod_predicciones_ml');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_validacion_pml', self::ESTADO_PENDIENTE);
    }
}
