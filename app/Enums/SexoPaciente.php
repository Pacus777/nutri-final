<?php

namespace App\Enums;

enum SexoPaciente: string
{
    case MASCULINO = 'masculino';
    case FEMENINO = 'femenino';
    case OTRO = 'otro';

    /**
     * Devuelve una versión formateada en mayúscula para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::MASCULINO => 'Masculino',
            self::FEMENINO => 'Femenino',
            self::OTRO => 'Otro',
        };
    }

    /**
     * Normaliza cualquier string heredado ('M', 'f', 'Masculino', etc.) a un Enum de SexoPaciente.
     */
    public static function normalizar(?string $sexo): ?self
    {
        if ($sexo === null) {
            return null;
        }

        $clean = strtolower(trim($sexo));

        if (in_array($clean, ['m', 'masculino'], true)) {
            return self::MASCULINO;
        }

        if (in_array($clean, ['f', 'femenino'], true)) {
            return self::FEMENINO;
        }

        if ($clean === 'otro') {
            return self::OTRO;
        }

        return null;
    }
}
