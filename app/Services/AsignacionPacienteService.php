<?php

namespace App\Services;

use App\Models\AsignacionNutricionistaPaciente;
use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionPacienteService
{
    public function asignarPaciente(int $pacienteId, int $nutricionistaId, string $fecha, ?string $observacion = null): AsignacionNutricionistaPaciente
    {
        return DB::transaction(function () use ($pacienteId, $nutricionistaId, $fecha) {
            $paciente = Paciente::query()->with(['usuario', 'asignacionActiva'])->findOrFail($pacienteId);
            $nutricionista = Nutricionista::query()->with('usuario')->findOrFail($nutricionistaId);

            $this->validarPerfilesActivos($paciente, $nutricionista);

            $asignacionActiva = $paciente->asignacionActiva;

            if ($asignacionActiva && (int) $asignacionActiva->cod_nutricionistas_anp === (int) $nutricionista->cod_nutricionistas) {
                throw ValidationException::withMessages([
                    'cod_nutricionistas_anp' => 'El paciente ya está asignado a ese nutricionista.',
                ]);
            }

            if ($asignacionActiva) {
                $asignacionActiva->update(['est_anp' => 'reasignada']);
            }

            return AsignacionNutricionistaPaciente::create([
                'cod_nutricionistas_anp' => $nutricionista->cod_nutricionistas,
                'cod_pacientes_anp' => $paciente->cod_pacientes,
                'fec_anp' => $fecha,
                'est_anp' => 'activa',
            ]);
        });
    }

    public function cerrarAsignacion(int $asignacionId, ?string $fecha = null, ?string $observacion = null): AsignacionNutricionistaPaciente
    {
        return DB::transaction(function () use ($asignacionId) {
            $asignacion = AsignacionNutricionistaPaciente::query()->findOrFail($asignacionId);

            if ($asignacion->est_anp !== 'activa') {
                throw ValidationException::withMessages([
                    'general' => 'Solo se puede cerrar una asignación activa.',
                ]);
            }

            $asignacion->update(['est_anp' => 'cerrada']);

            return $asignacion;
        });
    }

    public function reasignarPaciente(int $pacienteId, int $nuevoNutricionistaId, string $fecha, ?string $observacion = null): AsignacionNutricionistaPaciente
    {
        return DB::transaction(function () use ($pacienteId, $nuevoNutricionistaId, $fecha) {
            $paciente = Paciente::query()->with(['usuario', 'asignacionActiva'])->findOrFail($pacienteId);
            $nuevoNutricionista = Nutricionista::query()->with('usuario')->findOrFail($nuevoNutricionistaId);

            $this->validarPerfilesActivos($paciente, $nuevoNutricionista);

            $asignacionActiva = $paciente->asignacionActiva;

            if ($asignacionActiva && (int) $asignacionActiva->cod_nutricionistas_anp === (int) $nuevoNutricionista->cod_nutricionistas) {
                throw ValidationException::withMessages([
                    'cod_nutricionistas_anp' => 'El paciente ya está asignado a ese nutricionista.',
                ]);
            }

            if ($asignacionActiva) {
                $asignacionActiva->update(['est_anp' => 'reasignada']);
            }

            return AsignacionNutricionistaPaciente::create([
                'cod_nutricionistas_anp' => $nuevoNutricionista->cod_nutricionistas,
                'cod_pacientes_anp' => $paciente->cod_pacientes,
                'fec_anp' => $fecha,
                'est_anp' => 'activa',
            ]);
        });
    }

    protected function validarPerfilesActivos(Paciente $paciente, Nutricionista $nutricionista): void
    {
        if (! $paciente->estaActivo()) {
            throw ValidationException::withMessages([
                'cod_pacientes_anp' => 'El paciente debe estar activo para poder asignarlo.',
            ]);
        }

        if (! $nutricionista->estaActivo()) {
            throw ValidationException::withMessages([
                'cod_nutricionistas_anp' => 'El nutricionista debe estar activo para poder asignarlo.',
            ]);
        }
    }
}