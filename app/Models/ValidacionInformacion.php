<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidacionInformacion extends Model
{
    use HasFactory;

    public const ESTADO_ACEPTADO = 'aceptado';
    public const ESTADO_OBSERVADO = 'observado';
    public const ESTADO_RECHAZADO = 'rechazado';

    public const ESTADOS = [
        self::ESTADO_ACEPTADO,
        self::ESTADO_OBSERVADO,
        self::ESTADO_RECHAZADO,
    ];

    public const ESTADOS_CON_OBSERVACION_OBLIGATORIA = [
        self::ESTADO_OBSERVADO,
        self::ESTADO_RECHAZADO,
    ];

    protected $table = 'validaciones_informacion';

    protected $primaryKey = 'cod_validaciones_informacion';

    protected $fillable = [
        'cod_registros_alimenticios_vin',
        'cod_nutricionistas_vin',
        'fec_vin',
        'est_vin',
        'obs_vin',
    ];

    protected $casts = [
        'fec_vin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function registroAlimenticio()
    {
        return $this->belongsTo(
            RegistroAlimenticio::class,
            'cod_registros_alimenticios_vin',
            'cod_registros_alimenticios'
        );
    }

    public function nutricionista()
    {
        return $this->belongsTo(
            Nutricionista::class,
            'cod_nutricionistas_vin',
            'cod_nutricionistas'
        );
    }

    public function scopeAceptadas(Builder $query): Builder
    {
        return $query->where('est_vin', self::ESTADO_ACEPTADO);
    }

    public function scopeObservadas(Builder $query): Builder
    {
        return $query->where('est_vin', self::ESTADO_OBSERVADO);
    }

    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where('est_vin', self::ESTADO_RECHAZADO);
    }

    public function scopeConObservacion(Builder $query): Builder
    {
        return $query->whereIn('est_vin', [
            self::ESTADO_OBSERVADO,
            self::ESTADO_RECHAZADO,
        ])->whereNotNull('obs_vin');
    }

    public function estaAceptada(): bool
    {
        return $this->est_vin === self::ESTADO_ACEPTADO;
    }

    public function estaObservada(): bool
    {
        return $this->est_vin === self::ESTADO_OBSERVADO;
    }
    public function estaRechazada(): bool
    {
        return $this->est_vin === self::ESTADO_RECHAZADO;
    }

    public function requiereObservacion(): bool
    {
        return in_array($this->est_vin, self::ESTADOS_CON_OBSERVACION_OBLIGATORIA, true);
    }
}