<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_usuarios_pac' => [
                'required',
                'integer',
                'exists:usuarios,cod_usuarios',
                'unique:pacientes,cod_usuarios_pac',
            ],
            'fec_pac' => ['required', 'date'],
            'eda_pac' => ['required', 'integer', 'min:1'],
            'sex_pac' => ['required', Rule::in(['masculino', 'femenino', 'otro'])],
            'pes_pac' => ['required', 'numeric', 'gt:0'],
            'tal_pac' => ['required', 'numeric', 'gt:0'],
            'est_pac' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_usuarios_pac.required' => 'Debes seleccionar un usuario.',
            'cod_usuarios_pac.exists' => 'El usuario seleccionado no existe.',
            'cod_usuarios_pac.unique' => 'Ese usuario ya está registrado como paciente.',
            'fec_pac.required' => 'La fecha de registro es obligatoria.',
            'eda_pac.required' => 'La edad es obligatoria.',
            'eda_pac.min' => 'La edad debe ser mayor que cero.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.gt' => 'El peso debe ser mayor que cero.',
            'tal_pac.required' => 'La talla es obligatoria.',
            'tal_pac.gt' => 'La talla debe ser mayor que cero.',
            'est_pac.required' => 'El estado del paciente es obligatorio.',
        ];
    }
}