<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNutricionistaRapidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_usu' => ['required', 'string', 'max:120'],
            'cor_usu' => ['required', 'string', 'email', 'max:120', 'unique:usuarios,cor_usu'],
            'cla_usu' => ['required', 'string', 'min:8', 'confirmed'],
            'est_usu' => ['required', Rule::in(['activo', 'inactivo', 'bloqueado'])],
            'esp_nut' => ['nullable', 'string', 'max:100'],
            'est_nut' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
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
            'esp_nut.max' => 'La especialidad no debe superar los 100 caracteres.',
            'est_nut.required' => 'El estado del perfil de nutricionista es obligatorio.',
        ];
    }
}