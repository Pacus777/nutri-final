<?php

namespace App\Http\Requests;

use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsignacionNutricionistaPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_nutricionistas_anp' => ['required', 'integer', 'exists:nutricionistas,cod_nutricionistas'],
            'cod_pacientes_anp' => ['required', 'integer', 'exists:pacientes,cod_pacientes'],
            'fec_anp' => ['required', 'date'],
            'est_anp' => ['required', Rule::in(['activa', 'cerrada', 'reasignada'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $pacienteId = $this->input('cod_pacientes_anp');
            $nutricionistaId = $this->input('cod_nutricionistas_anp');

            $paciente = Paciente::find($pacienteId);
            $nutricionista = Nutricionista::find($nutricionistaId);

            if ($paciente && !$paciente->estaActivo()) {
                $validator->errors()->add('cod_pacientes_anp', 'El paciente debe estar activo.');
            }

            if ($nutricionista && !$nutricionista->estaActivo()) {
                $validator->errors()->add('cod_nutricionistas_anp', 'El nutricionista debe estar activo.');
            }

            if ($paciente && $paciente->asignacionActiva()->exists()) {
                $activa = $paciente->asignacionActiva()->first();

                if ($activa && (int) $activa->cod_nutricionistas_anp === (int) $nutricionistaId) {
                    $validator->errors()->add('cod_nutricionistas_anp', 'El paciente ya tiene una asignación activa con ese nutricionista.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'cod_nutricionistas_anp.required' => 'Debes seleccionar un nutricionista.',
            'cod_nutricionistas_anp.exists' => 'El nutricionista seleccionado no existe.',
            'cod_pacientes_anp.required' => 'Debes seleccionar un paciente.',
            'cod_pacientes_anp.exists' => 'El paciente seleccionado no existe.',
            'fec_anp.required' => 'La fecha de asignación es obligatoria.',
            'est_anp.required' => 'El estado de la asignación es obligatorio.',
        ];
    }
}