<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $primaryKey = 'cod_pacientes';

    protected $fillable = [
        'cod_usuarios_pac',
        'fec_pac',
        'eda_pac',
        'sex_pac',
        'pes_pac',
        'tal_pac',
        'est_pac',
    ];

    /**
     * Relación: un paciente pertenece a un usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuarios_pac', 'cod_usuarios');
    }

    public function estaActivo(): bool
    {
        return $this->est_pac === 'activo';
    }

    public function estaInactivo(): bool
    {
        return $this->est_pac === 'inactivo';
    }

    public function asignacionesNutricionista()
    {
        return $this->hasMany(
            AsignacionNutricionistaPaciente::class,
            'cod_pacientes_anp',
            'cod_pacientes'
        );
    }

    public function asignacionActiva()
    {
        return $this->hasOne(
            AsignacionNutricionistaPaciente::class,
            'cod_pacientes_anp',
            'cod_pacientes'
        )->where('est_anp', 'activa');
    }
}