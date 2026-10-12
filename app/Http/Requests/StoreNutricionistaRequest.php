<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNutricionistaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_usuarios_nut' => [
                'required',
                'integer',
                'exists:usuarios,cod_usuarios',
                'unique:nutricionistas,cod_usuarios_nut',
            ],
            'esp_nut' => ['nullable', 'string', 'max:100'],
            'est_nut' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $usuarioId = $this->input('cod_usuarios_nut');
            $usuario = Usuario::with(['nutricionista', 'paciente'])->find($usuarioId);

            if (! $usuario) {
                return;
            }

            if (! $usuario->estaActivo()) {
                $validator->errors()->add('cod_usuarios_nut', 'El usuario debe estar activo.');
            }

            if ($usuario->paciente) {
                $validator->errors()->add('cod_usuarios_nut', 'Ese usuario ya está registrado como paciente.');
            }
        });
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