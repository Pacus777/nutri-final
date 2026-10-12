<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\DetalleRegistroAlimenticio;

class Alimento extends Model
{
    use HasFactory;

    protected $table = 'alimentos';

    protected $primaryKey = 'cod_alimentos';

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    public const FUENTE_USDA = 'usda';
    public const FUENTE_FATSECRET = 'fatsecret';
    public const FUENTE_MANUAL = 'manual';

    protected $fillable = [
        'nom_ali',
        'nom_ext_ali',
        'tip_ali',
        'des_ali',
        'mar_ali',
        'src_ali',
        'ref_ext_ali',
        'idi_ali',
        'pai_ali',
        'img_ali',
        'fec_sync_ali',
        'est_ali',
    ];

    protected function casts(): array
    {
        return [
            'fec_sync_ali' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function composicionesNutricionales()
    {
        return $this->hasMany(
            TablaComposicionNutricional::class,
            'cod_alimentos_tcn',
            'cod_alimentos'
        );
    }

    public function composicionVigente()
    {
        return $this->hasOne(
            TablaComposicionNutricional::class,
            'cod_alimentos_tcn',
            'cod_alimentos'
        )->where('est_tcn', TablaComposicionNutricional::ESTADO_VIGENTE);
    }

    public function esActivo(): bool
    {
        return $this->est_ali === self::ESTADO_ACTIVO;
    }

    public function esInactivo(): bool
    {
        return $this->est_ali === self::ESTADO_INACTIVO;
    }

    public function esFuenteFatsecret(): bool
    {
        return $this->src_ali === self::FUENTE_FATSECRET;
    }

    public function esFuenteManual(): bool
    {
        return $this->src_ali === self::FUENTE_MANUAL;
    }

    public function esFuenteUsda(): bool
    {
        return $this->src_ali === self::FUENTE_USDA;
    }

    public function detallesRegistroAlimenticio()
    {
        return $this->hasMany(
            DetalleRegistroAlimenticio::class,
            'cod_alimentos_dra',
            'cod_alimentos'
        );
    }
}