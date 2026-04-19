<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsuarioRol extends Model
{
    use HasFactory;

    protected $table = 'usuario_rol';

    protected $primaryKey = 'cod_usuario_rol';

    protected $fillable = [
        'cod_usuarios_url',
        'cod_roles_url',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cod_usuarios_url', 'cod_usuarios');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'cod_roles_url', 'cod_roles');
    }
}