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
        $rules = [
            'nom_usu' => ['required', 'string', 'max:120'],
            'cor_usu' => ['required', 'string', 'email', 'max:120', 'unique:usuarios,cor_usu'],
            'cla_usu' => ['required', 'string', 'min:8', 'confirmed'],
            'est_usu' => ['required', Rule::in(['activo', 'inactivo', 'bloqueado'])],
            'tipo_registro' => ['required', Rule::in(['Administrador', 'Nutricionista', 'Paciente'])],
        ];

        if ($this->tipo_registro === 'Nutricionista') {
            $rules['esp_nut'] = ['nullable', 'string', 'max:100'];
            $rules['est_nut'] = ['required', Rule::in(['activo', 'inactivo'])];
        }

        if ($this->tipo_registro === 'Paciente') {
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
            'cla_usu.required' => 'La contraseña es obligatoria.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',
            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'est_usu.required' => 'El estado del usuario es obligatorio.',
            'tipo_registro.required' => 'Debes seleccionar un tipo de registro.',
            'esp_nut.max' => 'La especialidad no debe superar los 100 caracteres.',
            'fec_pac.required' => 'La fecha de registro del paciente es obligatoria.',
            'eda_pac.required' => 'La edad es obligatoria.',
            'eda_pac.min' => 'La edad debe ser mayor que cero.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.gt' => 'El peso debe ser mayor que cero.',
            'tal_pac.required' => 'La talla es obligatoria.',
            'tal_pac.gt' => 'La talla debe ser mayor que cero.',
            'est_nut.required' => 'El estado del perfil de nutricionista es obligatorio.',
            'est_pac.required' => 'El estado del perfil del paciente es obligatorio.',
        ];
    }
}