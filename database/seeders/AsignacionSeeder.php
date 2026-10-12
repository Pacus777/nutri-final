<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\AsignacionNutricionistaPaciente;

class AsignacionSeeder extends Seeder
{
    public function run(): void
    {
        $nutricionistas = Nutricionista::query()->orderBy('cod_nutricionistas')->get()->values();
        $pacientes = Paciente::query()->orderBy('cod_pacientes')->get()->values();

        if ($nutricionistas->isEmpty() || $pacientes->isEmpty()) {
            return;
        }

        $maxPacientesPorNutricionista = 3;
        $gruposPacientes = $pacientes->chunk($maxPacientesPorNutricionista);

        foreach ($gruposPacientes as $indiceGrupo => $grupoPacientes) {
            $nutricionista = $nutricionistas->get($indiceGrupo);

            if (! $nutricionista) {
                break;
            }

            foreach ($grupoPacientes as $paciente) {
                AsignacionNutricionistaPaciente::firstOrCreate(
                    [
                        'cod_nutricionistas_anp' => $nutricionista->cod_nutricionistas,
                        'cod_pacientes_anp' => $paciente->cod_pacientes,
                    ],
                    [
                        'fec_anp' => now()->toDateString(),
                        'est_anp' => 'activa',
                    ]
                );
            }
        }
    }
}
