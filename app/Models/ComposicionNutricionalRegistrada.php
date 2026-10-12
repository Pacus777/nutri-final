<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComposicionNutricionalRegistrada extends Model
{
    use HasFactory;

    protected $table = 'composiciones_nutricionales_registradas';

    protected $primaryKey = 'cod_composiciones_nutricionales_registradas';

    protected $fillable = [
        'cod_registros_alimenticios_cnr',
        'cal_cnr',
        'pro_cnr',
        'gra_cnr',
        'car_cnr',
        'vit_cnr',
        'min_cnr',
    ];

    protected function casts(): array
    {
        return [
            'cal_cnr' => 'decimal:2',
            'pro_cnr' => 'decimal:2',
            'gra_cnr' => 'decimal:2',
            'car_cnr' => 'decimal:2',
            'vit_cnr' => 'decimal:2',
            'min_cnr' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function registroAlimenticio()
    {
        return $this->belongsTo(
            RegistroAlimenticio::class,
            'cod_registros_alimenticios_cnr',
            'cod_registros_alimenticios'
        );
    }
}
