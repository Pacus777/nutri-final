<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeguimientoNutricionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_pacientes_seg' => ['required', 'integer', 'exists:pacientes,cod_pacientes'],
            'fec_seg' => [
                'required',
                'date',
                Rule::unique('seguimientos_nutricionales', 'fec_seg')
                    ->where(fn ($query) => $query->where('cod_pacientes_seg', $this->input('cod_pacientes_seg'))
                        ->where('cod_nutricionistas_seg', $this->input('cod_nutricionistas_seg'))),
            ],
            'res_seg' => ['required', 'string', 'min:5', 'max:2500'],
            'cam_seg' => ['nullable', 'string', 'max:2500'],
            'cod_nutricionistas_seg' => ['required', 'integer', 'exists:nutricionistas,cod_nutricionistas'],
        ];
    }

    public function messages(): array
    {
        return [
            'cod_pacientes_seg.required' => 'Debes seleccionar un paciente para registrar el seguimiento.',
            'cod_pacientes_seg.exists' => 'El paciente seleccionado no existe.',
            'cod_nutricionistas_seg.required' => 'No se encontro el nutricionista del seguimiento.',
            'cod_nutricionistas_seg.exists' => 'El nutricionista seleccionado no existe.',
            'fec_seg.required' => 'La fecha del seguimiento es obligatoria.',
            'fec_seg.unique' => 'Ya existe un seguimiento para esta fecha con este paciente.',
            'res_seg.required' => 'El resultado del seguimiento es obligatorio.',
            'res_seg.min' => 'El resultado debe tener al menos 5 caracteres.',
        ];
    }
}
