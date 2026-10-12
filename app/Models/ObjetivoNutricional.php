<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjetivoNutricional extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_CUMPLIDO = 'cumplido';
    public const ESTADO_PAUSADO = 'pausado';
    public const ESTADO_CANCELADO = 'cancelado';
    public const ESTADO_CERRADO = 'cerrado';

    public const ESTADOS = [
        self::ESTADO_ACTIVO,
        self::ESTADO_CUMPLIDO,
        self::ESTADO_PAUSADO,
        self::ESTADO_CANCELADO,
        self::ESTADO_CERRADO,
    ];

    public const ESTADOS_EDITABLES = [
        self::ESTADO_ACTIVO,
        self::ESTADO_PAUSADO,
    ];

    public const ESTADOS_TERMINALES = [
        self::ESTADO_CUMPLIDO,
        self::ESTADO_CANCELADO,
        self::ESTADO_CERRADO,
    ];

    protected $table = 'objetivos_nutricionales';

    protected $primaryKey = 'cod_objetivos_nutricionales';

    protected $fillable = [
        'cod_pacientes_obe',
        'cod_nutricionistas_obe',
        'des_obe',
        'progreso_actual',
        'meta_progreso',
        'unidad_progreso',
        'fec_obe',
        'est_obe',
    ];

    protected function casts(): array
    {
        return [
            'fec_obe' => 'date',
            'progreso_actual' => 'integer',
            'meta_progreso' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_obe', 'cod_pacientes');
    }

    public function nutricionista()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_obe', 'cod_nutricionistas');
    }

    public function ajustes()
    {
        return $this->hasMany(
            AjusteObjetivoNutricional::class,
            'cod_objetivos_nutricionales_ajn',
            'cod_objetivos_nutricionales'
        );
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('est_obe', self::ESTADO_ACTIVO);
    }

    public function scopeCerrados(Builder $query): Builder
    {
        return $query->where('est_obe', self::ESTADO_CERRADO);
    }

    public function scopeCumplidos(Builder $query): Builder
    {
        return $query->where('est_obe', self::ESTADO_CUMPLIDO);
    }

    public function scopePausados(Builder $query): Builder
    {
        return $query->where('est_obe', self::ESTADO_PAUSADO);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereIn('est_obe', [
            self::ESTADO_ACTIVO,
            self::ESTADO_PAUSADO,
        ]);
    }

    public function scopeTerminales(Builder $query): Builder
    {
        return $query->whereIn('est_obe', self::ESTADOS_TERMINALES);
    }

    public function estaActivo(): bool
    {
        return $this->est_obe === self::ESTADO_ACTIVO;
    }

    public function estaCerrado(): bool
    {
        return $this->est_obe === self::ESTADO_CERRADO;
    }

    public function estaCancelado(): bool
    {
        return $this->est_obe === self::ESTADO_CANCELADO;
    }

    public function estaCumplido(): bool
    {
        return $this->est_obe === self::ESTADO_CUMPLIDO;
    }

    public function estaPausado(): bool
    {
        return $this->est_obe === self::ESTADO_PAUSADO;
    }

    public function esTerminal(): bool
    {
        return in_array($this->est_obe, self::ESTADOS_TERMINALES, true);
    }

    public function puedeEditarse(): bool
    {
        return in_array($this->est_obe, self::ESTADOS_EDITABLES, true);
    }

    public function puedeCambiarEstado(): bool
    {
        return ! $this->esTerminal();
    }

    public function cerrar(): bool
    {
        if (! $this->puedeCambiarEstado()) {
            return false;
        }

        return $this->update([
            'est_obe' => self::ESTADO_CERRADO,
        ]);
    }

    public function marcarCumplido(): bool
    {
        if (! $this->puedeCambiarEstado()) {
            return false;
        }

        return $this->update([
            'est_obe' => self::ESTADO_CUMPLIDO,
        ]);
    }

    public function pausar(): bool
    {
        if (! $this->estaActivo()) {
            return false;
        }

        return $this->update([
            'est_obe' => self::ESTADO_PAUSADO,
        ]);
    }

    public function reactivar(): bool
    {
        if (! $this->estaPausado()) {
            return false;
        }

        return $this->update([
            'est_obe' => self::ESTADO_ACTIVO,
        ]);
    }

    public function cancelar(): bool
    {
        if (! $this->puedeCambiarEstado()) {
            return false;
        }

        return $this->update([
            'est_obe' => self::ESTADO_CANCELADO,
        ]);
    }

    public function calcularPorcentajeProgreso(): int
    {
        $meta = (int) ($this->meta_progreso ?: 7);
        if ($meta <= 0) {
            return 0;
        }

        $actual = (int) ($this->progreso_actual ?: 0);
        $porcentaje = ($actual / $meta) * 100;

        return (int) min(round($porcentaje), 100);
    }

    public function incrementarProgreso(int $delta = 1): bool
    {
        $meta = (int) ($this->meta_progreso ?: 7);
        $actual = max(0, ((int) ($this->progreso_actual ?: 0)) + $delta);

        return $this->update([
            'progreso_actual' => $actual,
        ]);
    }
}