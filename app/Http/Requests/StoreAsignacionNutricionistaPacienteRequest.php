<?php

namespace App\Http\Requests;

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