<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'cod_notificaciones';

    protected $fillable = [
        'cod_usuarios_not',
        'tit_not',
        'des_not',
        'tip_not',
        'url_not',
        'est_not',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuarios_not', 'cod_usuarios');
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('est_not', 'no_leido');
    }
}
