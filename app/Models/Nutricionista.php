<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nutricionista extends Model
{
    use HasFactory;

    protected $table = 'nutricionistas';

    protected $primaryKey = 'cod_nutricionistas';

    protected $fillable = [
        'cod_usuarios_nut',
        'esp_nut',
        'est_nut',
    ];

    /**
     * Relación: un nutricionista pertenece a un usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuarios_nut', 'cod_usuarios');
    }

    /**
     * Verifica si el nutricionista está activo.
     */
    public function estaActivo(): bool
    {
        return $this->est_nut === 'activo';
    }

    /**
     * Verifica si el nutricionista está inactivo.
     */
    public function estaInactivo(): bool
    {
        return $this->est_nut === 'inactivo';
    }

    public function asignacionesPaciente()
    {
        return $this->hasMany(
            AsignacionNutricionistaPaciente::class,
            'cod_nutricionistas_anp',
            'cod_nutricionistas'
        );
    }
}