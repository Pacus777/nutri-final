<?php

namespace App\Http\Requests;

use App\Models\Alimento;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistroAlimenticioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_ral' => $this->input('est_ral') ?: RegistroAlimenticio::ESTADO_ACTIVO,
        ]);
    }

    public function rules(): array
    {
        return [
            'cod_pacientes_ral' => [
                'required',
                'integer',
                'exists:pacientes,cod_pacientes',
            ],

            'fec_ral' => [
                'required',
                'date',
            ],

            'est_ral' => [
                'required',
                Rule::in(RegistroAlimenticio::ESTADOS),
            ],

            'detalle' => [
                'required',
                'array',
                'min:1',
                'max:30',
            ],

            'detalle.*.cod_alimentos_dra' => [
                'required',
                'integer',
                'exists:alimentos,cod_alimentos',
            ],

            'detalle.*.tip_dra' => [
                'required',
                Rule::in(DetalleRegistroAlimenticio::TIPOS_COMIDA),
            ],

            'detalle.*.can_dra' => [
                'required',
                'numeric',
                'gt:0',
                'max:9999.99',
            ],

            'detalle.*.uni_dra' => [
                'nullable',
                'string',
                Rule::in(DetalleRegistroAlimenticio::UNIDADES),
            ],

            'detalle.*.hor_dra' => [
                'required',
                'date_format:H:i',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $paciente = Paciente::find($this->input('cod_pacientes_ral'));

            if (! $paciente) {
                return;
            }

            if (! $paciente->estaActivo()) {
                $validator->errors()->add(
                    'cod_pacientes_ral',
                    'El paciente debe estar activo para registrar alimentos.'
                );
            }

            if ((int) $paciente->cod_usuarios_pac !== (int) $this->user()?->cod_usuarios) {
                $validator->errors()->add(
                    'cod_pacientes_ral',
                    'No tienes permiso para registrar alimentos de este paciente.'
                );
            }

            if ($this->input('fec_ral') !== now()->toDateString()) {
                $validator->errors()->add(
                    'fec_ral',
                    'Solo puedes editar o registrar el consumo del día actual.'
                );
            }

            $detalles = $this->input('detalle') ?? ($validator->getData()['detalle'] ?? []);

            $alimentosIds = collect($detalles)
                ->pluck('cod_alimentos_dra')
                ->filter()
                ->unique()
                ->values();

            $alimentosActivos = Alimento::query()
                ->whereIn('cod_alimentos', $alimentosIds)
                ->where('est_ali', Alimento::ESTADO_ACTIVO)
                ->pluck('cod_alimentos')
                ->map(fn ($id) => (int) $id)
                ->values();

            $alimentosInactivos = $alimentosIds
                ->map(fn ($id) => (int) $id)
                ->diff($alimentosActivos);

            if ($alimentosInactivos->isNotEmpty()) {
                $validator->errors()->add(
                    'detalle',
                    'Uno o más alimentos seleccionados no están activos o no están disponibles.'
                );
            }

            $duplicadosExactos = collect($detalles)
                ->map(fn ($item) => implode('|', [
                    $item['cod_alimentos_dra'] ?? '',
                    $item['tip_dra'] ?? '',
                    $item['hor_dra'] ?? '',
                ]))
                ->duplicates();

            if ($duplicadosExactos->isNotEmpty()) {
                $validator->errors()->add(
                    'detalle',
                    'Hay alimentos duplicados en el mismo tipo de comida y hora. Ajusta cantidad o elimina duplicados.'
                );
            }

            // Validar compatibilidad física de unidad (sólido no puede ser ml, líquido no debe ser g)
            $calculoService = app(\App\Services\CalculoNutricionalRegistroService::class);
            $alimentosMap = Alimento::with('composicionVigente')
                ->whereIn('cod_alimentos', $alimentosIds)
                ->get()
                ->keyBy('cod_alimentos');

            foreach ($detalles as $idx => $det) {
                $aliId = $det['cod_alimentos_dra'] ?? null;
                $uniDra = strtolower(trim((string) ($det['uni_dra'] ?? 'gramos')));
                $alimento = $alimentosMap->get($aliId);

                if ($alimento) {
                    $esLiquido = $calculoService->esAlimentoLiquido($alimento);
                    if (! $esLiquido && $uniDra === 'mililitros') {
                        $validator->errors()->add(
                            "detalle.{$idx}.uni_dra",
                            "El alimento '{$alimento->nom_ali}' es un alimento sólido/físico y no puede medirse en mililitros. Por favor usa gramos, porción o unidad."
                        );
                    }
                    if ($esLiquido && $uniDra === 'gramos') {
                        $validator->errors()->add(
                            "detalle.{$idx}.uni_dra",
                            "El alimento '{$alimento->nom_ali}' es un líquido o bebida y debe registrarse en mililitros, porción o taza."
                        );
                    }
                }

                // Validar límites máximos razonables según la unidad de medida
                $cantidad = is_numeric($det['can_dra'] ?? null) ? (float) $det['can_dra'] : 0;
                $nomAli = $alimento ? "'{$alimento->nom_ali}'" : 'el alimento';

                if ($cantidad <= 0) {
                    $validator->errors()->add(
                        "detalle.{$idx}.can_dra",
                        "La cantidad para {$nomAli} debe ser mayor a 0."
                    );
                } else {
                    match ($uniDra) {
                        'gramos' => $cantidad > 2500 ? $validator->errors()->add(
                            "detalle.{$idx}.can_dra",
                            "La cantidad en gramos para {$nomAli} no puede superar los 2,500 g por comida."
                        ) : null,
                        'mililitros' => $cantidad > 3000 ? $validator->errors()->add(
                            "detalle.{$idx}.can_dra",
                            "La cantidad en mililitros para {$nomAli} no puede superar los 3,000 ml por comida."
                        ) : null,
                        'porcion', 'unidad' => $cantidad > 15 ? $validator->errors()->add(
                            "detalle.{$idx}.can_dra",
                            "La cantidad de " . ($uniDra === 'unidad' ? 'unidades' : 'porciones') . " para {$nomAli} no puede superar 15 por comida."
                        ) : null,
                        'taza' => $cantidad > 10 ? $validator->errors()->add(
                            "detalle.{$idx}.can_dra",
                            "La cantidad de tazas para {$nomAli} no puede superar 10 por comida."
                        ) : null,
                        'cucharada' => $cantidad > 20 ? $validator->errors()->add(
                            "detalle.{$idx}.can_dra",
                            "La cantidad de cucharadas para {$nomAli} no puede superar 20 por comida."
                        ) : null,
                        default => $cantidad > 2500 ? $validator->errors()->add(
                            "detalle.{$idx}.can_dra",
                            "La cantidad para {$nomAli} excede el límite máximo permitido."
                        ) : null,
                    };
                }
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_ral.required' => 'Debes seleccionar un paciente.',
            'cod_pacientes_ral.exists' => 'El paciente seleccionado no existe.',

            'fec_ral.required' => 'La fecha del registro alimenticio es obligatoria.',
            'fec_ral.date' => 'La fecha del registro alimenticio debe ser válida.',

            'est_ral.required' => 'El estado del registro alimenticio es obligatorio.',
            'est_ral.in' => 'El estado del registro alimenticio no es válido.',

            'detalle.required' => 'Debes agregar al menos un alimento.',
            'detalle.array' => 'El detalle del registro no tiene un formato válido.',
            'detalle.min' => 'Debes agregar al menos un alimento.',
            'detalle.max' => 'No puedes agregar más de 30 alimentos en un mismo registro diario.',

            'detalle.*.cod_alimentos_dra.required' => 'Debes seleccionar un alimento.',
            'detalle.*.cod_alimentos_dra.exists' => 'Uno de los alimentos seleccionados no existe.',

            'detalle.*.tip_dra.required' => 'Debes indicar el tipo de comida.',
            'detalle.*.tip_dra.in' => 'El tipo de comida no es válido.',

            'detalle.*.can_dra.required' => 'Debes indicar la cantidad consumida.',
            'detalle.*.can_dra.numeric' => 'La cantidad consumida debe ser numérica.',
            'detalle.*.can_dra.gt' => 'La cantidad consumida debe ser mayor que cero.',
            'detalle.*.can_dra.max' => 'La cantidad consumida es demasiado alta.',

            'detalle.*.uni_dra.in' => 'La unidad del alimento no es válida.',

            'detalle.*.hor_dra.required' => 'Debes indicar la hora del consumo.',
            'detalle.*.hor_dra.date_format' => 'La hora debe tener formato HH:MM.',
        ];
    }
}