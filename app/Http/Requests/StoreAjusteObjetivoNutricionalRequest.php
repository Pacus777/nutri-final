<?php

namespace App\Http\Requests;

use App\Models\AjusteObjetivoNutricional;
use App\Models\Nutricionista;
use App\Models\ObjetivoNutricional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAjusteObjetivoNutricionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'motivo_ajn' => $this->input('motivo_ajn') ?: AjusteObjetivoNutricional::MOTIVO_AJUSTE_MANUAL,
        ]);
    }

    public function rules(): array
    {
        return [
            'cod_objetivos_nutricionales_ajn' => [
                'nullable',
                'integer',
                'exists:objetivos_nutricionales,cod_objetivos_nutricionales',
            ],

            'cod_nutricionistas_ajn' => [
                'nullable',
                'integer',
                'exists:nutricionistas,cod_nutricionistas',
            ],

            'fec_ajn' => [
                'required',
                'date',
            ],

            'des_ajn' => [
                'required',
                'string',
                'min:5',
                'max:1500',
            ],

            'motivo_ajn' => [
                'nullable',
                Rule::in(AjusteObjetivoNutricional::MOTIVOS),
            ],

            'estado_anterior_ajn' => [
                'nullable',
                'string',
                'max:50',
            ],

            'estado_nuevo_ajn' => [
                'nullable',
                'string',
                'max:50',
            ],

            'descripcion_anterior_ajn' => [
                'nullable',
                'string',
                'max:1500',
            ],

            'descripcion_nueva_ajn' => [
                'nullable',
                'string',
                'max:1500',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $objetivo = $this->objetivoSeleccionado();

            if (! $objetivo) {
                $validator->errors()->add(
                    'cod_objetivos_nutricionales_ajn',
                    'Debes seleccionar un objetivo nutricional válido.'
                );

                return;
            }

            if ($this->rolActivoEsNutricionista()) {
                $nutricionista = $this->nutricionistaAutenticado();

                if (! $nutricionista) {
                    $validator->errors()->add(
                        'cod_nutricionistas_ajn',
                        'No se encontró un perfil de nutricionista activo para el usuario autenticado.'
                    );

                    return;
                }

                $objetivo->loadMissing('paciente');

                if (! $objetivo->paciente) {
                    $validator->errors()->add(
                        'cod_objetivos_nutricionales_ajn',
                        'El objetivo no está asociado a un paciente.'
                    );

                    return;
                }

                if (! $objetivo->paciente->tieneAsignacionActivaCon($nutricionista->getKey())) {
                    $validator->errors()->add(
                        'cod_objetivos_nutricionales_ajn',
                        'No tienes acceso clínico al paciente asociado a este objetivo.'
                    );
                }
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'cod_objetivos_nutricionales_ajn.exists' => 'El objetivo seleccionado no existe.',
            'cod_nutricionistas_ajn.exists' => 'El nutricionista seleccionado no existe.',

            'fec_ajn.required' => 'La fecha del ajuste es obligatoria.',
            'fec_ajn.date' => 'La fecha del ajuste debe ser válida.',

            'des_ajn.required' => 'La descripción del ajuste es obligatoria.',
            'des_ajn.min' => 'La descripción del ajuste debe tener al menos 5 caracteres.',
            'des_ajn.max' => 'La descripción del ajuste no debe superar los 1500 caracteres.',

            'motivo_ajn.in' => 'El motivo del ajuste no es válido.',

            'estado_anterior_ajn.max' => 'El estado anterior no debe superar los 50 caracteres.',
            'estado_nuevo_ajn.max' => 'El estado nuevo no debe superar los 50 caracteres.',

            'descripcion_anterior_ajn.max' => 'La descripción anterior no debe superar los 1500 caracteres.',
            'descripcion_nueva_ajn.max' => 'La descripción nueva no debe superar los 1500 caracteres.',
        ];
    }

    private function objetivoSeleccionado(): ?ObjetivoNutricional
    {
        $objetivo = $this->route('objetivoNutricional')
            ?? $this->route('objetivo');

        if ($objetivo instanceof ObjetivoNutricional) {
            return $objetivo;
        }

        if ($this->filled('cod_objetivos_nutricionales_ajn')) {
            return ObjetivoNutricional::find($this->input('cod_objetivos_nutricionales_ajn'));
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
}