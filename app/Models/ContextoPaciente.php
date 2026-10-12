<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContextoPaciente extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    public const ESTADOS = [
        self::ESTADO_ACTIVO,
        self::ESTADO_INACTIVO,
    ];

    public const PRIORIDAD_BAJA = 'baja';
    public const PRIORIDAD_MEDIA = 'media';
    public const PRIORIDAD_ALTA = 'alta';

    public const PRIORIDADES = [
        self::PRIORIDAD_BAJA,
        self::PRIORIDAD_MEDIA,
        self::PRIORIDAD_ALTA,
    ];

    public const TIPO_PATOLOGIA = 'Patología';
    public const TIPO_ALERGIA = 'Alergia';
    public const TIPO_MEDICAMENTO = 'Medicamento';
    public const TIPO_ANTECEDENTE_FAMILIAR = 'Antecedente familiar';
    public const TIPO_HABITOS_ALIMENTARIOS = 'Hábitos alimentarios';
    public const TIPO_ACTIVIDAD_FISICA = 'Actividad física';
    public const TIPO_RESTRICCION_ALIMENTARIA = 'Restricción alimentaria';
    public const TIPO_PREFERENCIA_ALIMENTARIA = 'Preferencia alimentaria';
    public const TIPO_FACTOR_SOCIAL = 'Factor económico/social';
    public const TIPO_OBSERVACION_CLINICA = 'Observación clínica';
    public const TIPO_OTRO = 'Otro';

    public const TIPOS = [
        self::TIPO_PATOLOGIA,
        self::TIPO_ALERGIA,
        self::TIPO_MEDICAMENTO,
        self::TIPO_ANTECEDENTE_FAMILIAR,
        self::TIPO_HABITOS_ALIMENTARIOS,
        self::TIPO_ACTIVIDAD_FISICA,
        self::TIPO_RESTRICCION_ALIMENTARIA,
        self::TIPO_PREFERENCIA_ALIMENTARIA,
        self::TIPO_FACTOR_SOCIAL,
        self::TIPO_OBSERVACION_CLINICA,
        self::TIPO_OTRO,
    ];

    protected $table = 'contextos_paciente';

    protected $primaryKey = 'cod_contextos_paciente';

    protected $fillable = [
        'cod_pacientes_cxp',
        'des_cxp',
        'fec_cxp',
        'tip_cxp',
        'pri_cxp',
        'est_cxp',
    ];

    protected function casts(): array
    {
        return [
            'fec_cxp' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_cxp', 'cod_pacientes');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('est_cxp', self::ESTADO_ACTIVO);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('est_cxp', self::ESTADO_INACTIVO);
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fec_cxp')
            ->orderByDesc('cod_contextos_paciente');
    }

    public function scopeAltaPrioridad(Builder $query): Builder
    {
        return $query->where('pri_cxp', self::PRIORIDAD_ALTA);
    }

    public function estaActivo(): bool
    {
        return $this->est_cxp === self::ESTADO_ACTIVO;
    }

    public function estaInactivo(): bool
    {
        return $this->est_cxp === self::ESTADO_INACTIVO;
    }

    public function puedeEditarse(): bool
    {
        return $this->estaActivo();
    }

    public function esPrioridadAlta(): bool
    {
        return $this->pri_cxp === self::PRIORIDAD_ALTA;
    }

    public function esPrioridadMedia(): bool
    {
        return $this->pri_cxp === self::PRIORIDAD_MEDIA;
    }

    public function esPrioridadBaja(): bool
    {
        return $this->pri_cxp === self::PRIORIDAD_BAJA;
    }

    public function esClinicamenteCritico(): bool
    {
        return $this->estaActivo()
            && $this->esPrioridadAlta();
    }

    public function activar(): bool
    {
        return $this->update([
            'est_cxp' => self::ESTADO_ACTIVO,
        ]);
    }

    public function inactivar(): bool
    {
        return $this->update([
            'est_cxp' => self::ESTADO_INACTIVO,
        ]);
    }
}