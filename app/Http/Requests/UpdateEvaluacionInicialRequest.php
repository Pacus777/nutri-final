<?php

namespace App\Http\Requests;

use App\Models\EvaluacionInicial;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluacionInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'peso_evi' => $this->normalizarDecimal($this->input('peso_evi')),
            'estatura_evi' => $this->normalizarEstatura($this->input('estatura_evi')),
        ]);
    }

    public function rules(): array
    {
        return [
            'fec_evi' => [
                'required',
                'date',
            ],

            'peso_evi' => [
                'required',
                'numeric',
                'min:20',
                'max:300',
            ],

            'estatura_evi' => [
                'required',
                'numeric',
                'min:0.50',
                'max:2.50',
            ],

            'imc_evi' => [
                'nullable',
                'numeric',
                'min:0',
                'max:150',
            ],

            'objetivo_inicial_evi' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'restricciones_evi' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'contexto_general_evi' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'obs_evi' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $evaluacion = $this->evaluacionDesdeRuta();

            if ($evaluacion && ! $evaluacion->puedeEditarse()) {
                $validator->errors()->add(
                    'evaluacion',
                    'No puedes editar una evaluación inicial cerrada.'
                );
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'fec_evi.required' => 'La fecha de evaluación es obligatoria.',
            'fec_evi.date' => 'La fecha debe ser válida.',

            'peso_evi.required' => 'El peso inicial es obligatorio.',
            'peso_evi.numeric' => 'El peso debe ser un número válido.',
            'peso_evi.min' => 'El peso debe ser mayor o igual a 20 kg.',
            'peso_evi.max' => 'El peso no puede ser mayor a 300 kg.',

            'estatura_evi.required' => 'La estatura inicial es obligatoria.',
            'estatura_evi.numeric' => 'La estatura debe ser un número válido.',
            'estatura_evi.min' => 'La estatura debe ser mayor o igual a 0.50 m.',
            'estatura_evi.max' => 'La estatura no puede ser mayor a 2.50 m.',

            'objetivo_inicial_evi.max' => 'El objetivo inicial no debe superar los 1000 caracteres.',
            'restricciones_evi.max' => 'Las restricciones no deben superar los 1000 caracteres.',
            'contexto_general_evi.max' => 'El contexto general no debe superar los 2000 caracteres.',
            'obs_evi.max' => 'Las observaciones no deben superar los 2000 caracteres.',
        ];
    }

    private function evaluacionDesdeRuta(): ?EvaluacionInicial
    {
        $evaluacion = $this->route('evaluacionInicial')
            ?? $this->route('evaluacion')
            ?? $this->route('objetivoNutricional');

        return $evaluacion instanceof EvaluacionInicial
            ? $evaluacion
            : null;
    }

    private function normalizarDecimal(mixed $valor): mixed
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        return str_replace(',', '.', (string) $valor);
    }

    private function normalizarEstatura(mixed $valor): mixed
    {
        $valor = $this->normalizarDecimal($valor);

        if ($valor === null || $valor === '') {
            return $valor;
        }

        $numero = (float) $valor;

        /*
         * Si el usuario escribe 170, lo convertimos a 1.70.
         * En la base de datos guardaremos estatura en metros.
         */
        if ($numero > 3) {
            return round($numero / 100, 2);
        }

        return $valor;
    }
}