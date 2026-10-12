<?php

namespace App\Enums;

enum EstadoPerfil: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';

    /**
     * Obtiene los valores como un array de strings.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
