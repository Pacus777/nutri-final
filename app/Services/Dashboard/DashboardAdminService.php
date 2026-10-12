<?php

namespace App\Services\Dashboard;

use App\Models\Paciente;
use App\Models\Nutricionista;
use App\Models\Usuario;
use App\Enums\SexoPaciente;

class DashboardAdminService
{
    /**
     * Obtiene todos los datos requeridos para el Dashboard de Administrador / SuperAdmin.
     */
    public function getDatos(): array
    {
        // Base set: pacientes con usuario activo
        $pacientesEstadisticas = Paciente::with('usuario')
            ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'))
            ->get();

        // Filter cohort: only pacientes with edad between 18 and 50 (inclusive)
        $pacientes18a50 = $pacientesEstadisticas->filter(function ($paciente) {
            $edad = $paciente->edad;
            return is_numeric($edad) && $edad >= 18 && $edad <= 50;
        })->values();

        // Ranges for full population (kept for backwards compatibility)
        $rangosEdad = [
            '0-17' => 0,
            '18-29' => 0,
            '30-44' => 0,
            '45-59' => 0,
            '60+' => 0,
            'sin_dato' => 0,
        ];

        // Ranges for 18-50 cohort (more granular inside the allowed range)
        $rangosEdad18_50 = [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-50' => 0,
            'sin_dato' => 0,
        ];

        $sexoPacientes = [
            'Masculino' => 0,
            'Femenino' => 0,
            'Otro' => 0,
            'Sin dato' => 0,
        ];

        $sexoPacientes18_50 = [
            'Masculino' => 0,
            'Femenino' => 0,
            'Otro' => 0,
            'Sin dato' => 0,
        ];

        // Populate full population ranges and sex
        $edades = $pacientesEstadisticas->map(function ($paciente) use (&$rangosEdad, &$sexoPacientes) {
            $edad = $paciente->edad;

            if ($edad === null) {
                $rangosEdad['sin_dato']++;
            } elseif ($edad <= 17) {
                $rangosEdad['0-17']++;
            } elseif ($edad <= 29) {
                $rangosEdad['18-29']++;
            } elseif ($edad <= 44) {
                $rangosEdad['30-44']++;
            } elseif ($edad <= 59) {
                $rangosEdad['45-59']++;
            } else {
                $rangosEdad['60+']++;
            }

            $sexo = $paciente->sex_pac;

            if ($sexo === SexoPaciente::MASCULINO) {
                $sexoPacientes['Masculino']++;
            } elseif ($sexo === SexoPaciente::FEMENINO) {
                $sexoPacientes['Femenino']++;
            } elseif ($sexo === SexoPaciente::OTRO) {
                $sexoPacientes['Otro']++;
            } else {
                $sexoPacientes['Sin dato']++;
            }

            return $edad;
        })->filter();

        // Populate 18-50 cohort ranges and sex
        $edades18_50 = $pacientes18a50->map(function ($paciente) use (&$rangosEdad18_50, &$sexoPacientes18_50) {
            $edad = $paciente->edad;

            if ($edad === null) {
                $rangosEdad18_50['sin_dato']++;
            } elseif ($edad <= 25) {
                $rangosEdad18_50['18-25']++;
            } elseif ($edad <= 35) {
                $rangosEdad18_50['26-35']++;
            } elseif ($edad <= 45) {
                $rangosEdad18_50['36-45']++;
            } else {
                $rangosEdad18_50['46-50']++;
            }

            $sexo = $paciente->sex_pac;

            if ($sexo === SexoPaciente::MASCULINO) {
                $sexoPacientes18_50['Masculino']++;
            } elseif ($sexo === SexoPaciente::FEMENINO) {
                $sexoPacientes18_50['Femenino']++;
            } elseif ($sexo === SexoPaciente::OTRO) {
                $sexoPacientes18_50['Otro']++;
            } else {
                $sexoPacientes18_50['Sin dato']++;
            }

            return $edad;
        })->filter();

        $edadPromedio = $edades->count() > 0 ? round($edades->avg(), 1) : null;
        $edadPromedio18_50 = $edades18_50->count() > 0 ? round($edades18_50->avg(), 1) : null;

        // Other useful metrics: new patients in last 30 days (by usuario created_at)
        $nuevosPacientes30Dias = Paciente::whereHas('usuario', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))->count();

        return [
            'estadisticas' => [
                'usuarios_totales' => Usuario::count(),
                'usuarios_activos' => Usuario::where('est_usu', 'activo')->count(),
                'usuarios_inactivos' => Usuario::where('est_usu', '!=', 'activo')->count(),
                'pacientes_totales' => Paciente::count(),
                'pacientes_activos' => Paciente::where('est_pac', 'activo')->count(),
                'pacientes_18_50_total' => $pacientes18a50->count(),
                'pacientes_18_50_activos' => $pacientes18a50->filter(fn($p) => ($p->est_pac ?? '') === 'activo')->count(),
                'nutricionistas_totales' => Nutricionista::count(),
                'nutricionistas_activos' => Nutricionista::where('est_nut', 'activo')->count(),
                'edad_promedio_pacientes' => $edadPromedio,
                'edad_promedio_pacientes_18_50' => $edadPromedio18_50,
                'rangos_edad_pacientes' => $rangosEdad,
                'rangos_edad_pacientes_18_50' => $rangosEdad18_50,
                'sexo_pacientes' => $sexoPacientes,
                'sexo_pacientes_18_50' => $sexoPacientes18_50,
                'nuevos_pacientes_30dias' => $nuevosPacientes30Dias,
            ],
        ];
    }
}
