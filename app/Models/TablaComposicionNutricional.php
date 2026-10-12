<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TablaComposicionNutricional extends Model
{
    use HasFactory;

    protected $table = 'tabla_composicion_nutricional';

    protected $primaryKey = 'cod_tabla_composicion_nutricional';

    public const ESTADO_VIGENTE = 'vigente';
    public const ESTADO_ARCHIVADA = 'archivada';

    public const ESTADOS = [
        self::ESTADO_VIGENTE,
        self::ESTADO_ARCHIVADA,
    ];

    protected $fillable = [
        'cod_alimentos_tcn',
        'por_ref_tcn',
        'cant_ref_tcn',
        'uni_ref_tcn',
        'cal_tcn',
        'pro_tcn',
        'gra_tcn',
        'car_tcn',
        'fib_tcn',
        'azu_tcn',
        'gra_sat_tcn',
        'gra_mono_tcn',
        'gra_poli_tcn',
        'sod_tcn',
        'pot_tcn',
        'col_tcn',
        'calcio_tcn',
        'hierro_tcn',
        'vit_a_tcn',
        'vit_c_tcn',
        'vit_d_tcn',
        'fec_tcn',
        'est_tcn',
    ];

    protected function casts(): array
    {
        return [
            'cant_ref_tcn' => 'decimal:2',
            'cal_tcn' => 'decimal:2',
            'pro_tcn' => 'decimal:2',
            'gra_tcn' => 'decimal:2',
            'car_tcn' => 'decimal:2',
            'fib_tcn' => 'decimal:2',
            'azu_tcn' => 'decimal:2',
            'gra_sat_tcn' => 'decimal:2',
            'gra_mono_tcn' => 'decimal:2',
            'gra_poli_tcn' => 'decimal:2',
            'sod_tcn' => 'decimal:2',
            'pot_tcn' => 'decimal:2',
            'col_tcn' => 'decimal:2',
            'calcio_tcn' => 'decimal:2',
            'hierro_tcn' => 'decimal:2',
            'vit_a_tcn' => 'decimal:2',
            'vit_c_tcn' => 'decimal:2',
            'vit_d_tcn' => 'decimal:2',
            'fec_tcn' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function alimento()
    {
        return $this->belongsTo(
            Alimento::class,
            'cod_alimentos_tcn',
            'cod_alimentos'
        );
    }

    public function esVigente(): bool
    {
        return $this->est_tcn === self::ESTADO_VIGENTE;
    }

    public function esArchivada(): bool
    {
        return $this->est_tcn === self::ESTADO_ARCHIVADA;
    }

    public function estaVigente(): bool
    {
        return $this->est_tcn === self::ESTADO_VIGENTE;
    }

    public function archivar(): bool
    {
        return $this->update([
            'est_tcn' => self::ESTADO_ARCHIVADA,
        ]);
    }
}