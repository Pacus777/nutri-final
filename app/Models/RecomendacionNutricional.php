<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecomendacionNutricional extends Model
{
    use HasFactory;

    protected $table = 'recomendaciones_nutricionales';
    protected $primaryKey = 'cod_recomendaciones_nutricionales';

    public const ORIGEN_ASISTIDA_IA = 'asistida_ia';
    public const ORIGEN_MANUAL = 'manual';
    public const ORIGEN_AJUSTE_PROFESIONAL = 'ajuste_profesional';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA = 'aprobada';
    public const ESTADO_EDITADA = 'editada';
    public const ESTADO_DESCARTADA = 'descartada';

    protected $fillable = [
        'cod_pacientes_rec',
        'cod_nutricionistas_rec',
        'cod_features_nutricionales_rec',
        'cod_predicciones_ml_rec',
        'fec_rec',
        'des_rec',
        'tip_rec',
        'origen_rec',
        'estado_rec',
        'categoria_rec',
        'justificacion_rec',
        'fec_aprobacion_rec',
    ];

    protected $casts = [
        'fec_rec' => 'date',
        'fec_aprobacion_rec' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_rec', 'cod_pacientes');
    }

    public function nutricionista(): BelongsTo
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_rec', 'cod_nutricionistas');
    }

    public function featureNutricional(): BelongsTo
    {
        return $this->belongsTo(FeatureNutricional::class, 'cod_features_nutricionales_rec', 'cod_features_nutricionales');
    }

    public function prediccionMl(): BelongsTo
    {
        return $this->belongsTo(PrediccionMl::class, 'cod_predicciones_ml_rec', 'cod_predicciones_ml');
    }

    public function estaPendiente(): bool
    {
        return $this->estado_rec === self::ESTADO_PENDIENTE;
    }

    public function estaAprobada(): bool
    {
        return in_array($this->estado_rec, [self::ESTADO_APROBADA, self::ESTADO_EDITADA], true);
    }
}
