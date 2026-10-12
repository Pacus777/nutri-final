<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjusteObjetivoNutricional extends Model
{
    use HasFactory;

    public const MOTIVO_CREACION_OBJETIVO = 'creacion_objetivo';
    public const MOTIVO_CAMBIO_ESTADO = 'cambio_estado';
    public const MOTIVO_CAMBIO_DESCRIPCION = 'cambio_descripcion';
    public const MOTIVO_AJUSTE_MANUAL = 'ajuste_manual';
    public const MOTIVO_SEGUIMIENTO = 'seguimiento';

    public const MOTIVOS = [
        self::MOTIVO_CREACION_OBJETIVO,
        self::MOTIVO_CAMBIO_ESTADO,
        self::MOTIVO_CAMBIO_DESCRIPCION,
        self::MOTIVO_AJUSTE_MANUAL,
        self::MOTIVO_SEGUIMIENTO,
    ];

    public const MOTIVOS_MANUALES = [
        self::MOTIVO_AJUSTE_MANUAL,
        self::MOTIVO_SEGUIMIENTO,
    ];

    protected $table = 'ajustes_objetivos_nutricionales';

    protected $primaryKey = 'cod_ajustes_objetivos_nutricionales';

    protected $fillable = [
        'cod_objetivos_nutricionales_ajn',
        'cod_nutricionistas_ajn',
        'fec_ajn',
        'des_ajn',
        'motivo_ajn',
        'estado_anterior_ajn',
        'estado_nuevo_ajn',
        'descripcion_anterior_ajn',
        'descripcion_nueva_ajn',
    ];

    protected function casts(): array
    {
        return [
            'fec_ajn' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AjusteObjetivoNutricional $ajuste) {
            $ajuste->loadMissing('objetivoNutricional.paciente.usuario');
            $objetivo = $ajuste->objetivoNutricional;

            if ($objetivo && $objetivo->paciente && $objetivo->paciente->usuario) {
                $usuario = $objetivo->paciente->usuario;

                $titulo = 'Objetivo Nutricional';
                $mensajeNotif = $ajuste->des_ajn;

                if ($ajuste->motivo_ajn === self::MOTIVO_CREACION_OBJETIVO) {
                    $titulo = 'Nuevo Objetivo Nutricional';
                    $mensajeNotif = 'Tu nutricionista ha definido un nuevo objetivo: ' . $ajuste->descripcion_nueva_ajn;
                } elseif ($ajuste->motivo_ajn === self::MOTIVO_CAMBIO_ESTADO) {
                    $titulo = 'Objetivo Nutricional Actualizado';
                    $estadoTexto = match($ajuste->estado_nuevo_ajn) {
                        ObjetivoNutricional::ESTADO_CUMPLIDO => 'marcado como CUMPLIDO',
                        ObjetivoNutricional::ESTADO_PAUSADO => 'PAUSADO',
                        ObjetivoNutricional::ESTADO_CANCELADO => 'CANCELADO',
                        ObjetivoNutricional::ESTADO_CERRADO => 'CERRADO',
                        ObjetivoNutricional::ESTADO_ACTIVO => 'REACTIVADO',
                        default => 'actualizado',
                    };
                    $mensajeNotif = "Tu objetivo «{$ajuste->descripcion_nueva_ajn}» ha sido {$estadoTexto}.";
                } elseif ($ajuste->motivo_ajn === self::MOTIVO_CAMBIO_DESCRIPCION) {
                    $titulo = 'Objetivo Nutricional Modificado';
                    $mensajeNotif = 'Se ha modificado la descripción de tu objetivo: ' . $ajuste->descripcion_nueva_ajn;
                } elseif ($ajuste->motivo_ajn === self::MOTIVO_AJUSTE_MANUAL) {
                    $titulo = 'Ajuste de Objetivo';
                    $mensajeNotif = 'Se realizó un ajuste en tu objetivo: ' . $ajuste->des_ajn;
                }

                Notificacion::create([
                    'cod_usuarios_not' => $usuario->cod_usuarios,
                    'tit_not' => $titulo,
                    'des_not' => $mensajeNotif,
                    'tip_not' => 'nuevo_objetivo',
                    'url_not' => '/dashboard/paciente',
                    'est_not' => 'no_leido',
                ]);
            }
        });
    }

    public function objetivoNutricional()
    {
        return $this->belongsTo(
            ObjetivoNutricional::class,
            'cod_objetivos_nutricionales_ajn',
            'cod_objetivos_nutricionales'
        );
    }

    public function objetivo()
    {
        return $this->objetivoNutricional();
    }

    public function nutricionista()
    {
        return $this->belongsTo(
            Nutricionista::class,
            'cod_nutricionistas_ajn',
            'cod_nutricionistas'
        );
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByDesc('fec_ajn')
            ->orderByDesc('cod_ajustes_objetivos_nutricionales');
    }

    public static function registrarCambio(
        ObjetivoNutricional $objetivo,
        Nutricionista $nutricionista,
        string $descripcion,
        string $motivo = self::MOTIVO_AJUSTE_MANUAL,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?string $descripcionAnterior = null,
        ?string $descripcionNueva = null
    ): self {
        return self::create([
            'cod_objetivos_nutricionales_ajn' => $objetivo->getKey(),
            'cod_nutricionistas_ajn' => $nutricionista->getKey(),
            'fec_ajn' => now()->toDateString(),
            'des_ajn' => $descripcion,
            'motivo_ajn' => $motivo,
            'estado_anterior_ajn' => $estadoAnterior,
            'estado_nuevo_ajn' => $estadoNuevo,
            'descripcion_anterior_ajn' => $descripcionAnterior,
            'descripcion_nueva_ajn' => $descripcionNueva,
        ]);
    }
}