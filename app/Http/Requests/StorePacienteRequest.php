<?php

namespace App\Http\Requests;

use App\Models\Usuario;
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
        $tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        return [
            'cod_usuarios_pac' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'cod_usuarios')->where(fn ($query) => $query->where('est_usu', 'activo')),
                'unique:pacientes,cod_usuarios_pac',
            ],
            'sex_pac' => ['required', Rule::in(['masculino', 'femenino', 'otro'])],
            'pes_pac' => ['required', 'numeric', 'between:0.3,500'],
            'estatura_pac' => ['required', 'numeric', 'between:0.3,3'],
            'tip_san_pac' => ['nullable', Rule::in($tiposSangre)],
            'ale_pac' => ['nullable', 'string', 'max:1000'],
            'est_pac' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $usuarioId = $this->input('cod_usuarios_pac');
            $usuario = Usuario::with(['paciente', 'nutricionista'])->find($usuarioId);

            if (! $usuario) {
                return;
            }

            if (! $usuario->estaActivo()) {
                $validator->errors()->add('cod_usuarios_pac', 'El usuario debe estar activo.');
            }

            if ($usuario->nutricionista) {
                $validator->errors()->add('cod_usuarios_pac', 'Ese usuario ya está registrado como nutricionista.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'cod_usuarios_pac.required' => 'Debes seleccionar un usuario.',
            'cod_usuarios_pac.exists' => 'El usuario seleccionado no existe o no esta activo.',
            'cod_usuarios_pac.unique' => 'Ese usuario ya está registrado como paciente.',
            'sex_pac.required' => 'El sexo es obligatorio.',
            'pes_pac.required' => 'El peso es obligatorio.',
            'pes_pac.between' => 'El peso debe estar entre 0.3 y 500 kg.',
            'estatura_pac.required' => 'La estatura es obligatoria.',
            'estatura_pac.between' => 'La estatura debe estar entre 0.3 y 3 metros.',
            'tip_san_pac.in' => 'El tipo de sangre seleccionado no es valido.',
            'ale_pac.max' => 'La descripcion de alergias no debe superar los 1000 caracteres.',
            'est_pac.required' => 'El estado del paciente es obligatorio.',
        ];
    }
}