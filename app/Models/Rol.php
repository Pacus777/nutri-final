<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $primaryKey = 'cod_roles';

    protected $fillable = [
        'nom_rol',
        'des_rol',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'usuario_rol',
            'cod_roles_url',
            'cod_usuarios_url',
            'cod_roles',
            'cod_usuarios'
        )->withTimestamps();
    }

    public function usuarioRoles()
    {
        return $this->hasMany(UsuarioRol::class, 'cod_roles_url', 'cod_roles');
    }
}