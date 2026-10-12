<?php

namespace App\Http\Requests;

use App\Models\ContextoPaciente;
use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContextoPacienteRequest extends FormRequest
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
            /*
             * Nullable porque en el flujo del nutricionista el paciente puede venir desde la ruta:
             * /nutricionista/pacientes/{paciente}/contextos
             */
            'cod_pacientes_cxp' => [
                'nullable',
                'integer',
                'exists:pacientes,cod_pacientes',
            ],

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
            $paciente = $this->pacienteSeleccionado();

            if (! $paciente) {
                $validator->errors()->add(
                    'cod_pacientes_cxp',
                    'Debes seleccionar un paciente válido.'
                );

                return;
            }

            if (! $paciente->estaActivo()) {
                $validator->errors()->add(
                    'cod_pacientes_cxp',
                    'El paciente debe estar activo.'
                );
            }

            if ($this->rolActivoEsNutricionista()) {
                $nutricionista = $this->nutricionistaAutenticado();

                if (! $nutricionista) {
                    $validator->errors()->add(
                        'cod_pacientes_cxp',
                        'No se encontró un perfil de nutricionista activo para el usuario autenticado.'
                    );

                    return;
                }

                if (! $paciente->tieneAsignacionActivaCon($nutricionista->getKey())) {
                    $validator->errors()->add(
                        'cod_pacientes_cxp',
                        'El paciente no está asignado activamente a tu perfil de nutricionista.'
                    );
                }
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_cxp.exists' => 'El paciente seleccionado no existe.',

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

    private function pacienteSeleccionado(): ?Paciente
    {
        $paciente = $this->route('paciente');

        if ($paciente instanceof Paciente) {
            return $paciente;
        }

        if ($this->filled('cod_pacientes_cxp')) {
            return Paciente::find($this->input('cod_pacientes_cxp'));
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