<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\EstadoUsuario;
use App\Enums\EstadoPerfil;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'cod_usuarios';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nom_usu',
        'ape_usu',
        'ci_usu',
        'ext_ci_usu',
        'fec_nac_usu',
        'fot_usu',
        'cor_usu',
        'cla_usu',
        'est_usu',
    ];

    protected $hidden = [
        'cla_usu',
        'remember_token',
    ];

    protected $casts = [
        'cla_usu' => 'hashed',
        'fec_nac_usu' => 'date',
        'est_usu' => EstadoUsuario::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Laravel usa este campo como contraseña autenticable.
     */
    public function getAuthPassword(): string
    {
        return $this->cla_usu;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->cor_usu;
    }

    public function getEmailAttribute(): string
    {
        return $this->cor_usu;
    }

    /**
     * Nombre completo del usuario.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nom_usu . ' ' . $this->ape_usu);
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

    public function nutricionista()
    {
        return $this->hasOne(Nutricionista::class, 'cod_usuarios_nut', 'cod_usuarios');
    }

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'cod_usuarios_pac', 'cod_usuarios');
    }

    public function estaActivo(): bool
    {
        return $this->est_usu === EstadoUsuario::ACTIVO;
    }

    public function estaInactivo(): bool
    {
        return $this->est_usu === EstadoUsuario::INACTIVO;
    }

    public function estaBloqueado(): bool
    {
        return $this->est_usu === EstadoUsuario::BLOQUEADO;
    }

    public function tieneRol(string $rol): bool
    {
        return $this->roles->contains('nom_rol', $rol);
    }

    public function isAdmin(): bool
    {
        return $this->tieneRol('Administrador') || $this->tieneRol('SuperAdmin');
    }

    public function isNutricionista(): bool
    {
        return $this->tieneRol('Nutricionista');
    }

    public function isPaciente(): bool
    {
        return $this->tieneRol('Paciente');
    }

    public function isActivo(): bool
    {
        return $this->est_usu === EstadoUsuario::ACTIVO;
    }

    /**
     * Mapea el estado del usuario base al estado correspondiente para sus perfiles.
     */
    public static function estadoPerfilDesdeUsuario(string|EstadoUsuario $estadoUsuario): string
    {
        $val = $estadoUsuario instanceof EstadoUsuario ? $estadoUsuario->value : $estadoUsuario;
        return $val === EstadoUsuario::ACTIVO->value ? EstadoPerfil::ACTIVO->value : EstadoPerfil::INACTIVO->value;
    }
}