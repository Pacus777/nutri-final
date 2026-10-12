<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatronAlimenticio extends Model
{
    use HasFactory;

    protected $table = 'patrones_alimenticios';
    protected $primaryKey = 'cod_patrones_alimenticios';

    protected $fillable = [
        'nom_pat',
        'des_pat',
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionPatronPaciente::class, 'cod_patrones_alimenticios_app', 'cod_patrones_alimenticios');
    }

    public function clustersModelo()
    {
        return $this->hasMany(ClusterModelo::class, 'cod_patrones_alimenticios_clm', 'cod_patrones_alimenticios');
    }
}
