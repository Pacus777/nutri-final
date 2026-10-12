<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
                'unique:usuarios,cor_usu',
            ],

            'cla_usu' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'est_usu' => [
                'required',
                Rule::in(['activo', 'inactivo', 'bloqueado']),
            ],

            // Tipo de registro
            'tipo_registro' => [
                'required',
                Rule::in(['Administrador', 'Nutricionista', 'Paciente']),
            ],

            // Roles múltiples, para uso futuro
            'roles' => [
                'nullable',
                'array',
            ],

            'roles.*' => [
                'integer',
                'exists:roles,cod_roles',
            ],

            // Perfil nutricionista
            'esp_nut' => [
                'required_if:tipo_registro,Nutricionista',
                'nullable',
                'string',
                'max:150',
            ],

            'est_nut' => [
                'required_if:tipo_registro,Nutricionista',
                'nullable',
                Rule::in(['activo', 'inactivo']),
            ],

            // Perfil paciente
            'sex_pac' => [
                'required_if:tipo_registro,Paciente',
                'nullable',
                Rule::in(['masculino', 'femenino', 'otro']),
            ],

            'pes_pac' => [
                'required_if:tipo_registro,Paciente',
                'nullable',
                'numeric',
                'min:0.01',
                'max:999.99',
            ],

            'estatura_pac' => [
                'required_if:tipo_registro,Paciente',
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
                'required_if:tipo_registro,Paciente',
                'nullable',
                Rule::in(['activo', 'inactivo']),
            ],
        ];
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

            'cla_usu.required' => 'La contraseña es obligatoria.',
            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',

            'est_usu.required' => 'El estado del usuario es obligatorio.',
            'est_usu.in' => 'El estado del usuario seleccionado no es válido.',

            // Tipo de registro
            'tipo_registro.required' => 'El tipo de registro es obligatorio.',
            'tipo_registro.in' => 'El tipo de registro seleccionado no es válido.',

            // Perfil nutricionista
            'esp_nut.required_if' => 'La especialidad es obligatoria para registrar un nutricionista.',
            'est_nut.required_if' => 'El estado del perfil nutricionista es obligatorio.',
            'est_nut.in' => 'El estado del perfil nutricionista no es válido.',

            // Perfil paciente
            'sex_pac.required_if' => 'El sexo es obligatorio para registrar un paciente.',
            'sex_pac.in' => 'El sexo seleccionado no es válido.',

            'pes_pac.required_if' => 'El peso es obligatorio para registrar un paciente.',
            'pes_pac.numeric' => 'El peso debe ser un valor numérico.',
            'pes_pac.min' => 'El peso debe ser mayor a cero.',
            'pes_pac.max' => 'El peso ingresado es demasiado alto.',

            'estatura_pac.required_if' => 'La estatura es obligatoria para registrar un paciente.',
            'estatura_pac.numeric' => 'La estatura debe ser un valor numérico.',
            'estatura_pac.min' => 'La estatura debe ser mayor a cero.',
            'estatura_pac.max' => 'La estatura ingresada no es válida.',

            'tip_san_pac.in' => 'El tipo de sangre seleccionado no es válido.',
            'ale_pac.max' => 'Las alergias no deben superar los 1000 caracteres.',

            'est_pac.required_if' => 'El estado del perfil paciente es obligatorio.',
            'est_pac.in' => 'El estado del perfil paciente no es válido.',

            // Roles
            'roles.array' => 'Los roles deben enviarse como una lista.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }
}