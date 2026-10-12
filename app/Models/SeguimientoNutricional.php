<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoNutricional extends Model
{
    use HasFactory;

    protected $table = 'seguimientos_nutricionales';

    protected $primaryKey = 'cod_seguimientos_nutricionales';

    protected $fillable = [
        'cod_pacientes_seg',
        'cod_nutricionistas_seg',
        'fec_seg',
        'res_seg',
        'cam_seg',
    ];

    protected function casts(): array
    {
        return [
            'fec_seg' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(
            Paciente::class,
            'cod_pacientes_seg',
            'cod_pacientes'
        );
    }

    public function nutricionista()
    {
        return $this->belongsTo(
            Nutricionista::class,
            'cod_nutricionistas_seg',
            'cod_nutricionistas'
        );
    }

    public function tieneCambioSignificativo(): bool
    {
        return trim((string) $this->cam_seg) !== '';
    }
}
