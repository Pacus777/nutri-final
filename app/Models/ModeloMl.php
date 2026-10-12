<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloMl extends Model
{
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_EN_ENTRENAMIENTO = 'en_entrenamiento';
    public const ESTADO_VALIDACION = 'validacion';
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_DEPRECADO = 'deprecado';

    public const ESTADOS = [
        self::ESTADO_BORRADOR,
        self::ESTADO_EN_ENTRENAMIENTO,
        self::ESTADO_VALIDACION,
        self::ESTADO_ACTIVO,
        self::ESTADO_DEPRECADO,
    ];

    protected $table = 'modelos_ml';
    protected $primaryKey = 'cod_modelos_ml';

    protected $fillable = [
        'nombre_mml',
        'tipo_algoritmo_mml',
        'tarea_mml',
        'version_mml',
        'version_features_mml',
        'ruta_artefacto_mml',
        'origen_dataset_mml',
        'hash_dataset_mml',
        'numero_muestras_mml',
        'numero_pacientes_mml',
        'fec_inicio_dataset_mml',
        'fec_fin_dataset_mml',
        'random_seed_mml',
        'parametros_mml',
        'metricas_rendimiento_mml',
        'features_requeridas_mml',
        'est_mml',
        'entrenado_en_mml',
    ];

    protected function casts(): array
    {
        return [
            'numero_muestras_mml' => 'integer',
            'numero_pacientes_mml' => 'integer',
            'random_seed_mml' => 'integer',
            'fec_inicio_dataset_mml' => 'date',
            'fec_fin_dataset_mml' => 'date',
            'parametros_mml' => 'array',
            'metricas_rendimiento_mml' => 'array',
            'features_requeridas_mml' => 'array',
            'entrenado_en_mml' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function predicciones()
    {
        return $this->hasMany(PrediccionMl::class, 'cod_modelos_ml_pml', 'cod_modelos_ml');
    }

    public function clusters()
    {
        return $this->hasMany(ClusterModelo::class, 'cod_modelos_ml_clm', 'cod_modelos_ml');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('est_mml', self::ESTADO_ACTIVO);
    }

    public function esActivo(): bool
    {
        return $this->est_mml === self::ESTADO_ACTIVO;
    }
}
