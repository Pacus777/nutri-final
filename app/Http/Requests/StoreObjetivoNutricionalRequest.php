<?php

namespace App\Http\Requests;

use App\Models\Nutricionista;
use App\Models\ObjetivoNutricional;
use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObjetivoNutricionalRequest extends FormRequest
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
            'cod_pacientes_obe' => [
                'nullable',
                'integer',
                'exists:pacientes,cod_pacientes',
            ],

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
            $paciente = $this->pacienteSeleccionado();

            if ($paciente && ! $paciente->estaActivo()) {
                $validator->errors()->add(
                    'cod_pacientes_obe',
                    'El paciente debe estar activo.'
                );
            }

            if ($this->rolActivoEsNutricionista()) {
                if (! $paciente) {
                    $validator->errors()->add(
                        'cod_pacientes_obe',
                        'Para crear un objetivo como nutricionista debes seleccionar un paciente asignado.'
                    );

                    return;
                }

                $nutricionista = $this->nutricionistaAutenticado();

                if (! $nutricionista) {
                    $validator->errors()->add(
                        'cod_pacientes_obe',
                        'No se encontró un perfil de nutricionista activo para el usuario autenticado.'
                    );

                    return;
                }

                if (! $paciente->tieneAsignacionActivaCon($nutricionista->getKey())) {
                    $validator->errors()->add(
                        'cod_pacientes_obe',
                        'El paciente no está asignado activamente a tu perfil de nutricionista.'
                    );
                }

                $this->validarRedaccionClinicaObjetivo($validator);
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_obe.exists' => 'El paciente seleccionado no existe.',

            'des_obe.required' => 'La descripción del objetivo es obligatoria.',
            'des_obe.min' => 'La descripción debe tener al menos 5 caracteres.',
            'des_obe.max' => 'La descripción no debe superar los 1000 caracteres.',

            'fec_obe.required' => 'La fecha del objetivo es obligatoria.',
            'fec_obe.date' => 'La fecha debe ser una fecha válida.',

            'est_obe.required' => 'El estado del objetivo es obligatorio.',
            'est_obe.in' => 'El estado del objetivo no es válido.',
        ];
    }

    private function pacienteSeleccionado(): ?Paciente
    {
        $paciente = $this->route('paciente');

        if ($paciente instanceof Paciente) {
            return $paciente;
        }

        if ($this->filled('cod_pacientes_obe')) {
            return Paciente::find($this->input('cod_pacientes_obe'));
        }

        return null;
    }

    private function nutricionistaAutenticado(): ?Nutricionista
    {
        $usuario = $this->user();

        if (! $usuario) {
            return null;
        }

        return Nutricionista::query()
            ->where('cod_usuarios_nut', $usuario->getKey())
            ->where('est_nut', 'activo')
            ->first();
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