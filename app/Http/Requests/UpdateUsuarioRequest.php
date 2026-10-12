<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('roles')) {
            $this->merge([
                'roles' => collect($this->input('roles', []))
                    ->filter(fn ($rol) => $rol !== null && $rol !== '')
                    ->map(fn ($rol) => (int) $rol)
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function rules(): array
    {
        $usuarioId = $this->usuarioId();

        return [
            // Datos personales
            'nom_usu' => [
                'required',
                'string',
                'max:120',
            ],

            'ape_usu' => [
                'required',
                'string',
                'max:120',
            ],

            'ci_usu' => [
                'required',
                'string',
                'max:12',
                Rule::unique('usuarios', 'ci_usu')
                    ->ignore($usuarioId, 'cod_usuarios')
                    ->where(fn ($query) => $query->where('ext_ci_usu', $this->input('ext_ci_usu'))),
            ],

            'ext_ci_usu' => [
                'required',
                'string',
                'max:5',
            ],

            'fec_nac_usu' => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            ],

            'fot_usu' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            // Datos de acceso
            'cor_usu' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'cor_usu')
                    ->ignore($usuarioId, 'cod_usuarios'),
            ],

            'cla_usu' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'est_usu' => [
                'required',
                Rule::in(['activo', 'inactivo', 'bloqueado']),
            ],

            // Roles múltiples
            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'integer',
                'distinct',
                'exists:roles,cod_roles',
            ],

            // Perfil nutricionista
            'tiene_perfil_nutricionista' => [
                'nullable',
                'boolean',
            ],

            'esp_nut' => [
                'nullable',
                'string',
                'max:150',
            ],

            'est_nut' => [
                'nullable',
                Rule::in(['activo', 'inactivo']),
            ],

            // Perfil paciente
            'tiene_perfil_paciente' => [
                'nullable',
                'boolean',
            ],

            'sex_pac' => [
                'nullable',
                Rule::in(['masculino', 'femenino', 'otro']),
            ],

            'pes_pac' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:999.99',
            ],

            'estatura_pac' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:2.99',
            ],

            'tip_san_pac' => [
                'nullable',
                Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            ],

            'ale_pac' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'est_pac' => [
                'nullable',
                Rule::in(['activo', 'inactivo']),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $usuarioId = $this->usuarioId();

            if (! $usuarioId) {
                return;
            }

            $rolesSolicitados = collect($this->input('roles', []))
                ->map(fn ($rol) => (int) $rol)
                ->values();

            if ($rolesSolicitados->isEmpty()) {
                return;
            }

            $rolesPorNombre = DB::table('roles')
                ->whereIn('nom_rol', [
                    'SuperAdmin',
                    'Administrador',
                    'Nutricionista',
                    'Paciente',
                ])
                ->pluck('cod_roles', 'nom_rol');

            $this->validarPerfilNutricionistaConRol(
                $validator,
                $usuarioId,
                $rolesSolicitados,
                $rolesPorNombre
            );

            $this->validarPerfilPacienteConRol(
                $validator,
                $usuarioId,
                $rolesSolicitados,
                $rolesPorNombre
            );

            $this->validarAdministradorActivo(
                $validator,
                $usuarioId,
                $rolesSolicitados,
                $rolesPorNombre
            );
        });
    }

    public function messages(): array
    {
        return [
            // Usuario
            'nom_usu.required' => 'El nombre del usuario es obligatorio.',
            'ape_usu.required' => 'El apellido del usuario es obligatorio.',

            'ci_usu.required' => 'El CI del usuario es obligatorio.',
            'ci_usu.unique' => 'Ya existe un usuario registrado con este CI y extensión.',

            'ext_ci_usu.required' => 'La extensión del CI es obligatoria.',

            'fec_nac_usu.required' => 'La fecha de nacimiento es obligatoria.',
            'fec_nac_usu.date' => 'La fecha de nacimiento no tiene un formato válido.',
            'fec_nac_usu.before_or_equal' => 'El usuario debe tener 18 años o más.',

            'fot_usu.image' => 'La foto debe ser una imagen.',
            'fot_usu.mimes' => 'La foto debe estar en formato JPG, JPEG, PNG o WEBP.',
            'fot_usu.max' => 'La foto no debe superar los 2 MB.',

            // Acceso
            'cor_usu.required' => 'El correo electrónico es obligatorio.',
            'cor_usu.email' => 'El correo electrónico no tiene un formato válido.',
            'cor_usu.unique' => 'Ya existe un usuario registrado con este correo electrónico.',

            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',

            'est_usu.required' => 'El estado del usuario es obligatorio.',
            'est_usu.in' => 'El estado del usuario seleccionado no es válido.',

            // Roles
            'roles.required' => 'El usuario debe tener al menos un rol asignado.',
            'roles.array' => 'Los roles deben enviarse como una lista.',
            'roles.min' => 'El usuario debe tener al menos un rol asignado.',
            'roles.*.integer' => 'Uno de los roles seleccionados no es válido.',
            'roles.*.distinct' => 'Hay roles repetidos en la solicitud.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',

            // Nutricionista
            'esp_nut.max' => 'La especialidad no debe superar los 150 caracteres.',
            'est_nut.in' => 'El estado del perfil nutricionista no es válido.',

            // Paciente
            'sex_pac.in' => 'El sexo seleccionado no es válido.',

            'pes_pac.numeric' => 'El peso debe ser un valor numérico.',
            'pes_pac.min' => 'El peso debe ser mayor a cero.',
            'pes_pac.max' => 'El peso ingresado es demasiado alto.',

            'estatura_pac.numeric' => 'La estatura debe ser un valor numérico.',
            'estatura_pac.min' => 'La estatura debe ser mayor a cero.',
            'estatura_pac.max' => 'La estatura ingresada no es válida.',

            'tip_san_pac.in' => 'El tipo de sangre seleccionado no es válido.',
            'ale_pac.max' => 'Las alergias no deben superar los 1000 caracteres.',
            'est_pac.in' => 'El estado del perfil paciente no es válido.',
        ];
    }

    private function usuarioId(): ?int
    {
        $usuario = $this->route('usuario');

        if (is_object($usuario) && isset($usuario->cod_usuarios)) {
            return (int) $usuario->cod_usuarios;
        }

        if (is_numeric($usuario)) {
            return (int) $usuario;
        }

        return null;
    }

    private function validarPerfilNutricionistaConRol(
        Validator $validator,
        int $usuarioId,
        $rolesSolicitados,
        $rolesPorNombre
    ): void {
        $rolNutricionistaId = $rolesPorNombre->get('Nutricionista');

        if (! $rolNutricionistaId) {
            return;
        }

        $tienePerfilNutricionista = DB::table('nutricionistas')
            ->where('cod_usuarios_nut', $usuarioId)
            ->exists();

        if (
            $tienePerfilNutricionista &&
            ! $rolesSolicitados->contains((int) $rolNutricionistaId)
        ) {
            $validator->errors()->add(
                'roles',
                'No puedes quitar el rol Nutricionista porque este usuario tiene un perfil profesional registrado.'
            );
        }
    }

    private function validarPerfilPacienteConRol(
        Validator $validator,
        int $usuarioId,
        $rolesSolicitados,
        $rolesPorNombre
    ): void {
        $rolPacienteId = $rolesPorNombre->get('Paciente');

        if (! $rolPacienteId) {
            return;
        }

        $tienePerfilPaciente = DB::table('pacientes')
            ->where('cod_usuarios_pac', $usuarioId)
            ->exists();

        if (
            $tienePerfilPaciente &&
            ! $rolesSolicitados->contains((int) $rolPacienteId)
        ) {
            $validator->errors()->add(
                'roles',
                'No puedes quitar el rol Paciente porque este usuario tiene un perfil clínico registrado.'
            );
        }
    }

    private function validarAdministradorActivo(
        Validator $validator,
        int $usuarioId,
        $rolesSolicitados,
        $rolesPorNombre
    ): void {
        $rolesAdministrativosIds = collect([
            $rolesPorNombre->get('SuperAdmin'),
            $rolesPorNombre->get('Administrador'),
        ])
            ->filter()
            ->map(fn ($rol) => (int) $rol)
            ->values();

        if ($rolesAdministrativosIds->isEmpty()) {
            return;
        }

        $usuarioEsAdminActivoActualmente = DB::table('usuarios')
            ->join('usuario_rol', 'usuarios.cod_usuarios', '=', 'usuario_rol.cod_usuarios_url')
            ->where('usuarios.cod_usuarios', $usuarioId)
            ->where('usuarios.est_usu', 'activo')
            ->whereIn('usuario_rol.cod_roles_url', $rolesAdministrativosIds)
            ->exists();

        if (! $usuarioEsAdminActivoActualmente) {
            return;
        }

        $usuarioQuedaActivo = $this->input('est_usu') === 'activo';

        $usuarioQuedaConRolAdministrativo = $rolesSolicitados
            ->intersect($rolesAdministrativosIds)
            ->isNotEmpty();

        if ($usuarioQuedaActivo && $usuarioQuedaConRolAdministrativo) {
            return;
        }

        $otrosAdministradoresActivos = DB::table('usuarios')
            ->join('usuario_rol', 'usuarios.cod_usuarios', '=', 'usuario_rol.cod_usuarios_url')
            ->where('usuarios.cod_usuarios', '<>', $usuarioId)
            ->where('usuarios.est_usu', 'activo')
            ->whereIn('usuario_rol.cod_roles_url', $rolesAdministrativosIds)
            ->distinct('usuarios.cod_usuarios')
            ->count('usuarios.cod_usuarios');

        if ($otrosAdministradoresActivos === 0) {
            $validator->errors()->add(
                'roles',
                'No puedes quitar o inactivar al último administrador activo del sistema.'
            );
        }
    }
}