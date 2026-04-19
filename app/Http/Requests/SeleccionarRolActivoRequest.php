<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeleccionarRolActivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'cod_rol' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_rol.required' => 'Debes seleccionar un rol.',
            'cod_rol.integer' => 'El rol seleccionado no es válido.',
        ];
    }
}