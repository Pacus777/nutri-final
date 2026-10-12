<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInicial extends Model
{
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_CERRADA = 'cerrada';

    public const ESTADOS = [
        self::ESTADO_BORRADOR,
        self::ESTADO_PENDIENTE_REVISION,
        self::ESTADO_ACTIVA,
        self::ESTADO_CERRADA,
    ];

    public const ESTADOS_EDITABLES = [
        self::ESTADO_BORRADOR,
        self::ESTADO_PENDIENTE_REVISION,
        self::ESTADO_ACTIVA,
    ];

    protected $table = 'evaluaciones_iniciales';

    protected $primaryKey = 'cod_evaluaciones_iniciales';

    protected $fillable = [
        'cod_pacientes_evi',
        'cod_nutricionistas_evi',
        'fec_evi',
        'est_evi',
        'peso_evi',
        'estatura_evi',
        'imc_evi',
        'objetivo_inicial_evi',
        'restricciones_evi',
        'contexto_general_evi',
        'obs_evi',
    ];

    protected function casts(): array
    {
        return [
            'fec_evi' => 'date',
            'peso_evi' => 'decimal:2',
            'estatura_evi' => 'decimal:2',
            'imc_evi' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_evi', 'cod_pacientes');
    }

    public function nutricionista()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_evi', 'cod_nutricionistas');
    }

    public function scopeBorradores(Builder $query): Builder
    {
        return $query->where('est_evi', self::ESTADO_BORRADOR);
    }

    public function scopePendientesRevision(Builder $query): Builder
    {
        return $query->where('est_evi', self::ESTADO_PENDIENTE_REVISION);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('est_evi', self::ESTADO_ACTIVA);
    }

    public function scopeCerradas(Builder $query): Builder
    {
        return $query->where('est_evi', self::ESTADO_CERRADA);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereIn('est_evi', [
            self::ESTADO_BORRADOR,
            self::ESTADO_PENDIENTE_REVISION,
            self::ESTADO_ACTIVA,
        ]);
    }

    public function estaEnBorrador(): bool
    {
        return $this->est_evi === self::ESTADO_BORRADOR;
    }

    public function estaPendienteRevision(): bool
    {
        return $this->est_evi === self::ESTADO_PENDIENTE_REVISION;
    }

    public function estaActiva(): bool
    {
        return $this->est_evi === self::ESTADO_ACTIVA;
    }

    public function estaCerrada(): bool
    {
        return $this->est_evi === self::ESTADO_CERRADA;
    }

    public function puedeEditarse(): bool
    {
        return in_array($this->est_evi, self::ESTADOS_EDITABLES, true);
    }

    public function calcularImc(): ?float
    {
        if (! $this->peso_evi || ! $this->estatura_evi) {
            return null;
        }

        $peso = (float) $this->peso_evi;
        $estatura = (float) $this->estatura_evi;

        if ($peso <= 0 || $estatura <= 0) {
            return null;
        }

        /*
         * La base debe guardar estatura en metros.
         * Pero este control permite aceptar valores como 170 y convertirlos a 1.70.
         */
        if ($estatura > 3) {
            $estatura = $estatura / 100;
        }

        if ($estatura <= 0) {
            return null;
        }

        return round($peso / ($estatura * $estatura), 2);
    }

    public function recalcularImc(): void
    {
        $this->imc_evi = $this->calcularImc();
    }

    public function marcarBorrador(): bool
    {
        return $this->update([
            'est_evi' => self::ESTADO_BORRADOR,
        ]);
    }

    public function marcarPendienteRevision(): bool
    {
        return $this->update([
            'est_evi' => self::ESTADO_PENDIENTE_REVISION,
        ]);
    }

    public function marcarActiva(): bool
    {
        return $this->update([
            'est_evi' => self::ESTADO_ACTIVA,
        ]);
    }

    public function cerrar(): bool
    {
        return $this->update([
            'est_evi' => self::ESTADO_CERRADA,
        ]);
    }
}