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
        $usuarioId = $this->route('usuario');

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
}