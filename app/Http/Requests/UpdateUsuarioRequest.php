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

        return [
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
            'est_usu.required' => 'El estado es obligatorio.',
            'roles.required' => 'Debes asignar al menos un rol.',
            'roles.min' => 'Debes asignar al menos un rol.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }
}