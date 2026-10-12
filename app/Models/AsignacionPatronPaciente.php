<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionPatronPaciente extends Model
{
    use HasFactory;

    protected $table = 'asignacion_patron_paciente';
    protected $primaryKey = 'cod_asignacion_patron_paciente';

    protected $fillable = [
        'cod_pacientes_app',
        'cod_patrones_alimenticios_app',
        'fec_app',
        'cod_predicciones_ml_app',
    ];

    protected function casts(): array
    {
        return [
            'fec_app' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'cod_pacientes_app', 'cod_pacientes');
    }

    public function patronAlimenticio()
    {
        return $this->belongsTo(PatronAlimenticio::class, 'cod_patrones_alimenticios_app', 'cod_patrones_alimenticios');
    }

    public function prediccionMl()
    {
        return $this->belongsTo(PrediccionMl::class, 'cod_predicciones_ml_app', 'cod_predicciones_ml');
    }
}
