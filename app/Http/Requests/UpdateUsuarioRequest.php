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
            'ape_usu' => ['required', 'string', 'max:120'],
            'ci_usu' => ['required', 'string', 'max:30'],
            'ext_ci_usu' => ['required', 'string', 'max:20'],
            'fec_nac_usu' => ['required', 'date'],
            'fot_usu' => ['nullable', 'image', 'max:2048'],
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
            $rules['sex_pac'] = ['required', Rule::in(['masculino', 'femenino', 'otro'])];
            $rules['pes_pac'] = ['required', 'numeric', 'gt:0'];
            $rules['estatura_pac'] = ['required', 'numeric', 'gt:0'];
            $rules['tip_san_pac'] = ['nullable', 'string', 'max:20'];
            $rules['ale_pac'] = ['nullable', 'string'];
            $rules['est_pac'] = ['required', Rule::in(['activo', 'inactivo'])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom_usu.required' => 'Los nombres son obligatorios.',
            'ape_usu.required' => 'Los apellidos son obligatorios.',
            'ci_usu.required' => 'El CI es obligatorio.',
            'ext_ci_usu.required' => 'La extensión del CI es obligatoria.',
            'fec_nac_usu.required' => 'La fecha de nacimiento es obligatoria.',
            'cor_usu.required' => 'El correo es obligatorio.',
            'cor_usu.email' => 'El correo debe tener un formato válido.',
            'cor_usu.unique' => 'Este correo ya está registrado.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',
            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'roles.required' => 'Debes asignar al menos un rol.',
            'roles.min' => 'Debes asignar al menos un rol.',
            'esp_nut.max' => 'La especialidad no debe superar los 100 caracteres.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.gt' => 'El peso debe ser mayor que cero.',
            'estatura_pac.required' => 'La estatura es obligatoria.',
            'estatura_pac.gt' => 'La estatura debe ser mayor que cero.',
            'tip_san_pac.max' => 'El tipo de sangre no debe superar los 20 caracteres.',
            'fot_usu.image' => 'La foto debe ser una imagen válida.',
            'fot_usu.max' => 'La foto no debe superar los 2 MB.',
        ];
    }
}