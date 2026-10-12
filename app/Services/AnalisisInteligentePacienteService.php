<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\ValidacionInformacion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalisisInteligentePacienteService
{
    public function generar(Paciente $paciente): array
    {
        $desde = now()->subDays(27)->startOfDay();

        $registrosPeriodo = RegistroAlimenticio::query()
            ->where('cod_pacientes_ral', $paciente->getKey())
            ->whereDate('fec_ral', '>=', $desde->toDateString())
            ->with([
                'detalles.alimento.composicionVigente',
                'ultimaValidacionInformacion',
            ])
            ->orderBy('fec_ral')
            ->get();

        $resumenDataset = $this->resumenDatasetValidado($registrosPeriodo);

        $registros = $registrosPeriodo
            ->filter(fn (RegistroAlimenticio $registro) => $this->registroUsableParaAnalisis($registro))
            ->values();

        $detalles = $registros
            ->flatMap(fn (RegistroAlimenticio $registro) => $registro->detalles ?? collect())
            ->values();

        $totales = $this->calcularTotalesNutricionales($detalles);

        $diasConRegistro = max($registros->count(), 1);
        $alimentosProcesados = $detalles->count();
        $alimentosConComposicion = $detalles->filter(fn ($detalle) => $detalle->alimento?->composicionVigente)->count();

        $promedioCalorico = $totales['cal'] / $diasConRegistro;
        $promedioProteina = $totales['pro'] / $diasConRegistro;
        $promedioGrasa = $totales['gra'] / $diasConRegistro;
        $promedioCarbohidratos = $totales['car'] / $diasConRegistro;

        $totalMacronutrientes = max($totales['pro'] + $totales['gra'] + $totales['car'], 1);
        $porcentajeCarbohidratos = round(($totales['car'] / $totalMacronutrientes) * 100);
        $porcentajeProteina = round(($totales['pro'] / $totalMacronutrientes) * 100);
        $porcentajeGrasa = round(($totales['gra'] / $totalMacronutrientes) * 100);

        $frecuenciaDesayuno = $this->calcularFrecuenciaSemanalDesayuno($registros);
        $variabilidadHoraria = $this->calcularVariabilidadHoraria($detalles);
        $coberturaComposicion = $alimentosProcesados > 0
            ? round(($alimentosConComposicion / $alimentosProcesados) * 100)
            : 0;

        $variables = $this->generarVariables(
            promedioCalorico: $promedioCalorico,
            porcentajeCarbohidratos: $porcentajeCarbohidratos,
            porcentajeProteina: $porcentajeProteina,
            porcentajeGrasa: $porcentajeGrasa,
            frecuenciaDesayuno: $frecuenciaDesayuno,
            variabilidadHoraria: $variabilidadHoraria,
            coberturaComposicion: $coberturaComposicion,
            promedioProteina: $promedioProteina,
        );

        $riesgo = $this->estimarRiesgo(
            promedioCalorico: $promedioCalorico,
            porcentajeCarbohidratos: $porcentajeCarbohidratos,
            frecuenciaDesayuno: $frecuenciaDesayuno,
            variabilidadHoraria: $variabilidadHoraria,
            coberturaComposicion: $coberturaComposicion,
            registrosAnalizados: $registros->count(),
        );

        $patron = $this->detectarPatron(
            porcentajeCarbohidratos: $porcentajeCarbohidratos,
            porcentajeGrasa: $porcentajeGrasa,
            promedioCalorico: $promedioCalorico,
            frecuenciaDesayuno: $frecuenciaDesayuno,
            variabilidadHoraria: $variabilidadHoraria,
            coberturaComposicion: $coberturaComposicion,
            registrosAnalizados: $registros->count(),
        );

        return [
            'modo' => 'real_preliminar',
            'periodo' => 'Últimos 28 días',
            'registros_analizados' => $registros->count(),
            'alimentos_procesados' => $alimentosProcesados,
            'variables_generadas' => count($variables),
            'calidad_datos' => $this->calidadDatos($registros->count(), $coberturaComposicion),
            'dataset' => $resumenDataset,

            'riesgo' => $riesgo,
            'patron' => $patron,
            'variables' => $variables,
            'arbol_decision' => $this->generarArbolDecision(
                porcentajeCarbohidratos: $porcentajeCarbohidratos,
                frecuenciaDesayuno: $frecuenciaDesayuno,
                variabilidadHoraria: $variabilidadHoraria,
                promedioProteina: $promedioProteina,
                coberturaComposicion: $coberturaComposicion,
            ),
            'recomendaciones' => $this->generarRecomendaciones(
                porcentajeCarbohidratos: $porcentajeCarbohidratos,
                frecuenciaDesayuno: $frecuenciaDesayuno,
                variabilidadHoraria: $variabilidadHoraria,
                coberturaComposicion: $coberturaComposicion,
                riesgoNivel: $riesgo['nivel'],
            ),
        ];
    }

    private function registroUsableParaAnalisis(RegistroAlimenticio $registro): bool
    {
        $estadoValidacion = $registro->ultimaValidacionInformacion?->est_vin;

        return in_array($estadoValidacion, [
            ValidacionInformacion::ESTADO_ACEPTADO,
            ValidacionInformacion::ESTADO_OBSERVADO,
        ], true);
    }

    private function resumenDatasetValidado(Collection $registrosPeriodo): array
    {
        $aceptados = 0;
        $observados = 0;
        $rechazados = 0;
        $pendientes = 0;

        foreach ($registrosPeriodo as $registro) {
            $estadoValidacion = $registro->ultimaValidacionInformacion?->est_vin;

            if ($estadoValidacion === ValidacionInformacion::ESTADO_ACEPTADO) {
                $aceptados++;
                continue;
            }

            if ($estadoValidacion === ValidacionInformacion::ESTADO_OBSERVADO) {
                $observados++;
                continue;
            }

            if ($estadoValidacion === ValidacionInformacion::ESTADO_RECHAZADO) {
                $rechazados++;
                continue;
            }

            $pendientes++;
        }

        $usados = $aceptados + $observados;
        $total = $registrosPeriodo->count();

        return [
            'registros_totales_periodo' => $total,
            'registros_usados' => $usados,
            'registros_excluidos' => max($total - $usados, 0),
            'aceptados' => $aceptados,
            'observados' => $observados,
            'rechazados' => $rechazados,
            'pendientes' => $pendientes,
            'criterio_uso' => 'El análisis usa registros aceptados y observados. Excluye registros rechazados o pendientes de validación.',
        ];
    }

    private function calcularTotalesNutricionales(Collection $detalles): array
    {
        $totales = [
            'cal' => 0.0,
            'pro' => 0.0,
            'gra' => 0.0,
            'car' => 0.0,
        ];

        foreach ($detalles as $detalle) {
            $composicion = $detalle->alimento?->composicionVigente;

            if (! $composicion) {
                continue;
            }

            $factor = $this->factorPorcion($detalle, $composicion);

            $totales['cal'] += (float) $composicion->cal_tcn * $factor;
            $totales['pro'] += (float) $composicion->pro_tcn * $factor;
            $totales['gra'] += (float) $composicion->gra_tcn * $factor;
            $totales['car'] += (float) $composicion->car_tcn * $factor;
        }

        return $totales;
    }

    private function factorPorcion($detalle, $composicion): float
    {
        $cantidad = max((float) $detalle->can_dra, 0);
        $unidad = strtolower((string) $detalle->uni_dra);
        $referencia = (float) ($composicion->cant_ref_tcn ?: 100);

        if ($cantidad <= 0) {
            return 0;
        }

        if (in_array($unidad, ['gramos', 'mililitros'], true)) {
            return $referencia > 0 ? $cantidad / $referencia : $cantidad / 100;
        }

        if (in_array($unidad, ['unidad', 'porcion', 'taza', 'cucharada'], true)) {
            return $cantidad;
        }

        return $referencia > 0 ? $cantidad / $referencia : 1;
    }

    private function calcularFrecuenciaSemanalDesayuno(Collection $registros): float
    {
        if ($registros->isEmpty()) {
            return 0;
        }

        $diasConDesayuno = $registros->filter(function (RegistroAlimenticio $registro) {
            return $registro->detalles
                ->contains(fn ($detalle) => $detalle->tip_dra === 'desayuno');
        })->count();

        return round(($diasConDesayuno / max($registros->count(), 1)) * 7, 1);
    }

    private function calcularVariabilidadHoraria(Collection $detalles): array
    {
        $minutos = $detalles
            ->filter(fn ($detalle) => $detalle->hor_dra)
            ->map(fn ($detalle) => $this->horaAMinutos($detalle->hor_dra))
            ->filter(fn ($valor) => $valor !== null)
            ->values();

        if ($minutos->count() < 2) {
            return [
                'nivel' => 'Insuficiente',
                'desviacion_minutos' => 0,
                'estado' => 'warning',
            ];
        }

        $promedio = $minutos->average();

        $varianza = $minutos
            ->map(fn ($valor) => pow($valor - $promedio, 2))
            ->average();

        $desviacion = round(sqrt($varianza));

        if ($desviacion >= 120) {
            return [
                'nivel' => 'Alta',
                'desviacion_minutos' => $desviacion,
                'estado' => 'warning',
            ];
        }

        if ($desviacion >= 60) {
            return [
                'nivel' => 'Media',
                'desviacion_minutos' => $desviacion,
                'estado' => 'info',
            ];
        }

        return [
            'nivel' => 'Baja',
            'desviacion_minutos' => $desviacion,
            'estado' => 'success',
        ];
    }

    private function horaAMinutos($hora): ?int
    {
        try {
            $carbon = $hora instanceof Carbon
                ? $hora
                : Carbon::parse((string) $hora);

            return ($carbon->hour * 60) + $carbon->minute;
        } catch (\Throwable) {
            return null;
        }
    }

    private function generarVariables(
        float $promedioCalorico,
        int $porcentajeCarbohidratos,
        int $porcentajeProteina,
        int $porcentajeGrasa,
        float $frecuenciaDesayuno,
        array $variabilidadHoraria,
        int $coberturaComposicion,
        float $promedioProteina,
    ): array {
        return [
            [
                'nombre' => 'Promedio calórico diario',
                'valor' => round($promedioCalorico) . ' kcal',
                'lectura' => $promedioCalorico > 2300 ? 'Sobre rango estimado' : 'Dentro de rango preliminar',
                'estado' => $promedioCalorico > 2300 ? 'warning' : 'success',
            ],
            [
                'nombre' => 'Distribución de carbohidratos',
                'valor' => $porcentajeCarbohidratos . '%',
                'lectura' => $porcentajeCarbohidratos > 55 ? 'Tendencia alta' : 'Proporción aceptable',
                'estado' => $porcentajeCarbohidratos > 55 ? 'warning' : 'success',
            ],
            [
                'nombre' => 'Distribución de proteínas',
                'valor' => $porcentajeProteina . '%',
                'lectura' => $promedioProteina < 45 ? 'Aporte bajo' : 'Aporte aceptable',
                'estado' => $promedioProteina < 45 ? 'warning' : 'success',
            ],
            [
                'nombre' => 'Distribución de grasas',
                'valor' => $porcentajeGrasa . '%',
                'lectura' => $porcentajeGrasa > 35 ? 'Tendencia elevada' : 'Proporción aceptable',
                'estado' => $porcentajeGrasa > 35 ? 'warning' : 'success',
            ],
            [
                'nombre' => 'Frecuencia de desayuno',
                'valor' => $frecuenciaDesayuno . '/7 días',
                'lectura' => $frecuenciaDesayuno < 4 ? 'Baja regularidad' : 'Regularidad aceptable',
                'estado' => $frecuenciaDesayuno < 4 ? 'danger' : 'success',
            ],
            [
                'nombre' => 'Variabilidad horaria',
                'valor' => $variabilidadHoraria['nivel'],
                'lectura' => $variabilidadHoraria['nivel'] === 'Alta' ? 'Rutina irregular' : 'Rutina más estable',
                'estado' => $variabilidadHoraria['estado'],
            ],
            [
                'nombre' => 'Cobertura nutricional',
                'valor' => $coberturaComposicion . '%',
                'lectura' => $coberturaComposicion < 70 ? 'Datos incompletos' : 'Datos suficientes',
                'estado' => $coberturaComposicion < 70 ? 'warning' : 'success',
            ],
        ];
    }

    private function estimarRiesgo(
        float $promedioCalorico,
        int $porcentajeCarbohidratos,
        float $frecuenciaDesayuno,
        array $variabilidadHoraria,
        int $coberturaComposicion,
        int $registrosAnalizados,
    ): array {
        if ($registrosAnalizados === 0) {
            return [
                'nivel' => 'No estimado',
                'score_heuristico' => 0,
                'interpretacion' => 'No existen registros aceptados u observados suficientes para calcular el score heurístico.',
            ];
        }

        $cfgCalorias = config('criterios_nutricionales.heuristica.calorias_referencia_max', 2300.0);
        $cfgCarbos = config('criterios_nutricionales.heuristica.carbohidratos_pct_max', 55);

        $score = 15;

        if ($promedioCalorico > $cfgCalorias) {
            $score += 18;
        }

        if ($porcentajeCarbohidratos > $cfgCarbos) {
            $score += 22;
        }

        if ($frecuenciaDesayuno < 4) {
            $score += 18;
        }

        if ($variabilidadHoraria['nivel'] === 'Alta') {
            $score += 15;
        }

        if ($coberturaComposicion < 70) {
            $score += 10;
        }

        if ($registrosAnalizados < 7) {
            $score += 7;
        }

        $scoreHeuristico = min($score, 95);

        if ($scoreHeuristico >= 70) {
            $nivel = 'Alto';
        } elseif ($scoreHeuristico >= 40) {
            $nivel = 'Moderado';
        } else {
            $nivel = 'Bajo';
        }

        return [
            'nivel' => $nivel,
            'score_heuristico' => $scoreHeuristico,
            'interpretacion' => $this->interpretacionRiesgo($nivel, $porcentajeCarbohidratos, $frecuenciaDesayuno, $variabilidadHoraria),
        ];
    }

    private function interpretacionRiesgo(string $nivel, int $porcentajeCarbohidratos, float $frecuenciaDesayuno, array $variabilidadHoraria): string
    {
        if ($nivel === 'Alto') {
            return 'Se identifican varias señales de desequilibrio nutricional que requieren seguimiento cercano.';
        }

        if ($nivel === 'Moderado') {
            return 'Existen variables alimenticias que requieren monitoreo, especialmente distribución de macronutrientes y regularidad de hábitos.';
        }

        return 'El historial reciente no muestra señales fuertes de desequilibrio según las variables disponibles.';
    }

    private function detectarPatron(
        int $porcentajeCarbohidratos,
        int $porcentajeGrasa,
        float $promedioCalorico,
        float $frecuenciaDesayuno,
        array $variabilidadHoraria,
        int $coberturaComposicion,
        int $registrosAnalizados,
    ): array {
        if ($registrosAnalizados === 0) {
            return [
                'cluster' => 'Sin perfil',
                'nombre' => 'Sin datos validados suficientes',
                'similitud' => 0,
                'descripcion' => 'El paciente aún no tiene registros aceptados u observados para identificar un patrón alimenticio confiable.',
            ];
        }

        if ($coberturaComposicion < 50) {
            return [
                'cluster' => 'Perfil D',
                'nombre' => 'Datos insuficientes o incompletos',
                'similitud' => 55,
                'descripcion' => 'El paciente tiene registros con cobertura nutricional limitada. Es necesario mejorar la calidad del dato antes de interpretar patrones.',
            ];
        }

        if ($porcentajeCarbohidratos > 55 || $frecuenciaDesayuno < 4 || $variabilidadHoraria['nivel'] === 'Alta') {
            return [
                'cluster' => 'Perfil B',
                'nombre' => 'Consumo alto en carbohidratos con horarios irregulares',
                'similitud' => 82,
                'descripcion' => 'El paciente se aproxima a un patrón de alta proporción de carbohidratos, baja regularidad de desayuno o variabilidad horaria.',
            ];
        }

        if ($porcentajeGrasa > 35 || $promedioCalorico > 2500) {
            return [
                'cluster' => 'Perfil C',
                'nombre' => 'Consumo energético elevado',
                'similitud' => 76,
                'descripcion' => 'El paciente se aproxima a un perfil de mayor densidad energética y posible exceso calórico.',
            ];
        }

        return [
            'cluster' => 'Perfil A',
            'nombre' => 'Patrón alimenticio relativamente balanceado',
            'similitud' => 79,
            'descripcion' => 'El paciente se aproxima a un perfil con distribución nutricional más estable dentro del historial reciente.',
        ];
    }

    private function generarArbolDecision(
        int $porcentajeCarbohidratos,
        float $frecuenciaDesayuno,
        array $variabilidadHoraria,
        float $promedioProteina,
        int $coberturaComposicion,
    ): array {
        return [
            [
                'condicion' => 'Carbohidratos promedio > 55%',
                'resultado' => $porcentajeCarbohidratos > 55 ? 'Sí' : 'No',
                'estado' => $porcentajeCarbohidratos > 55 ? 'warning' : 'success',
            ],
            [
                'condicion' => 'Desayuno registrado menos de 4 días/semana',
                'resultado' => $frecuenciaDesayuno < 4 ? 'Sí' : 'No',
                'estado' => $frecuenciaDesayuno < 4 ? 'warning' : 'success',
            ],
            [
                'condicion' => 'Variabilidad horaria alta',
                'resultado' => $variabilidadHoraria['nivel'] === 'Alta' ? 'Sí' : 'No',
                'estado' => $variabilidadHoraria['nivel'] === 'Alta' ? 'warning' : 'success',
            ],
            [
                'condicion' => 'Proteína promedio bajo mínimo estimado',
                'resultado' => $promedioProteina < 45 ? 'Sí' : 'No',
                'estado' => $promedioProteina < 45 ? 'warning' : 'success',
            ],
            [
                'condicion' => 'Cobertura nutricional menor a 70%',
                'resultado' => $coberturaComposicion < 70 ? 'Sí' : 'No',
                'estado' => $coberturaComposicion < 70 ? 'warning' : 'success',
            ],
        ];
    }

    private function generarRecomendaciones(
        int $porcentajeCarbohidratos,
        float $frecuenciaDesayuno,
        array $variabilidadHoraria,
        int $coberturaComposicion,
        string $riesgoNivel,
    ): array {
        $recomendaciones = [];

        if ($porcentajeCarbohidratos > 55) {
            $recomendaciones[] = 'Revisar la distribución de carbohidratos y priorizar fuentes con mayor calidad nutricional.';
        }

        if ($frecuenciaDesayuno < 4) {
            $recomendaciones[] = 'Regularizar el desayuno para mejorar frecuencia de comidas y control del consumo diario.';
        }

        if ($variabilidadHoraria['nivel'] === 'Alta') {
            $recomendaciones[] = 'Trabajar en horarios alimenticios más constantes durante la semana.';
        }

        if ($coberturaComposicion < 70) {
            $recomendaciones[] = 'Mejorar la calidad del registro alimenticio para obtener análisis más confiables.';
        }

        if (empty($recomendaciones)) {
            $recomendaciones[] = 'Mantener seguimiento periódico para confirmar estabilidad del patrón alimenticio.';
        }

        if ($riesgoNivel === 'Alto') {
            $recomendaciones[] = 'Priorizar intervención y seguimiento cercano por señales acumuladas de riesgo.';
        }

        return $recomendaciones;
    }

    private function calidadDatos(int $registrosAnalizados, int $coberturaComposicion): string
    {
        if ($registrosAnalizados === 0) {
            return 'Sin registros suficientes';
        }

        if ($registrosAnalizados < 7 || $coberturaComposicion < 70) {
            return 'Datos en consolidación';
        }

        if ($registrosAnalizados < 14) {
            return 'Suficiente para análisis preliminar';
        }

        return 'Suficiente para análisis consistente';
    }
}
