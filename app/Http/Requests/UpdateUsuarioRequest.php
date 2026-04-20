<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $usuarioId = is_object($usuario) ? $usuario->cod_usuarios : $usuario;

        $rules = [
            'nom_usu' => ['required', 'string', 'max:120'],
            'cor_usu' => [
                'required',
                'string',
                'email',
                'max:120',
                Rule::unique('usuarios', 'cor_usu')->ignore($usuarioId, 'cod_usuarios'),
            ],
            'cla_usu' => ['nullable', 'string', 'min:8', 'confirmed'],
            'est_usu' => ['required', Rule::in(['activo', 'inactivo', 'bloqueado'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,cod_roles'],
        ];

        if ($this->boolean('tiene_perfil_nutricionista')) {
            $rules['esp_nut'] = ['nullable', 'string', 'max:100'];
            $rules['est_nut'] = ['required', Rule::in(['activo', 'inactivo'])];
        }

        if ($this->boolean('tiene_perfil_paciente')) {
            $rules['fec_pac'] = ['required', 'date'];
            $rules['eda_pac'] = ['required', 'integer', 'min:1'];
            $rules['sex_pac'] = ['required', Rule::in(['masculino', 'femenino', 'otro'])];
            $rules['pes_pac'] = ['required', 'numeric', 'gt:0'];
            $rules['tal_pac'] = ['required', 'numeric', 'gt:0'];
            $rules['est_pac'] = ['required', Rule::in(['activo', 'inactivo'])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom_usu.required' => 'El nombre es obligatorio.',
            'cor_usu.required' => 'El correo es obligatorio.',
            'cor_usu.email' => 'El correo debe tener un formato válido.',
            'cor_usu.unique' => 'Este correo ya está registrado.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',
            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'est_usu.required' => 'El estado del usuario es obligatorio.',
            'roles.required' => 'Debes asignar al menos un rol.',
            'roles.min' => 'Debes asignar al menos un rol.',
            'esp_nut.max' => 'La especialidad no debe superar los 100 caracteres.',
            'fec_pac.required' => 'La fecha de registro del paciente es obligatoria.',
            'eda_pac.required' => 'La edad es obligatoria.',
            'eda_pac.min' => 'La edad debe ser mayor que cero.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.gt' => 'El peso debe ser mayor que cero.',
            'tal_pac.required' => 'La talla es obligatoria.',
            'tal_pac.gt' => 'La talla debe ser mayor que cero.',
        ];
    }
}