<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AsignacionNutricionistaPaciente extends Model
{
    use HasFactory;

    protected $table = 'asignacion_nutricionista_paciente';

    protected $primaryKey = 'cod_asignacion_nutricionista_paciente';

    protected $fillable = [
        'cod_nutricionistas_anp',
        'cod_pacientes_anp',
        'fec_anp',
        'est_anp',
    ];

    /**
     * Relación: la asignación pertenece a un nutricionista.
     */
    public function nutricionista()
    {
        return $this->belongsTo(Nutricionista::class, 'cod_nutricionistas_anp', 'cod_nutricionistas');
    }

    /**
     * Relación: la asignación pertenece a un paciente.
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_anp', 'cod_pacientes');
    }

    public function estaActiva(): bool
    {
        return $this->est_anp === 'activa';
    }

    public function estaCerrada(): bool
    {
        return $this->est_anp === 'cerrada';
    }

    public function estaReasignada(): bool
    {
        return $this->est_anp === 'reasignada';
    }
}