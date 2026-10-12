<?php

namespace App\Http\Requests;

use App\Models\ObjetivoNutricional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateObjetivoNutricionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_obe' => $this->input('est_obe') ?: ObjetivoNutricional::ESTADO_ACTIVO,
        ]);
    }

    public function rules(): array
    {
        return [
            'des_obe' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],

            'fec_obe' => [
                'required',
                'date',
            ],

            'est_obe' => [
                'required',
                Rule::in(ObjetivoNutricional::ESTADOS),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $objetivo = $this->objetivoDesdeRuta();

            if (! $objetivo) {
                return;
            }

            if ($objetivo->esTerminal() && $this->input('est_obe') !== $objetivo->est_obe) {
                $validator->errors()->add(
                    'est_obe',
                    'No puedes cambiar el estado de un objetivo cumplido, cerrado o cancelado.'
                );
            }

            if ($objetivo->esTerminal()) {
                $validator->errors()->add(
                    'objetivo',
                    'No puedes editar un objetivo cumplido, cerrado o cancelado.'
                );
            }

            if ($this->rolActivoEsNutricionista()) {
                $this->validarRedaccionClinicaObjetivo($validator);
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'des_obe.required' => 'La descripción del objetivo es obligatoria.',
            'des_obe.min' => 'La descripción debe tener al menos 5 caracteres.',
            'des_obe.max' => 'La descripción no debe superar los 1000 caracteres.',

            'fec_obe.required' => 'La fecha del objetivo es obligatoria.',
            'fec_obe.date' => 'La fecha debe ser una fecha válida.',

            'est_obe.required' => 'El estado del objetivo es obligatorio.',
            'est_obe.in' => 'El estado del objetivo no es válido.',
        ];
    }

    private function objetivoDesdeRuta(): ?ObjetivoNutricional
    {
        $objetivo = $this->route('objetivoNutricional')
            ?? $this->route('objetivo');

        return $objetivo instanceof ObjetivoNutricional
            ? $objetivo
            : null;
    }

    private function rolActivoEsNutricionista(): bool
    {
        return trim((string) session('nom_rol_activo', session('rol_activo'))) === 'Nutricionista';
    }

    private function validarRedaccionClinicaObjetivo($validator): void
    {
        $descripcion = trim((string) $this->input('des_obe'));
        $descripcionNormalizada = mb_strtolower($descripcion);

        if (mb_strlen($descripcion) < 40) {
            $validator->errors()->add(
                'des_obe',
                'El objetivo nutricional debe tener una redacción clínica más completa. Describe qué se busca lograr, cómo se orientará y en qué contexto.'
            );
        }

        $frasesGenericas = [
            'bajar peso',
            'subir peso',
            'comer mejor',
            'hacer dieta',
            'tomar agua',
            'alimentarse bien',
            'mejorar salud',
        ];

        foreach ($frasesGenericas as $frase) {
            if ($descripcionNormalizada === $frase) {
                $validator->errors()->add(
                    'des_obe',
                    'El objetivo es demasiado genérico. Redáctalo como una meta nutricional específica y medible.'
                );

                break;
            }
        }

        $verbosClinicos = [
            'reducir',
            'aumentar',
            'mejorar',
            'mantener',
            'controlar',
            'regular',
            'fortalecer',
            'disminuir',
            'incrementar',
            'adaptar',
        ];

        $tieneVerboClinico = collect($verbosClinicos)
            ->contains(fn ($verbo) => str_contains($descripcionNormalizada, $verbo));

        if (! $tieneVerboClinico) {
            $validator->errors()->add(
                'des_obe',
                'El objetivo debe indicar una acción clínica clara, por ejemplo: reducir, aumentar, mejorar, mantener, controlar o adaptar.'
            );
        }

        $marcadoresContexto = [
            'durante',
            'semanas',
            'mes',
            'seguimiento',
            'restricciones',
            'alergias',
            'hábitos',
            'habitos',
            'contexto',
            'paciente',
            'progresivamente',
        ];

        $tieneContexto = collect($marcadoresContexto)
            ->contains(fn ($marcador) => str_contains($descripcionNormalizada, $marcador));

        if (! $tieneContexto) {
            $validator->errors()->add(
                'des_obe',
                'Agrega un criterio de contexto o seguimiento, por ejemplo: duración, hábitos, restricciones, alergias o condición del paciente.'
            );
        }
    }
}