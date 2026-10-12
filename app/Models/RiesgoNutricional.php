<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiesgoNutricional extends Model
{
    use HasFactory;

    public const ORIGEN_LEGACY = 'legacy';
    public const ORIGEN_HEURISTICO = 'heuristico';
    public const ORIGEN_ALGORITMO_ML = 'algoritmo_ml';
    public const ORIGEN_NUTRICIONISTA = 'nutricionista';

    public const ORIGENES = [
        self::ORIGEN_LEGACY,
        self::ORIGEN_HEURISTICO,
        self::ORIGEN_ALGORITMO_ML,
        self::ORIGEN_NUTRICIONISTA,
    ];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_AJUSTADO = 'ajustado';
    public const ESTADO_DESCARTADO = 'descartado';

    public const ESTADOS_VALIDACION = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_AJUSTADO,
        self::ESTADO_DESCARTADO,
    ];

    public const NIVEL_BAJO = 'bajo';
    public const NIVEL_MODERADO = 'moderado';
    public const NIVEL_ALTO = 'alto';

    public const NIVELES = [
        self::NIVEL_BAJO,
        self::NIVEL_MODERADO,
        self::NIVEL_ALTO,
    ];

    protected $table = 'riesgos_nutricionales';
    protected $primaryKey = 'cod_riesgos_nutricionales';

    protected $fillable = [
        'cod_pacientes_rnu',
        'fec_rnu',
        'tip_rnu',
        'niv_rnu',
        'des_rnu',
        'origen_rnu',
        'cod_predicciones_ml_rnu',
        'confianza_rnu',
        'estado_validacion_rnu',
        'cod_nutricionistas_rnu',
        'fec_validacion_rnu',
        'obs_profesional_rnu',
    ];

    protected function casts(): array
    {
        return [
            'fec_rnu' => 'date',
            'confianza_rnu' => 'decimal:4',
            'fec_validacion_rnu' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_rnu', 'cod_pacientes');
    }

    public function prediccionMl()
    {
        return $this->belongsTo(PrediccionMl::class, 'cod_predicciones_ml_rnu', 'cod_predicciones_ml');
    }

    public function nutricionistaValidador()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_rnu', 'cod_nutricionistas');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_validacion_rnu', self::ESTADO_PENDIENTE);
    }

    public function scopeConfirmados(Builder $query): Builder
    {
        return $query->where('estado_validacion_rnu', self::ESTADO_CONFIRMADO);
    }
}
