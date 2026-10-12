<?php

namespace App\Http\Requests\Concerns;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;

trait ValidaEdadNacimiento
{
    protected function agregarValidacionEdadNacimiento(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $fechaNacimiento = $this->input('fec_nac_usu');

            if (! $fechaNacimiento) {
                return;
            }

            try {
                $edad = Carbon::parse($fechaNacimiento)->age;
            } catch (\Throwable $e) {
                return;
            }

            if ($edad < 18) {
                $validator->errors()->add('fec_nac_usu', 'No puede ser menor a 18 años.');
            }

            if ($edad > 50) {
                $validator->errors()->add('fec_nac_usu', 'No puede ser mayor a 50 años.');
            }
        });
    }
}