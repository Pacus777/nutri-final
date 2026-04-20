<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsignacionNutricionistaPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'est_anp' => ['required', Rule::in(['activa', 'cerrada', 'reasignada'])],
        ];
    }

    public function messages(): array
    {
        return [
            'est_anp.required' => 'El estado de la asignación es obligatorio.',
        ];
    }
}