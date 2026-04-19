<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'cod_usuarios';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nom_usu',
        'cor_usu',
        'cla_usu',
        'est_usu',
    ];

    protected $hidden = [
        'cla_usu',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Laravel usa este campo como contraseña autenticable.
     */
    public function getAuthPassword(): string
    {
        return $this->cla_usu;
    }

    /**
     * Relación con roles.
     */
    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'usuario_rol',
            'cod_usuarios_url',
            'cod_roles_url',
            'cod_usuarios',
            'cod_roles'
        )->withTimestamps();
    }

    public function usuarioRoles()
    {
        return $this->hasMany(UsuarioRol::class, 'cod_usuarios_url', 'cod_usuarios');
    }

    public function estaActivo(): bool
    {
        return $this->est_usu === 'activo';
    }

    public function estaInactivo(): bool
    {
        return $this->est_usu === 'inactivo';
    }

    public function estaBloqueado(): bool
    {
        return $this->est_usu === 'bloqueado';
    }

    public function tieneRol(string $rol): bool
    {
        return $this->roles->contains('nom_rol', $rol);
    }
}