<?php

namespace App\Console\Commands;

use App\Enums\EstadoPerfil;
use App\Models\FeatureNutricional;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Services\FeatureEngineeringNutricionalService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateClinicalSnapshotsCommand extends Command
{
    protected $signature = 'ml:generate-clinical-snapshots
                            {--window=7 : Ventana de días por snapshot (por defecto: 7)}
                            {--mode=rolling : Modo de generación: rolling (móvil, paso de 1 día) o tumbling (fijo no superpuesto)}
                            {--min-adherence=0.5714 : Adherencia mínima requerida (0.5714 = 4 de 7 días, 0.7143 = 5 de 7 días)}
                            {--patient= : Filtrar por código de paciente específico}
                            {--include-seed-patients : Incluir pacientes operativos base del sistema}
                            {--force : Sobrescribir snapshots existentes}';

    protected $description = 'Genera snapshots de features_nutricionales para pacientes clínicos en ventanas de 7 días (modos rolling y tumbling con calidad de datos).';

    public function __construct(
        protected FeatureEngineeringNutricionalService $featureService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $window = (int) $this->option('window');
        if ($window <= 0) {
            $this->error('La ventana de días debe ser un entero positivo.');
            return self::FAILURE;
        }

        $mode = strtolower((string) $this->option('mode'));
        if (!in_array($mode, ['rolling', 'tumbling'])) {
            $this->error("Modo inválido '{$mode}'. Use 'rolling' o 'tumbling'.");
            return self::FAILURE;
        }

        $minAdherence = (float) $this->option('min-adherence');
        $patientId = $this->option('patient');
        $includeSeed = (bool) $this->option('include-seed-patients');
        $force = (bool) $this->option('force');

        $this->info("=== GENERACIÓN DE SNAPSHOTS CLÍNICOS REALES (ML FASE 2D.2) ===");
        $this->line("Modo: " . strtoupper($mode) . " | Ventana: {$window} días | Adherencia mínima: {$minAdherence} (mínimo " . ceil($window * $minAdherence) . "/{$window} días)");

        $query = Paciente::query()
            ->with(['usuario'])
            ->where('est_pac', EstadoPerfil::ACTIVO);

        if (!$includeSeed && !$patientId) {
            // Excluir bots de pruebas creados en ejecuciones automáticas
            $query->whereHas('usuario', function ($q) {
                $q->where('cor_usu', 'not like', '%prov_%')
                  ->where('cor_usu', 'not like', '%pac_ml_%')
                  ->where('cor_usu', 'not like', '%export_%')
                  ->where('cor_usu', 'not like', '%bot_%')
                  ->where('cor_usu', 'not like', '%@phpunit.test%');
            });
        }

        if ($patientId) {
            $query->where('cod_pacientes', $patientId);
        }

        $pacientes = $query->get();

        if ($pacientes->isEmpty()) {
            $this->warn('No se encontraron pacientes clínicos elegibles.');
            return self::SUCCESS;
        }

        $this->info("Pacientes clínicos evaluados: {$pacientes->count()}");

        $totalPacientesProcesados = 0;
        $totalVentanasEvaluadas = 0;
        $totalSnapshotsCreados = 0;
        $totalSnapshotsExistentes = 0;
        $totalVentanasInsuficientes = 0;

        $calidadAlta = 0;      // 7/7 días (1.0000)
        $calidadAceptable = 0; // 5-6/7 días (0.7143 - 0.8571)
        $calidadLimitada = 0;  // 4/7 días (0.5714)

        $desgloseVentanas = [];

        foreach ($pacientes as $paciente) {
            $totalPacientesProcesados++;

            // Obtener fechas de registros del paciente con detalles
            $fechasRegistros = RegistroAlimenticio::query()
                ->where('cod_pacientes_ral', $paciente->cod_pacientes)
                ->whereHas('detalles')
                ->orderBy('fec_ral', 'asc')
                ->pluck('fec_ral')
                ->map(fn ($d) => Carbon::parse($d)->startOfDay())
                ->unique(fn (Carbon $c) => $c->toDateString())
                ->values();

            if ($fechasRegistros->isEmpty()) {
                continue;
            }

            $minDate = $fechasRegistros->first();
            $maxDate = $fechasRegistros->last();

            $currentStart = $minDate->copy();
            $stepDays = ($mode === 'rolling') ? 1 : $window;

            while ($currentStart->lte($maxDate)) {
                $totalVentanasEvaluadas++;
                $currentEnd = $currentStart->copy()->addDays($window - 1);

                $fecInicioStr = $currentStart->toDateString();
                $fecFinStr = $currentEnd->toDateString();

                // Contar días registrados en la ventana exacta de 7 días
                $diasRegistrados = $fechasRegistros->filter(
                    fn (Carbon $d) => $d->betweenIncluded($currentStart, $currentEnd)
                )->count();

                $adherencia = round($diasRegistrados / $window, 4);

                if ($adherencia < $minAdherence) {
                    $totalVentanasInsuficientes++;
                    $currentStart->addDays($stepDays);
                    continue;
                }

                // Clasificar calidad técnica de la ventana
                if ($diasRegistrados >= 7) {
                    $nivelCalidad = 'alta (7/7)';
                    $calidadAlta++;
                } elseif ($diasRegistrados >= 5) {
                    $nivelCalidad = 'aceptable (5-6/7)';
                    $calidadAceptable++;
                } else {
                    $nivelCalidad = 'limitada (4/7)';
                    $calidadLimitada++;
                }

                // Verificar si ya existe snapshot equivalente
                $existente = FeatureNutricional::query()
                    ->where('cod_pacientes_fnu', $paciente->cod_pacientes)
                    ->where('fec_inicio_fnu', $fecInicioStr)
                    ->where('fec_fin_fnu', $fecFinStr)
                    ->where('ventana_dias_fnu', $window)
                    ->where('version_features_fnu', FeatureEngineeringNutricionalService::VERSION)
                    ->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
                    ->first();

                if ($existente && ! $force) {
                    $totalSnapshotsExistentes++;
                } else {
                    $this->featureService->guardarSnapshot(
                        paciente: $paciente,
                        fechaFin: $fecFinStr,
                        ventanaDias: $window,
                        forzar: $force,
                        origenDatos: FeatureNutricional::ORIGEN_CLINICO_REAL,
                        referenciaOrigen: "ml:generate-clinical-snapshots:{$mode}"
                    );
                    $totalSnapshotsCreados++;
                }

                $desgloseVentanas[] = [
                    'paciente' => "#{$paciente->cod_pacientes} " . ($paciente->usuario ? $paciente->usuario->nom_usu : ''),
                    'periodo' => "{$fecInicioStr} al {$fecFinStr}",
                    'dias_reg' => "{$diasRegistrados}/{$window}",
                    'adherencia' => ($adherencia * 100) . '%',
                    'calidad' => $nivelCalidad,
                    'estado' => $existente && !$force ? 'Existente (Omitido)' : 'Generado',
                ];

                $currentStart->addDays($stepDays);
            }
        }

        $this->newLine();
        $this->info("--- RESUMEN DE GENERACIÓN CLÍNICA REAL ---");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Pacientes clínicos evaluados', $totalPacientesProcesados],
                ['Modo de Ventanas', strtoupper($mode) . ($mode === 'rolling' ? ' (paso = 1 día)' : ' (no superpuestas)')],
                ['Ventanas de 7 días evaluadas', $totalVentanasEvaluadas],
                ['Snapshots clínicos reales CREADOS', $totalSnapshotsCreados],
                ['Snapshots ya existentes (conservados)', $totalSnapshotsExistentes],
                ['Ventanas omitidas (< 4 días)', $totalVentanasInsuficientes],
                ['  - Calidad ALTA (7/7 días)', $calidadAlta],
                ['  - Calidad ACEPTABLE (5-6/7 días)', $calidadAceptable],
                ['  - Calidad LIMITADA (4/7 días)', $calidadLimitada],
            ]
        );

        if (!empty($desgloseVentanas)) {
            $this->newLine();
            $this->info("--- DISTRIBUCIÓN TEMPORAL DE VENTANAS MÓVILES ELEGIBLES ---");
            $this->table(
                ['Paciente', 'Período Ventana (7d)', 'Días Reg.', 'Adherencia', 'Calidad', 'Estado'],
                $desgloseVentanas
            );
        }

        $this->info("Proceso completado exitosamente.");

        return self::SUCCESS;
    }
}
