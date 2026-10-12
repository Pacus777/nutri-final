<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_usuarios_url' => ['required', 'integer', 'exists:usuarios,cod_usuarios'],
            'cod_roles_url' => [
                'required',
                'integer',
                Rule::exists('roles', 'cod_roles')->where(function ($query) {
                    $query->where('est_rol', 'activo');
                }),
                Rule::unique('usuario_rol', 'cod_roles_url')->where(function ($query) {
                    $usuario = $this->input('cod_usuarios_url');

                    return $query->where('cod_usuarios_url', $usuario);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_usuarios_url.required' => 'El usuario es obligatorio.',
            'cod_usuarios_url.exists' => 'El usuario seleccionado no existe.',
            'cod_roles_url.required' => 'El rol es obligatorio.',
            'cod_roles_url.exists' => 'El rol seleccionado no existe o está inactivo.',
            'cod_roles_url.unique' => 'El usuario ya tiene asignado ese rol.',
        ];
    }
}
