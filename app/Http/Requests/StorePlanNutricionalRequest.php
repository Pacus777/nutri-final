<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class StorePlanNutricionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_pacientes_pan' => ['required', 'integer', 'exists:pacientes,cod_pacientes'],
            'cod_nutricionistas_pan' => ['required', 'integer', 'exists:nutricionistas,cod_nutricionistas'],
            'fec_pan' => ['required', 'date'],
            'des_pan' => ['required', 'string', 'min:10', 'max:2000'],
            'est_pan' => ['required', Rule::in(['activo', 'vigente', 'pausado', 'finalizado', 'reemplazado', 'cancelado'])],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_pan.required' => 'Debes seleccionar un paciente.',
            'cod_pacientes_pan.exists' => 'El paciente seleccionado no existe.',
            'cod_nutricionistas_pan.required' => 'Debes seleccionar un nutricionista.',
            'cod_nutricionistas_pan.exists' => 'El nutricionista seleccionado no existe.',
            'fec_pan.required' => 'La fecha del plan es obligatoria.',
            'fec_pan.date' => 'La fecha debe ser una fecha válida.',
            'des_pan.required' => 'La descripción del plan es obligatoria.',
            'des_pan.min' => 'La descripción debe tener al menos 10 caracteres.',
            'est_pan.required' => 'El estado del plan es obligatorio.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $paciente = \App\Models\Paciente::find($this->cod_pacientes_pan);
            $nutricionista = \App\Models\Nutricionista::find($this->cod_nutricionistas_pan);

            if ($paciente && !$paciente->estaActivo()) {
                $validator->errors()->add('cod_pacientes_pan', 'El paciente debe estar activo.');
            }

            if ($nutricionista && !$nutricionista->estaActivo()) {
                $validator->errors()->add('cod_nutricionistas_pan', 'El nutricionista debe estar activo.');
            }

            if ($paciente && $nutricionista && !$paciente->tieneAsignacionActivaCon($nutricionista->cod_nutricionistas)) {
                if (!auth()->user()->roles->pluck('nom_rol')->contains('Administrador')) {
                    $validator->errors()->add('cod_nutricionistas_pan', 'El paciente no está asignado a este nutricionista.');
                }
            }

            // Check for existing active plan if creating a new active plan
            if ($paciente && in_array($this->est_pan, ['activo', 'vigente'])) {
                $planActivo = \App\Models\PlanNutricional::where('cod_pacientes_pan', $paciente->cod_pacientes)
                    ->whereIn('est_pan', ['activo', 'vigente'])
                    ->exists();

                if ($planActivo) {
                    $validator->errors()->add('est_pan', 'El paciente ya tiene un plan activo o vigente. Debes cerrar o reemplazar el anterior.');
                }
            }
        });

        return $validator;
    }
}
