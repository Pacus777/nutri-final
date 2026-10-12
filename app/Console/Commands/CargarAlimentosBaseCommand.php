<?php

namespace App\Console\Commands;

use Database\Seeders\AlimentoSeeder;
use Illuminate\Console\Command;

class CargarAlimentosBaseCommand extends Command
{
    protected $signature = 'alimentos:cargar-base';

    protected $description = 'Carga el catálogo completo de alimentos estándar y platos típicos bolivianos en español, normalizando registros antiguos.';

    public function handle(): int
    {
        $this->info('Iniciando carga y normalización del catálogo de alimentos...');

        try {
            $seeder = new AlimentoSeeder();
            $seeder->run();

            $totalAlimentos = \App\Models\Alimento::count();
            $totalBolivianos = \App\Models\Alimento::where('pai_ali', 'BO')->count();

            $this->newLine();
            $this->info("¡Catálogo actualizado con éxito!");
            $this->line("Total de alimentos activos en sistema: <comment>{$totalAlimentos}</comment>");
            $this->line("Alimentos y preparaciones bolivianas registradas: <comment>{$totalBolivianos}</comment>");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Ocurrió un error al cargar los alimentos: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
