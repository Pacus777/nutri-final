<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNutricionistaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nutricionista = $this->route('nutricionista');
        $nutricionistaId = is_object($nutricionista)
            ? $nutricionista->cod_nutricionistas
            : $nutricionista;

        return [
            'cod_usuarios_nut' => [
                'required',
                'integer',
                'exists:usuarios,cod_usuarios',
                Rule::unique('nutricionistas', 'cod_usuarios_nut')
                    ->ignore($nutricionistaId, 'cod_nutricionistas'),
            ],
            'esp_nut' => ['nullable', 'string', 'max:100'],
            'est_nut' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_usuarios_nut.required' => 'Debes seleccionar un usuario.',
            'cod_usuarios_nut.exists' => 'El usuario seleccionado no existe.',
            'cod_usuarios_nut.unique' => 'Ese usuario ya está registrado como nutricionista.',
            'esp_nut.max' => 'La especialidad no debe superar los 100 caracteres.',
            'est_nut.required' => 'El estado del nutricionista es obligatorio.',
        ];
    }
}