<?php

namespace App\Services;

use App\Models\AsignacionNutricionistaPaciente;
use App\Models\HistorialNutricional;
use App\Models\Nutricionista;
use App\Models\SeguimientoNutricional;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeguimientoNutricionalService
{
    public function registrar(array $payload, Nutricionista $nutricionista): SeguimientoNutricional
    {
        $codPaciente = (int) Arr::get($payload, 'cod_pacientes_seg');

        $asignacionActiva = AsignacionNutricionistaPaciente::query()
            ->where('cod_pacientes_anp', $codPaciente)
            ->where('cod_nutricionistas_anp', $nutricionista->cod_nutricionistas)
            ->where('est_anp', 'activa')
            ->exists();

        if (! $asignacionActiva) {
            throw ValidationException::withMessages([
                'cod_pacientes_seg' => 'No tienes una asignacion activa para registrar seguimiento de este paciente.',
            ]);
        }

        return DB::transaction(function () use ($payload, $nutricionista, $codPaciente) {
            $resultado = trim((string) Arr::get($payload, 'res_seg'));
            $cambioManual = trim((string) Arr::get($payload, 'cam_seg', ''));

            $anterior = SeguimientoNutricional::query()
                ->where('cod_pacientes_seg', $codPaciente)
                ->where('cod_nutricionistas_seg', $nutricionista->cod_nutricionistas)
                ->latest('fec_seg')
                ->first();

            $cambioSignificativo = $this->detectarCambioSignificativo(
                $resultado,
                $anterior?->res_seg
            );

            $camSeg = $cambioManual;

            if ($cambioSignificativo && $camSeg === '') {
                $camSeg = 'Se detecta cambio significativo respecto al seguimiento anterior.';
            }

            $seguimiento = SeguimientoNutricional::create([
                'cod_pacientes_seg' => $codPaciente,
                'cod_nutricionistas_seg' => $nutricionista->cod_nutricionistas,
                'fec_seg' => Arr::get($payload, 'fec_seg'),
                'res_seg' => $resultado,
                'cam_seg' => $camSeg === '' ? null : $camSeg,
            ]);

            $descripcionHistorial = $this->construirDescripcionHistorial($seguimiento);

            if (trim($descripcionHistorial) === '') {
                throw ValidationException::withMessages([
                    'res_seg' => 'No se puede generar historial nutricional vacio.',
                ]);
            }

            HistorialNutricional::create([
                'cod_pacientes_hin' => $codPaciente,
                'fec_hin' => Arr::get($payload, 'fec_seg'),
                'des_hin' => $descripcionHistorial,
            ]);

            return $seguimiento;
        });
    }

    protected function detectarCambioSignificativo(string $actual, ?string $anterior): bool
    {
        $actualLimpio = mb_strtolower(trim($actual));
        $anteriorLimpio = mb_strtolower(trim((string) $anterior));

        if ($actualLimpio === '' || $anteriorLimpio === '') {
            return false;
        }

        if ($actualLimpio === $anteriorLimpio) {
            return false;
        }

        similar_text($actualLimpio, $anteriorLimpio, $porcentajeSimilitud);

        return $porcentajeSimilitud < 65;
    }

    protected function construirDescripcionHistorial(SeguimientoNutricional $seguimiento): string
    {
        $bloques = [
            'Resultado: '.trim((string) $seguimiento->res_seg),
        ];

        if (trim((string) $seguimiento->cam_seg) !== '') {
            $bloques[] = 'Cambio significativo: '.trim((string) $seguimiento->cam_seg);
        }

        return implode(' | ', $bloques);
    }
}
