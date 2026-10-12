<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaEdadNacimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacienteRequest extends FormRequest
{
    use ValidaEdadNacimiento;

    protected array $extensionesCi = [
        'LP', 'CB', 'SC', 'OR', 'PT', 'TJ', 'CH', 'BE', 'PD', 'BN', 'EX',
    ];

    protected array $tiposSangre = [
        'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paciente = $this->route('paciente');
        $usuarioId = $paciente->usuario?->cod_usuarios;

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

            'sex_pac' => ['required', Rule::in(['masculino', 'femenino', 'otro'])],
            'pes_pac' => ['required', 'numeric', 'between:0.3,500'],
            'estatura_pac' => ['required', 'numeric', 'between:0.3,3'],
            'tip_san_pac' => ['nullable', Rule::in($this->tiposSangre)],
            'ale_pac' => ['nullable', 'string', 'max:1000'],
            'est_pac' => ['required', Rule::in(['activo', 'inactivo'])],
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
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.between' => 'El peso debe estar entre 0.3 y 500 kg.',
            'estatura_pac.required' => 'La estatura es obligatoria.',
            'estatura_pac.between' => 'La estatura debe estar entre 0.3 y 3 metros.',
            'tip_san_pac.in' => 'El tipo de sangre seleccionado no es válido.',
            'ale_pac.max' => 'La descripción de alergias no debe superar los 1000 caracteres.',
            'est_pac.required' => 'El estado del perfil es obligatorio.',
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