<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchAlimentoUsdaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Debes ingresar un texto para buscar alimentos.',
            'q.min' => 'La búsqueda debe tener al menos 2 caracteres.',
        ];
    }
}