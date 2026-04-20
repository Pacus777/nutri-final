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
            'sex_pac' => ['required', Rule::in(['masculino', 'femenino', 'otro'])],
            'pes_pac' => ['required', 'numeric', 'gt:0'],
            'estatura_pac' => ['required', 'numeric', 'gt:0'],
            'tip_san_pac' => ['nullable', 'string', 'max:20'],
            'ale_pac' => ['nullable', 'string'],
            'est_pac' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_usuarios_pac.required' => 'Debes seleccionar un usuario.',
            'cod_usuarios_pac.exists' => 'El usuario seleccionado no existe.',
            'cod_usuarios_pac.unique' => 'Ese usuario ya está registrado como paciente.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.gt' => 'El peso debe ser mayor que cero.',
            'estatura_pac.required' => 'La estatura es obligatoria.',
            'estatura_pac.gt' => 'La estatura debe ser mayor que cero.',
            'tip_san_pac.max' => 'El tipo de sangre no debe superar los 20 caracteres.',
            'est_pac.required' => 'El estado del paciente es obligatorio.',
        ];
    }
}