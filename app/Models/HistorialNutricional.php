<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialNutricional extends Model
{
    use HasFactory;

    protected $table = 'historial_nutricional';

    protected $primaryKey = 'cod_historial_nutricional';

    protected $fillable = [
        'cod_pacientes_hin',
        'fec_hin',
        'des_hin',
    ];

    protected function casts(): array
    {
        return [
            'fec_hin' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(
            Paciente::class,
            'cod_pacientes_hin',
            'cod_pacientes'
        );
    }

    public function descripcionVacia(): bool
    {
        return trim((string) $this->des_hin) === '';
    }
}
