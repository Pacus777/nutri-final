<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuscarOImportarAlimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Debes ingresar un alimento para buscar.',
            'q.min' => 'La búsqueda debe tener al menos 2 caracteres.',
        ];
    }
}