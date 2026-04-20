<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paciente = $this->route('paciente');
        $usuarioId = $paciente->usuario?->cod_usuarios;

        return [
            'nom_usu' => ['required', 'string', 'max:120'],
            'ape_usu' => ['required', 'string', 'max:120'],
            'ci_usu' => ['required', 'string', 'max:30'],
            'ext_ci_usu' => ['required', 'string', 'max:20'],
            'fec_nac_usu' => ['required', 'date'],
            'fot_usu' => ['nullable', 'image', 'max:2048'],
            'cor_usu' => [
                'required',
                'string',
                'email',
                'max:120',
                Rule::unique('usuarios', 'cor_usu')->ignore($usuarioId, 'cod_usuarios'),
            ],
            'cla_usu' => ['nullable', 'string', 'min:8', 'confirmed'],
            'est_usu' => ['required', Rule::in(['activo', 'inactivo', 'bloqueado'])],

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
            'nom_usu.required' => 'Los nombres son obligatorios.',
            'ape_usu.required' => 'Los apellidos son obligatorios.',
            'ci_usu.required' => 'El CI es obligatorio.',
            'ext_ci_usu.required' => 'La extensión del CI es obligatoria.',
            'fec_nac_usu.required' => 'La fecha de nacimiento es obligatoria.',
            'cor_usu.required' => 'El correo es obligatorio.',
            'cor_usu.email' => 'El correo debe tener un formato válido.',
            'cor_usu.unique' => 'Este correo ya está registrado.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',
            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.gt' => 'El peso debe ser mayor que cero.',
            'estatura_pac.required' => 'La estatura es obligatoria.',
            'estatura_pac.gt' => 'La estatura debe ser mayor que cero.',
            'tip_san_pac.max' => 'El tipo de sangre no debe superar los 20 caracteres.',
            'est_pac.required' => 'El estado del perfil es obligatorio.',
            'fot_usu.image' => 'La foto debe ser una imagen válida.',
            'fot_usu.max' => 'La foto no debe superar los 2 MB.',
        ];
    }
}