<?php

namespace App\Http\Requests;

use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;

class ReasignarPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_pacientes_anp' => ['required', 'integer', 'exists:pacientes,cod_pacientes'],
            'cod_nutricionistas_anp' => ['required', 'integer', 'exists:nutricionistas,cod_nutricionistas'],
            'fec_anp' => ['required', 'date'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $paciente = Paciente::find($this->input('cod_pacientes_anp'));
            $nutricionista = Nutricionista::find($this->input('cod_nutricionistas_anp'));

            if ($paciente && !$paciente->estaActivo()) {
                $validator->errors()->add('cod_pacientes_anp', 'El paciente debe estar activo.');
            }

            if ($nutricionista && !$nutricionista->estaActivo()) {
                $validator->errors()->add('cod_nutricionistas_anp', 'El nutricionista debe estar activo.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_anp.required' => 'Debes seleccionar un paciente.',
            'cod_nutricionistas_anp.required' => 'Debes seleccionar un nutricionista.',
            'fec_anp.required' => 'La fecha es obligatoria.',
            'observacion.max' => 'La observación no debe superar los 1000 caracteres.',
        ];
    }
}
