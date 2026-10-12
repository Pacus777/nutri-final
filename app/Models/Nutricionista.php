<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\EstadoPerfil;

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

    protected $casts = [
        'est_nut' => EstadoPerfil::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuarios_nut', 'cod_usuarios');
    }

    public function asignaciones()
    {
        return $this->hasMany(
            AsignacionNutricionistaPaciente::class,
            'cod_nutricionistas_anp',
            'cod_nutricionistas'
        );
    }

    public function pacientes()
    {
        return $this->belongsToMany(
            Paciente::class,
            'asignacion_nutricionista_paciente',
            'cod_nutricionistas_anp',
            'cod_pacientes_anp',
            'cod_nutricionistas',
            'cod_pacientes'
        )->withPivot(['cod_asignacion_nutricionista_paciente', 'fec_anp', 'est_anp'])
            ->withTimestamps();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('est_nut', EstadoPerfil::ACTIVO);
    }

    public function tienePacientesAsignados(): bool
    {
        return $this->asignaciones()->exists();
    }

    public function pacientesActivos()
    {
        return $this->belongsToMany(
            Paciente::class,
            'asignacion_nutricionista_paciente',
            'cod_nutricionistas_anp',
            'cod_pacientes_anp',
            'cod_nutricionistas',
            'cod_pacientes'
        )->wherePivot('est_anp', 'activa')
            ->where('pacientes.est_pac', EstadoPerfil::ACTIVO)
            ->withTimestamps();
    }

    public function pacienteActivoCount(): int
    {
        return $this->pacientesActivos()->count();
    }

    public function estaActivo(): bool
    {
        return $this->est_nut === EstadoPerfil::ACTIVO;
    }

    public function estaInactivo(): bool
    {
        return $this->est_nut === EstadoPerfil::INACTIVO;
    }

    public function evaluacionesIniciales()
    {
        return $this->hasMany(
            EvaluacionInicial::class,
            'cod_nutricionistas_evi',
            'cod_nutricionistas'
        );
    }

    public function objetivosNutricionales()
    {
        return $this->hasMany(
            ObjetivoNutricional::class,
            'cod_nutricionistas_obe',
            'cod_nutricionistas'
        );
    }

    public function ajustesObjetivosNutricionales()
    {
        return $this->hasMany(
            AjusteObjetivoNutricional::class,
            'cod_nutricionistas_ajn',
            'cod_nutricionistas'
        );
    }

    public function planesNutricionales()
    {
        return $this->hasMany(
            PlanNutricional::class,
            'cod_nutricionistas_pan',
            'cod_nutricionistas'
        );
    }

    public function validacionesInformacion()
    {
        return $this->hasMany(
            ValidacionInformacion::class,
            'cod_nutricionistas_vin',
            'cod_nutricionistas'
        );
    }

    public function seguimientosNutricionales()
    {
        return $this->hasMany(
            SeguimientoNutricional::class,
            'cod_nutricionistas_seg',
            'cod_nutricionistas'
        );
    }

    public function prediccionesValidadas()
    {
        return $this->hasMany(
            PrediccionMl::class,
            'cod_nutricionistas_validador_pml',
            'cod_nutricionistas'
        );
    }

    public function clustersValidados()
    {
        return $this->hasMany(
            ClusterModelo::class,
            'cod_nutricionistas_validador_clm',
            'cod_nutricionistas'
        );
    }

    public function riesgosValidados()
    {
        return $this->hasMany(
            RiesgoNutricional::class,
            'cod_nutricionistas_rnu',
            'cod_nutricionistas'
        );
    }

    public function etiquetasRiesgoProfesional()
    {
        return $this->hasMany(
            EtiquetaRiesgoProfesional::class,
            'cod_nutricionistas_erp',
            'cod_nutricionistas'
        );
    }

    public function asignacionActivaConPaciente(Paciente|int $paciente)
    {
        $pacienteId = $paciente instanceof Paciente
            ? $paciente->getKey()
            : $paciente;

        return $this->asignaciones()
            ->where('cod_pacientes_anp', $pacienteId)
            ->where('est_anp', 'activa');
    }

    public function tienePacienteAsignadoActivo(Paciente|int $paciente): bool
    {
        return $this->asignacionActivaConPaciente($paciente)->exists();
    }
}