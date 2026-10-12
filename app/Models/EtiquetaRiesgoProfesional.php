<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtiquetaRiesgoProfesional extends Model
{
    use HasFactory;

    // Niveles de riesgo objetivo (Target supervisado)
    public const NIVEL_BAJO = 'bajo';
    public const NIVEL_MODERADO = 'moderado';
    public const NIVEL_ALTO = 'alto';

    public const NIVELES_RIESGO = [
        self::NIVEL_BAJO,
        self::NIVEL_MODERADO,
        self::NIVEL_ALTO,
    ];

    // Estados de la etiqueta
    public const ESTADO_VALIDA = 'valida';
    public const ESTADO_INSUFICIENTE = 'insuficiente';
    public const ESTADO_ANULADA = 'anulada';

    public const ESTADOS_ETIQUETA = [
        self::ESTADO_VALIDA,
        self::ESTADO_INSUFICIENTE,
        self::ESTADO_ANULADA,
    ];

    public const VERSION_CRITERIO_DEFAULT = 'v1.0';

    // Catálogo inicial configurable de tipos de riesgo complementarios
    public const TIPOS_RIESGO_CATALOGO = [
        'sin_riesgo_aparente' => 'Sin riesgo evidente / Ingesta adecuada',
        'exceso_calorico' => 'Exceso calórico persistente',
        'deficit_energetico' => 'Déficit calórico severo',
        'deficit_fibra' => 'Baja densidad de fibra dietética',
        'exceso_azucares_libres' => 'Elevado consumo de azúcares simples',
        'exceso_sodio' => 'Densidad excesiva de sodio',
        'desbalance_macronutrientes' => 'Desbalance severo en distribución energética',
        'irregularidad_horaria_omision' => 'Alta variabilidad horaria y omisión de comidas',
        'conducta_alimentaria_mixta' => 'Patrón mixto con múltiples factores de riesgo',
        'otro' => 'Otro factor clínico identificado',
    ];

    protected $table = 'etiquetas_riesgo_profesional';
    protected $primaryKey = 'cod_etiquetas_riesgo_profesional';

    protected $fillable = [
        'cod_features_nutricionales_erp',
        'cod_pacientes_erp',
        'cod_nutricionistas_erp',
        'nivel_riesgo_erp',
        'tipo_riesgo_erp',
        'estado_etiqueta_erp',
        'observacion_profesional_erp',
        'version_criterio_erp',
        'fec_evaluacion_erp',
    ];

    protected function casts(): array
    {
        return [
            'fec_evaluacion_erp' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Snapshot nutricional evaluado.
     */
    public function featureNutricional()
    {
        return $this->belongsTo(FeatureNutricional::class, 'cod_features_nutricionales_erp', 'cod_features_nutricionales');
    }

    /**
     * Paciente evaluado.
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_erp', 'cod_pacientes');
    }

    /**
     * Nutricionista que emitió el juicio clínico.
     */
    public function nutricionista()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_erp', 'cod_nutricionistas');
    }

    /**
     * Scope para filtrar únicamente etiquetas válidas para el dataset supervisado.
     */
    public function scopeValidas(Builder $query): Builder
    {
        return $query->where('estado_etiqueta_erp', self::ESTADO_VALIDA)
            ->whereNotNull('nivel_riesgo_erp');
    }

    /**
     * Scope para etiquetas de información insuficiente.
     */
    public function scopeInsuficientes(Builder $query): Builder
    {
        return $query->where('estado_etiqueta_erp', self::ESTADO_INSUFICIENTE);
    }
}
