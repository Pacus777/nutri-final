<?php

namespace App\Http\Requests;

use App\Models\EvaluacionInicial;
use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluacionInicialRequest extends FormRequest
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
            'est_evi' => $this->input('est_evi') ?: EvaluacionInicial::ESTADO_ACTIVA,
        ]);
    }

    public function rules(): array
    {
        return [
            /*
             * Se mantienen nullable para soportar el doble flujo:
             * - Admin: puede venir desde formulario.
             * - Nutricionista: el paciente vendrá desde la ruta.
             * - Paciente: el paciente vendrá desde el usuario autenticado.
             */
            'cod_pacientes_evi' => [
                'nullable',
                'integer',
                'exists:pacientes,cod_pacientes',
            ],

            'cod_nutricionistas_evi' => [
                'nullable',
                'integer',
                'exists:nutricionistas,cod_nutricionistas',
            ],

            'fec_evi' => [
                'required',
                'date',
            ],

            'est_evi' => [
                'required',
                Rule::in(EvaluacionInicial::ESTADOS),
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
            $paciente = $this->pacienteSeleccionado();
            $nutricionista = $this->nutricionistaSeleccionado();

            if ($paciente && ! $paciente->estaActivo()) {
                $validator->errors()->add(
                    'cod_pacientes_evi',
                    'El paciente debe estar activo para registrar una evaluación inicial.'
                );
            }

            if ($nutricionista && ! $nutricionista->estaActivo()) {
                $validator->errors()->add(
                    'cod_nutricionistas_evi',
                    'El nutricionista debe estar activo para registrar una evaluación inicial.'
                );
            }

            if ($paciente && $nutricionista && ! $paciente->tieneAsignacionActivaCon($nutricionista->getKey())) {
                if (! $this->usuarioEsAdministrador()) {
                    $validator->errors()->add(
                        'cod_nutricionistas_evi',
                        'El paciente no tiene una asignación activa con este nutricionista.'
                    );
                }
            }

            if ($paciente && $this->existeEvaluacionVigenteParaPaciente($paciente)) {
                $validator->errors()->add(
                    'cod_pacientes_evi',
                    'El paciente ya tiene una evaluación inicial vigente.'
                );
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_evi.exists' => 'El paciente seleccionado no existe.',
            'cod_nutricionistas_evi.exists' => 'El nutricionista seleccionado no existe.',

            'fec_evi.required' => 'La fecha de evaluación es obligatoria.',
            'fec_evi.date' => 'La fecha debe ser válida.',

            'est_evi.required' => 'El estado de la evaluación es obligatorio.',
            'est_evi.in' => 'El estado de la evaluación no es válido.',

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

    private function pacienteSeleccionado(): ?Paciente
    {
        $paciente = $this->route('paciente');

        if ($paciente instanceof Paciente) {
            return $paciente;
        }

        if ($this->filled('cod_pacientes_evi')) {
            return Paciente::find($this->input('cod_pacientes_evi'));
        }

        return null;
    }

    private function nutricionistaSeleccionado(): ?Nutricionista
    {
        $nutricionista = $this->route('nutricionista');

        if ($nutricionista instanceof Nutricionista) {
            return $nutricionista;
        }

        if ($this->filled('cod_nutricionistas_evi')) {
            return Nutricionista::find($this->input('cod_nutricionistas_evi'));
        }

        return null;
    }

    private function existeEvaluacionVigenteParaPaciente(Paciente $paciente): bool
    {
        return EvaluacionInicial::query()
            ->where('cod_pacientes_evi', $paciente->getKey())
            ->whereIn('est_evi', [
                EvaluacionInicial::ESTADO_BORRADOR,
                EvaluacionInicial::ESTADO_PENDIENTE_REVISION,
                EvaluacionInicial::ESTADO_ACTIVA,
            ])
            ->exists();
    }

    private function usuarioEsAdministrador(): bool
    {
        $usuario = $this->user();

        if (! $usuario) {
            return false;
        }

        return $usuario->roles()
            ->whereIn('nom_rol', ['Administrador', 'SuperAdmin'])
            ->exists();
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