<?php

namespace App\Http\Requests;

use App\Models\ContextoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContextoPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_cxp' => $this->input('est_cxp') ?: ContextoPaciente::ESTADO_ACTIVO,
            'pri_cxp' => $this->input('pri_cxp') ?: ContextoPaciente::PRIORIDAD_MEDIA,
        ]);
    }

    public function rules(): array
    {
        return [
            'des_cxp' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],

            'fec_cxp' => [
                'required',
                'date',
            ],

            'tip_cxp' => [
                'required',
                'string',
                Rule::in(ContextoPaciente::TIPOS),
            ],

            'pri_cxp' => [
                'required',
                'string',
                Rule::in(ContextoPaciente::PRIORIDADES),
            ],

            'est_cxp' => [
                'required',
                Rule::in(ContextoPaciente::ESTADOS),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $contexto = $this->contextoDesdeRuta();

            if ($contexto && $contexto->estaInactivo() && $this->input('est_cxp') === ContextoPaciente::ESTADO_INACTIVO) {
                $validator->errors()->add(
                    'contexto',
                    'No puedes editar un contexto inactivo. Actívalo o registra un nuevo contexto si corresponde.'
                );
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'des_cxp.required' => 'La descripción del contexto es obligatoria.',
            'des_cxp.min' => 'La descripción debe tener al menos 3 caracteres.',
            'des_cxp.max' => 'La descripción no debe superar los 1000 caracteres.',

            'fec_cxp.required' => 'La fecha del contexto es obligatoria.',
            'fec_cxp.date' => 'La fecha debe ser una fecha válida.',

            'tip_cxp.required' => 'Debes seleccionar el tipo de contexto clínico.',
            'tip_cxp.in' => 'El tipo de contexto clínico no es válido.',

            'pri_cxp.required' => 'Debes seleccionar la prioridad clínica.',
            'pri_cxp.in' => 'La prioridad clínica no es válida.',

            'est_cxp.required' => 'El estado del contexto es obligatorio.',
            'est_cxp.in' => 'El estado del contexto no es válido.',
        ];
    }

    private function contextoDesdeRuta(): ?ContextoPaciente
    {
        $contexto = $this->route('contextoPaciente')
            ?? $this->route('contexto');

        return $contexto instanceof ContextoPaciente
            ? $contexto
            : null;
    }
}