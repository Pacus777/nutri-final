<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClusterModelo extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_VALIDADO = 'validado';
    public const ESTADO_AJUSTADO = 'ajustado';

    public const ESTADOS_VALIDACION = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_VALIDADO,
        self::ESTADO_AJUSTADO,
    ];

    protected $table = 'clusters_modelo';
    protected $primaryKey = 'cod_clusters_modelo';

    protected $fillable = [
        'cod_modelos_ml_clm',
        'numero_cluster_clm',
        'cod_patrones_alimenticios_clm',
        'centroide_json_clm',
        'descripcion_automatica_clm',
        'descripcion_profesional_clm',
        'estado_validacion_clm',
        'cod_nutricionistas_validador_clm',
        'fec_validacion_clm',
    ];

    protected function casts(): array
    {
        return [
            'numero_cluster_clm' => 'integer',
            'centroide_json_clm' => 'array',
            'fec_validacion_clm' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function modeloMl()
    {
        return $this->belongsTo(ModeloMl::class, 'cod_modelos_ml_clm', 'cod_modelos_ml');
    }

    public function patronAlimenticio()
    {
        return $this->belongsTo(PatronAlimenticio::class, 'cod_patrones_alimenticios_clm', 'cod_patrones_alimenticios');
    }

    public function nutricionistaValidador()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_validador_clm', 'cod_nutricionistas');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_validacion_clm', self::ESTADO_PENDIENTE);
    }
}
