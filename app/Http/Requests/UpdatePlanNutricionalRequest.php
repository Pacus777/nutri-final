<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class UpdatePlanNutricionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fec_pan' => ['required', 'date'],
            'des_pan' => ['required', 'string', 'min:10', 'max:2000'],
            'est_pan' => ['required', Rule::in(['activo', 'vigente', 'pausado', 'finalizado', 'reemplazado', 'cancelado'])],
        ];
    }

    public function messages(): array
    {
        return [
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
            $plan = $this->route('planNutricional') ?? $this->route('plan');

            if ($plan && $plan->est_pan === 'finalizado' && $this->est_pan !== 'finalizado') {
                if (!auth()->user()->roles->pluck('nom_rol')->contains('Administrador')) {
                    $validator->errors()->add('est_pan', 'No puedes cambiar el estado de un plan finalizado.');
                }
            }

            // Check for duplicate active plan
            if ($plan && in_array($this->est_pan, ['activo', 'vigente'])) {
                $planActivo = \App\Models\PlanNutricional::where('cod_pacientes_pan', $plan->cod_pacientes_pan)
                    ->where('cod_planes_nutricionales', '!=', $plan->cod_planes_nutricionales)
                    ->whereIn('est_pan', ['activo', 'vigente'])
                    ->exists();

                if ($planActivo) {
                    $validator->errors()->add('est_pan', 'El paciente ya tiene otro plan activo o vigente.');
                }
            }
        });

        return $validator;
    }
}
