<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaEdadNacimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNutricionistaRequest extends FormRequest
{
    use ValidaEdadNacimiento;

    protected array $extensionesCi = [
        'LP', 'CB', 'SC', 'OR', 'PT', 'TJ', 'CH', 'BE', 'PD', 'BN', 'EX',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nutricionista = $this->route('nutricionista');
        $usuarioId = $nutricionista->usuario?->cod_usuarios;

        return [
            'nom_usu' => ['required', 'string', 'max:120', 'regex:/^[\pL\s]+$/u'],
            'ape_usu' => ['required', 'string', 'max:120', 'regex:/^[\pL\s]+$/u'],
            'ci_usu' => ['required', 'string', 'regex:/^\d{8,12}$/'],
            'ext_ci_usu' => ['required', Rule::in($this->extensionesCi)],
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
            'esp_nut' => ['nullable', 'string', 'max:100'],
            'est_nut' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_usu.required' => 'Los nombres son obligatorios.',
            'nom_usu.regex' => 'Los nombres solo deben contener letras.',
            'ape_usu.required' => 'Los apellidos son obligatorios.',
            'ape_usu.regex' => 'Los apellidos solo deben contener letras.',
            'ci_usu.required' => 'El CI es obligatorio.',
            'ci_usu.regex' => 'El CI debe contener solo números, entre 8 y 12 dígitos.',
            'ext_ci_usu.required' => 'La extensión del CI es obligatoria.',
            'ext_ci_usu.in' => 'La extensión del CI no es válida.',
            'fec_nac_usu.required' => 'La fecha de nacimiento es obligatoria.',
            'fec_nac_usu.min_age' => 'No puede ser menor a 18 años.',
            'fec_nac_usu.max_age' => 'No puede ser mayor a 50 años.',
            'cor_usu.required' => 'El correo es obligatorio.',
            'cor_usu.email' => 'El correo debe tener un formato válido.',
            'cor_usu.unique' => 'Este correo ya está registrado.',
            'cla_usu.confirmed' => 'La confirmación de contraseña no coincide.',
            'cla_usu.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'est_usu.required' => 'El estado del usuario es obligatorio.',
            'esp_nut.required' => 'La especialidad es obligatoria.',
            'esp_nut.max' => 'La especialidad no debe superar los 100 caracteres.',
            'est_nut.required' => 'El estado del perfil es obligatorio.',
            'fot_usu.image' => 'La foto debe ser una imagen válida.',
            'fot_usu.max' => 'La foto no debe superar los 2 MB.',
        ];
    }

    public function withValidator($validator)
    {
        $this->agregarValidacionEdadNacimiento($validator);

        return $validator;
    }
}