<?php

namespace App\Http\Requests;

use App\Models\ValidacionInformacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValidacionInformacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'obs_vin' => trim((string) $this->input('obs_vin')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'fec_vin' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'est_vin' => [
                'required',
                Rule::in(ValidacionInformacion::ESTADOS),
            ],

            'obs_vin' => [
                'nullable',
                'string',
                'max:1500',
                Rule::requiredIf(fn () => in_array(
                    $this->input('est_vin'),
                    ValidacionInformacion::ESTADOS_CON_OBSERVACION_OBLIGATORIA,
                    true
                )),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fec_vin.required' => 'La fecha de validación es obligatoria.',
            'fec_vin.date' => 'La fecha de validación debe ser válida.',
            'fec_vin.before_or_equal' => 'La fecha de validación no puede ser futura.',

            'est_vin.required' => 'El estado de validación es obligatorio.',
            'est_vin.in' => 'El estado de validación no es válido.',

            'obs_vin.required' => 'Debes registrar una observación cuando el estado sea observado o rechazado.',
            'obs_vin.max' => 'La observación no debe superar los 1500 caracteres.',
        ];
    }
}