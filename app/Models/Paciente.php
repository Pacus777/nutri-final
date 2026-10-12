<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\EstadoPerfil;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';
    protected $primaryKey = 'cod_pacientes';
    protected $fillable = [
        'cod_usuarios_pac',
        'sex_pac',
        'pes_pac',
        'estatura_pac',
        'tip_san_pac',
        'ale_pac',
        'est_pac',
    ];

    protected $casts = [
        'pes_pac' => 'decimal:2',
        'estatura_pac' => 'decimal:2',
        'est_pac' => EstadoPerfil::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuarios_pac', 'cod_usuarios');
    }

    public function asignaciones()
    {
        return $this->hasMany(
            AsignacionNutricionistaPaciente::class,
            'cod_pacientes_anp',
            'cod_pacientes'
        );
    }

    public function nutricionistaActual()
    {
        return $this->hasOne(
            AsignacionNutricionistaPaciente::class,
            'cod_pacientes_anp',
            'cod_pacientes'
        )->where('est_anp', 'activa');
    }

    public function asignacionActiva()
    {
        return $this->nutricionistaActual();
    }

    public function asignacionesNutricionista()
    {
        return $this->asignaciones();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('est_pac', EstadoPerfil::ACTIVO);
    }

    public function tieneAsignacionActiva(): bool
    {
        return $this->asignacionActiva()->exists();
    }

    public function tieneAsignacionActivaCon($nutricionistaId): bool
    {
        return $this->asignaciones()
            ->where('cod_nutricionistas_anp', $nutricionistaId)
            ->where('est_anp', 'activa')
            ->exists();
    }

    public function getEdadAttribute(): ?int
    {
        $fechaNacimiento = $this->usuario?->fec_nac_usu;

        if (! $fechaNacimiento) {
            return null;
        }

        return Carbon::parse($fechaNacimiento)->age;
    }

    /**
     * Accesor para normalizar el sexo del paciente de forma tipada mediante el Enum SexoPaciente.
     */
    public function getSexPacAttribute($value): ?\App\Enums\SexoPaciente
    {
        return \App\Enums\SexoPaciente::normalizar($value);
    }

    /**
     * Mutador para normalizar el sexo del paciente antes de guardarlo en la base de datos.
     */
    public function setSexPacAttribute($value): void
    {
        if ($value instanceof \App\Enums\SexoPaciente) {
            $this->attributes['sex_pac'] = $value->value;
        } else {
            $normalized = \App\Enums\SexoPaciente::normalizar($value);
            $this->attributes['sex_pac'] = $normalized ? $normalized->value : null;
        }
    }

    public function evaluacionesIniciales()
    {
        return $this->hasMany(
            EvaluacionInicial::class,
            'cod_pacientes_evi',
            'cod_pacientes'
        );
    }

    public function evaluacionInicialActiva()
    {
        return $this->hasOne(
            EvaluacionInicial::class,
            'cod_pacientes_evi',
            'cod_pacientes'
        )->where('est_evi', 'activa');
    }

    public function contextosPaciente()
    {
        return $this->hasMany(
            ContextoPaciente::class,
            'cod_pacientes_cxp',
            'cod_pacientes'
        );
    }

    public function objetivosNutricionales()
    {
        return $this->hasMany(
            ObjetivoNutricional::class,
            'cod_pacientes_obe',
            'cod_pacientes'
        );
    }

    public function objetivosActivos()
    {
        return $this->objetivosNutricionales()->where('est_obe', 'activo');
    }

    public function planesNutricionales()
    {
        return $this->hasMany(
            PlanNutricional::class,
            'cod_pacientes_pan',
            'cod_pacientes'
        );
    }

    public function planNutricionalVigente()
    {
        return $this->hasOne(
            PlanNutricional::class,
            'cod_pacientes_pan',
            'cod_pacientes'
        )->whereIn('est_pan', ['vigente', 'activo']);
    }

    public function registrosAlimenticios()
    {
        return $this->hasMany(
            RegistroAlimenticio::class,
            'cod_pacientes_ral',
            'cod_pacientes'
        );
    }

    public function registroAlimenticioPorFecha(string $fecha)
    {
        return $this->hasOne(
            RegistroAlimenticio::class,
            'cod_pacientes_ral',
            'cod_pacientes'
        )->whereDate('fec_ral', $fecha);
    }

    public function seguimientosNutricionales()
    {
        return $this->hasMany(
            SeguimientoNutricional::class,
            'cod_pacientes_seg',
            'cod_pacientes'
        );
    }

    public function historialNutricional()
    {
        return $this->hasMany(
            HistorialNutricional::class,
            'cod_pacientes_hin',
            'cod_pacientes'
        );
    }

    public function featuresNutricionales()
    {
        return $this->hasMany(
            FeatureNutricional::class,
            'cod_pacientes_fnu',
            'cod_pacientes'
        );
    }

    public function prediccionesMl()
    {
        return $this->hasMany(
            PrediccionMl::class,
            'cod_pacientes_pml',
            'cod_pacientes'
        );
    }

    public function riesgosNutricionales()
    {
        return $this->hasMany(
            RiesgoNutricional::class,
            'cod_pacientes_rnu',
            'cod_pacientes'
        );
    }

    public function asignacionesPatron()
    {
        return $this->hasMany(
            AsignacionPatronPaciente::class,
            'cod_pacientes_app',
            'cod_pacientes'
        );
    }

    public function etiquetasRiesgoProfesional()
    {
        return $this->hasMany(
            EtiquetaRiesgoProfesional::class,
            'cod_pacientes_erp',
            'cod_pacientes'
        );
    }

    public function estaActivo(): bool
    {
        return $this->est_pac === EstadoPerfil::ACTIVO;
    }

    public function estaInactivo(): bool
    {
        return $this->est_pac === EstadoPerfil::INACTIVO;
    }

    public function getHistorialPeso(): array
    {
        $evaluaciones = $this->evaluacionesIniciales()
            ->whereNotNull('peso_evi')
            ->orderBy('fec_evi', 'asc')
            ->get();

        $historial = [];
        foreach ($evaluaciones as $evi) {
            $historial[] = [
                'fecha' => optional($evi->fec_evi)->format('Y-m-d'),
                'peso' => (float) $evi->peso_evi,
                'imc' => $evi->imc_evi ? (float) $evi->imc_evi : null,
                'origen' => 'Evaluación',
            ];
        }

        // Si el peso actual del paciente existe, lo agregamos como el punto final si difiere de la última evaluación
        if ($this->pes_pac) {
            $latest = end($historial);
            $pesoActual = (float) $this->pes_pac;
            
            if (!$latest || $latest['peso'] !== $pesoActual) {
                $historial[] = [
                    'fecha' => optional($this->updated_at)->format('Y-m-d') ?? today()->format('Y-m-d'),
                    'peso' => $pesoActual,
                    'imc' => $this->estatura_pac && $this->estatura_pac > 0 
                        ? round($pesoActual / ((float)$this->estatura_pac * (float)$this->estatura_pac), 2) 
                        : null,
                    'origen' => 'Actual',
                ];
            }
        }

        return $historial;
    }

    public function calcularEstadoAdherencia(): array
    {
        $fechaLimite = now()->subDays(7)->startOfDay();
        $registros = $this->registrosAlimenticios()
            ->with('ultimaValidacionInformacion')
            ->whereDate('fec_ral', '>=', $fechaLimite->toDateString())
            ->get();

        $totalRegistros = $registros->count();
        $ultimoRegistro = $this->registrosAlimenticios()->latest('fec_ral')->first();

        if (!$ultimoRegistro) {
            return [
                'estado' => 'rojo',
                'label' => 'Inactivo (Sin registros)',
                'color' => 'danger',
                'dias_activos' => 0,
            ];
        }

        $diasUltimoRegistro = now()->diffInDays(Carbon::parse($ultimoRegistro->fec_ral)->startOfDay());
        if ($diasUltimoRegistro >= 3) {
            return [
                'estado' => 'rojo',
                'label' => 'Inactivo (' . $diasUltimoRegistro . ' días sin registrar)',
                'color' => 'danger',
                'dias_activos' => $totalRegistros,
            ];
        }

        $tieneObservadoRechazado = $registros->contains(function ($reg) {
            $val = $reg->ultimaValidacionInformacion;
            return $val && in_array($val->est_vin, ['observado', 'rechazado']);
        });

        if ($totalRegistros < 4 || $tieneObservadoRechazado) {
            $motivo = $tieneObservadoRechazado ? 'Con observaciones' : 'Baja frecuencia';
            return [
                'estado' => 'amarillo',
                'label' => 'Irregular (' . $motivo . ')',
                'color' => 'warning',
                'dias_activos' => $totalRegistros,
            ];
        }

        return [
            'estado' => 'verde',
            'label' => 'Excelente adherencia',
            'color' => 'success',
            'dias_activos' => $totalRegistros,
        ];
    }
}