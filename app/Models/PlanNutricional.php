<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class PlanNutricional extends Model
{
    use HasFactory;

    protected $table = 'planes_nutricionales';

    protected $primaryKey = 'cod_planes_nutricionales';

    protected $fillable = [
        'cod_pacientes_pan',
        'cod_nutricionistas_pan',
        'fec_pan',
        'des_pan',
        'est_pan',
    ];

    protected function casts(): array
    {
        return [
            'fec_pan' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_pan', 'cod_pacientes');
    }

    public function nutricionista()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_pan', 'cod_nutricionistas');
    }

    public function estaVigente(): bool
    {
        return $this->est_pan === 'vigente';
    }

    public function estaCerrado(): bool
    {
        return $this->est_pan === 'cerrado';
    }

    public function estaReemplazado(): bool
    {
        return $this->est_pan === 'reemplazado';
    }

    public function estaActivo(): bool
    {
        return $this->est_pan === 'activo';
    }

    public function estaPausado(): bool
    {
        return $this->est_pan === 'pausado';
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereIn('est_pan', ['activo', 'vigente']);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('est_pan', 'vigente');
    }

    public function scopeCerrados(Builder $query): Builder
    {
        return $query->where('est_pan', 'cerrado');
    }

    public function cerrar(): bool
    {
        $this->est_pan = 'cerrado';
        return $this->save();
    }

    public function reemplazar(): bool
    {
        $this->est_pan = 'reemplazado';
        return $this->save();
    }

    public function marcarVigente(): bool
    {
        $this->est_pan = 'vigente';
        return $this->save();
    }

    public function pausar(): bool
    {
        $this->est_pan = 'pausado';
        return $this->save();
    }

    public function finalizar(): bool
    {
        $this->est_pan = 'finalizado';
        return $this->save();
    }
}