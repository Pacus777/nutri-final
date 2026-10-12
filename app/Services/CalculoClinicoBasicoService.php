<?php

namespace App\Services;

class CalculoClinicoBasicoService
{
    /**
     * Calcular el Índice de Masa Corporal (IMC).
     *
     * @param float $pesoKg Peso en kilogramos
     * @param float $estatura Estatura en metros o centímetros
     * @return float|null IMC redondeado a 2 decimales, o null si los datos son inválidos
     */
    public function calcularImc($pesoKg, $estatura): ?float
    {
        if (!$pesoKg || !$estatura) {
            return null;
        }

        // Convertir a float
        $pesoKg = floatval($pesoKg);
        $estatura = floatval($estatura);

        // Validar que los valores sean positivos
        if ($pesoKg <= 0 || $estatura <= 0) {
            return null;
        }

        // Si la estatura es mayor a 3, asumir que está en centímetros
        if ($estatura > 3) {
            $estatura = $estatura / 100;
        }

        // Calcular IMC: peso / (estatura * estatura)
        $imc = $pesoKg / ($estatura * $estatura);

        // Redondear a 2 decimales
        return round($imc, 2);
    }

    /**
     * Clasificar el IMC según estándares de la OMS.
     * (Solo informativo, no diagnóstico médico)
     *
     * @param float $imc
     * @return string
     */
    public function clasificarImc($imc): string
    {
        if ($imc < 18.5) {
            return 'Bajo peso';
        } elseif ($imc < 25) {
            return 'Peso normal';
        } elseif ($imc < 30) {
            return 'Sobrepeso';
        } elseif ($imc < 35) {
            return 'Obesidad tipo I';
        } elseif ($imc < 40) {
            return 'Obesidad tipo II';
        } else {
            return 'Obesidad tipo III';
        }
    }

    /**
     * Validar que los datos antropométricos sean coherentes.
     *
     * @param float $pesoKg
     * @param float $estatura
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarDatosAntropometricos($pesoKg, $estatura): array
    {
        if (!$pesoKg || !$estatura) {
            return [
                'valido' => false,
                'mensaje' => 'Peso y estatura son requeridos.',
            ];
        }

        $pesoKg = floatval($pesoKg);
        $estatura = floatval($estatura);

        if ($pesoKg <= 0) {
            return [
                'valido' => false,
                'mensaje' => 'El peso debe ser un valor positivo.',
            ];
        }

        if ($estatura <= 0) {
            return [
                'valido' => false,
                'mensaje' => 'La estatura debe ser un valor positivo.',
            ];
        }

        // Convertir estatura si está en centímetros
        if ($estatura > 3) {
            $estatura = $estatura / 100;
        }

        // Validar rango razonable (humano)
        if ($pesoKg > 500 || $pesoKg < 2) {
            return [
                'valido' => false,
                'mensaje' => 'El peso está fuera del rango válido (2-500 kg).',
            ];
        }

        if ($estatura > 2.5 || $estatura < 0.5) {
            return [
                'valido' => false,
                'mensaje' => 'La estatura está fuera del rango válido (0.5-2.5 m).',
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Datos válidos.',
        ];
    }
}
