<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case BLOQUEADO = 'bloqueado';

    /**
     * Obtiene los valores como un array de strings.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
