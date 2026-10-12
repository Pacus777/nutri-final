<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleRegistroAlimenticio extends Model
{
    use HasFactory;

    public const TIPO_DESAYUNO = 'desayuno';
    public const TIPO_MEDIA_MANANA = 'media_manana';
    public const TIPO_ALMUERZO = 'almuerzo';
    public const TIPO_MERIENDA = 'merienda';
    public const TIPO_CENA = 'cena';
    public const TIPO_SNACK = 'snack';

    public const TIPOS_COMIDA = [
        self::TIPO_DESAYUNO,
        self::TIPO_MEDIA_MANANA,
        self::TIPO_ALMUERZO,
        self::TIPO_MERIENDA,
        self::TIPO_CENA,
        self::TIPO_SNACK,
    ];

    public const UNIDAD_GRAMOS = 'gramos';
    public const UNIDAD_MILILITROS = 'mililitros';
    public const UNIDAD_UNIDAD = 'unidad';
    public const UNIDAD_PORCION = 'porcion';
    public const UNIDAD_TAZA = 'taza';
    public const UNIDAD_CUCHARADA = 'cucharada';

    public const UNIDADES = [
        self::UNIDAD_GRAMOS,
        self::UNIDAD_MILILITROS,
        self::UNIDAD_UNIDAD,
        self::UNIDAD_PORCION,
        self::UNIDAD_TAZA,
        self::UNIDAD_CUCHARADA,
    ];

    protected $table = 'detalle_registro_alimenticio';

    protected $primaryKey = 'cod_detalle_registro_alimenticio';

    protected $fillable = [
        'cod_registros_alimenticios_dra',
        'cod_alimentos_dra',
        'tip_dra',
        'can_dra',
        'uni_dra',
        'hor_dra',
    ];

    protected function casts(): array
    {
        return [
            'can_dra' => 'decimal:2',
            'hor_dra' => 'datetime:H:i:s',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function registroAlimenticio()
    {
        return $this->belongsTo(
            RegistroAlimenticio::class,
            'cod_registros_alimenticios_dra',
            'cod_registros_alimenticios'
        );
    }

    public function alimento()
    {
        return $this->belongsTo(
            Alimento::class,
            'cod_alimentos_dra',
            'cod_alimentos'
        );
    }

    public function cantidadNormalizada(): float
    {
        return max((float) $this->can_dra, 0);
    }
}