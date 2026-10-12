<?php

namespace App\Services;

use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

class UsuarioRegistroService
{
    /**
     * Crea un usuario con sus roles y perfil base, si corresponde.
     *
     * Este método sirve para:
     * - Nuevo usuario
     * - Nuevo nutricionista
     * - Nuevo paciente
     */
    public function crear(array $data, ?UploadedFile $foto = null): Usuario
    {
        $rutaFoto = null;

        try {
            return DB::transaction(function () use ($data, $foto, &$rutaFoto) {
                $rutaFoto = $this->guardarFoto($foto);

                $usuario = $this->crearUsuario($data, $rutaFoto);

                $rolesIds = $this->resolverRoles($data);

                $usuario->roles()->syncWithoutDetaching($rolesIds);

                $this->crearPerfilSegunTipo($usuario, $data);

                return $usuario->load(['roles', 'nutricionista', 'paciente']);
            });
        } catch (Throwable $exception) {
            if ($rutaFoto && Storage::disk('public')->exists($rutaFoto)) {
                Storage::disk('public')->delete($rutaFoto);
            }

            throw $exception;
        }
    }

    private function guardarFoto(?UploadedFile $foto): ?string
    {
        if (! $foto) {
            return null;
        }

        return $foto->store('usuarios', 'public');
    }

    private function crearUsuario(array $data, ?string $rutaFoto): Usuario
    {
        return Usuario::create([
            'nom_usu' => $data['nom_usu'],
            'ape_usu' => $data['ape_usu'],
            'ci_usu' => $data['ci_usu'],
            'ext_ci_usu' => $data['ext_ci_usu'],
            'fec_nac_usu' => $data['fec_nac_usu'] ?? null,
            'fot_usu' => $rutaFoto,
            'cor_usu' => $data['cor_usu'],
            'cla_usu' => $data['cla_usu'],
            'est_usu' => $data['est_usu'] ?? 'activo',
        ]);
    }

    private function resolverRoles(array $data): array
    {
        $rolesIds = collect($data['roles'] ?? [])
            ->filter()
            ->map(fn ($rol) => (int) $rol)
            ->values();

        if ($rolesIds->isEmpty() && ! empty($data['tipo_registro'])) {
            $rol = Rol::query()
                ->where('nom_rol', $data['tipo_registro'])
                ->where('est_rol', 'activo')
                ->first();

            if (! $rol) {
                throw new InvalidArgumentException(
                    "No existe un rol activo para el tipo de registro: {$data['tipo_registro']}"
                );
            }

            $rolesIds->push((int) $rol->cod_roles);
        }

        if (! empty($data['tipo_registro']) && in_array($data['tipo_registro'], ['Nutricionista', 'Paciente'], true)) {
            $rolPerfil = Rol::query()
                ->where('nom_rol', $data['tipo_registro'])
                ->where('est_rol', 'activo')
                ->first();

            if (! $rolPerfil) {
                throw new InvalidArgumentException(
                    "No existe un rol activo para el perfil: {$data['tipo_registro']}"
                );
            }

            $rolesIds->push((int) $rolPerfil->cod_roles);
        }

        $rolesIds = $rolesIds->unique()->values();

        if ($rolesIds->isEmpty()) {
            throw new InvalidArgumentException('El usuario debe tener al menos un rol asignado.');
        }

        return $rolesIds->all();
    }

    private function crearPerfilSegunTipo(Usuario $usuario, array $data): void
    {
        $tipoRegistro = $data['tipo_registro'] ?? null;

        if ($tipoRegistro === 'Nutricionista') {
            $this->crearPerfilNutricionista($usuario, $data);
            return;
        }

        if ($tipoRegistro === 'Paciente') {
            $this->crearPerfilPaciente($usuario, $data);
        }
    }

    private function crearPerfilNutricionista(Usuario $usuario, array $data): void
    {
        Nutricionista::create([
            'cod_usuarios_nut' => $usuario->cod_usuarios,
            'esp_nut' => $data['esp_nut'] ?? null,
            'est_nut' => Usuario::estadoPerfilDesdeUsuario($data['est_usu'] ?? 'activo'),
        ]);
    }

    private function crearPerfilPaciente(Usuario $usuario, array $data): void
    {
        Paciente::create([
            'cod_usuarios_pac' => $usuario->cod_usuarios,
            'sex_pac' => $data['sex_pac'] ?? null,
            'pes_pac' => $data['pes_pac'] ?? null,
            'estatura_pac' => $data['estatura_pac'] ?? null,
            'tip_san_pac' => $data['tip_san_pac'] ?? null,
            'ale_pac' => $data['ale_pac'] ?? null,
            'est_pac' => Usuario::estadoPerfilDesdeUsuario($data['est_usu'] ?? 'activo'),
        ]);
    }
}