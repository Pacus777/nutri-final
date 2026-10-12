<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroAlimenticio extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_CERRADO = 'cerrado';

    public const ESTADOS = [
        self::ESTADO_ACTIVO,
        self::ESTADO_CERRADO,
    ];

    protected $table = 'registros_alimenticios';

    protected $primaryKey = 'cod_registros_alimenticios';

    protected $fillable = [
        'cod_pacientes_ral',
        'fec_ral',
        'est_ral',
    ];

    protected function casts(): array
    {
        return [
            'fec_ral' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(
            Paciente::class,
            'cod_pacientes_ral',
            'cod_pacientes'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            DetalleRegistroAlimenticio::class,
            'cod_registros_alimenticios_dra',
            'cod_registros_alimenticios'
        );
    }

    public function validacionesInformacion()
    {
        return $this->hasMany(
            ValidacionInformacion::class,
            'cod_registros_alimenticios_vin',
            'cod_registros_alimenticios'
        );
    }

    public function ultimaValidacionInformacion()
    {
        return $this->hasOne(
            ValidacionInformacion::class,
            'cod_registros_alimenticios_vin',
            'cod_registros_alimenticios'
        )->latestOfMany('cod_validaciones_informacion');
    }

    public function composicionNutricionalRegistrada()
    {
        return $this->hasOne(
            ComposicionNutricionalRegistrada::class,
            'cod_registros_alimenticios_cnr',
            'cod_registros_alimenticios'
        );
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('est_ral', self::ESTADO_ACTIVO);
    }

    public function scopeCerrados(Builder $query): Builder
    {
        return $query->where('est_ral', self::ESTADO_CERRADO);
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fec_ral')
            ->orderByDesc('cod_registros_alimenticios');
    }

    public function estaActivo(): bool
    {
        return $this->est_ral === self::ESTADO_ACTIVO;
    }

    public function estaCerrado(): bool
    {
        return $this->est_ral === self::ESTADO_CERRADO;
    }

    public function enviadoARevision(): bool
    {
        return $this->est_ral === self::ESTADO_CERRADO;
    }

    public function puedeSerEditadoPorPaciente(): bool
    {
        return $this->estaActivo()
            && ! $this->ultimaValidacionInformacion()->exists();
    }

    public function puedeEditarse(): bool
    {
        return $this->estaActivo();
    }

    public function cerrar(): bool
    {
        return $this->update([
            'est_ral' => self::ESTADO_CERRADO,
        ]);
    }

    public function activar(): bool
    {
        return $this->update([
            'est_ral' => self::ESTADO_ACTIVO,
        ]);
    }
}