<?php

namespace App\Services;

use App\Models\EvaluacionInicial;
use App\Models\FeatureNutricional;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\SeguimientoNutricional;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FeatureEngineeringNutricionalService
{
    public const VERSION = 'v1.0';

    public function __construct(
        protected CalculoNutricionalRegistroService $calculoService
    ) {}

    /**
     * Extrae el vector de features para un paciente en una ventana de tiempo inclusiva [fec_inicio, fec_fin].
     */
    public function extraerVector(Paciente|int $paciente, ?string $fechaFin = null, int $ventanaDias = 7): array
    {
        $pacienteModel = $paciente instanceof Paciente
            ? $paciente->loadMissing('usuario')
            : Paciente::with('usuario')->findOrFail($paciente);

        if ($ventanaDias <= 0) {
            throw new InvalidArgumentException("La ventana de días debe ser mayor a cero. Valor dado: {$ventanaDias}");
        }

        $fin = $fechaFin ? Carbon::parse($fechaFin)->startOfDay() : Carbon::now()->startOfDay();
        $inicio = $fin->copy()->subDays($ventanaDias - 1)->startOfDay();

        // Obtener registros dentro de la ventana inclusiva
        $registros = RegistroAlimenticio::query()
            ->where('cod_pacientes_ral', $pacienteModel->cod_pacientes)
            ->whereBetween('fec_ral', [$inicio->toDateString(), $fin->toDateString()])
            ->with([
                'detalles.alimento.composicionVigente',
                'ultimaValidacionInformacion',
            ])
            ->orderBy('fec_ral', 'asc')
            ->get()
            ->filter(fn (RegistroAlimenticio $r) => $r->detalles->isNotEmpty())
            ->values();

        $diasRegistrados = $registros->count();
        $adherenciaRegistro = round($diasRegistrados / $ventanaDias, 4);

        $diasValidados = $registros->filter(function (RegistroAlimenticio $r) {
            $val = $r->ultimaValidacionInformacion;
            return $val && ! empty($val->est_vin);
        })->count();

        $ratioValidacion = $diasRegistrados > 0 ? round($diasValidados / $diasRegistrados, 4) : 0.0000;

        // Procesar ingestas diarias
        $diasData = [];
        $totalAlimentos = 0;
        $totalAlimentosConComposicion = 0;

        $diasConDesayuno = 0;
        $diasConCena = 0;
        $diasConSnack = 0;
        $momentosComidaPorDia = [];
        $horariosPorTipoComida = [
            'desayuno' => [],
            'almuerzo' => [],
            'cena' => [],
            'snack' => [],
        ];

        foreach ($registros as $registro) {
            $calDia = 0.0;
            $proDia = 0.0;
            $carDia = 0.0;
            $graDia = 0.0;
            $fibDia = 0.0;
            $azuDia = 0.0;
            $sodDia = 0.0;

            $tiposComidaDelDia = [];
            $minutosPorTipoEnDia = [
                'desayuno' => [],
                'almuerzo' => [],
                'cena' => [],
                'snack' => [],
            ];

            foreach ($registro->detalles as $detalle) {
                $totalAlimentos++;
                $comp = $detalle->alimento?->composicionVigente;

                if ($comp) {
                    $totalAlimentosConComposicion++;
                    $factor = $this->calculoService->calcularFactorConsumo($detalle, $comp);

                    $calDia += ((float) $comp->cal_tcn) * $factor;
                    $proDia += ((float) $comp->pro_tcn) * $factor;
                    $carDia += ((float) $comp->car_tcn) * $factor;
                    $graDia += ((float) $comp->gra_tcn) * $factor;
                    $fibDia += ((float) $comp->fib_tcn) * $factor;
                    $azuDia += ((float) $comp->azu_tcn) * $factor;
                    $sodDia += ((float) $comp->sod_tcn) * $factor;
                }

                $tipoNormalizado = mb_strtolower(trim((string) $detalle->tip_dra));
                $tiposComidaDelDia[$tipoNormalizado] = true;

                // Extraer horario en minutos desde medianoche
                if ($detalle->hor_dra) {
                    $horaStr = Carbon::parse($detalle->hor_dra)->format('H:i:s');
                    $parts = explode(':', $horaStr);
                    $minutosDesdeMedianoche = ((int) $parts[0] * 60) + (int) $parts[1];

                    $categoriaHoraria = match ($tipoNormalizado) {
                        'desayuno' => 'desayuno',
                        'almuerzo' => 'almuerzo',
                        'cena' => 'cena',
                        'media_manana', 'merienda', 'snack' => 'snack',
                        default => null,
                    };

                    if ($categoriaHoraria) {
                        $minutosPorTipoEnDia[$categoriaHoraria][] = $minutosDesdeMedianoche;
                    }
                }
            }

            $diasData[] = [
                'calorias' => $calDia,
                'proteina' => $proDia,
                'carbohidratos' => $carDia,
                'grasas' => $graDia,
                'fibra' => $fibDia,
                'azucar' => $azuDia,
                'sodio' => $sodDia,
            ];

            // Momentos de comida únicos del día
            $momentosComidaPorDia[] = count($tiposComidaDelDia);

            if (isset($tiposComidaDelDia['desayuno'])) {
                $diasConDesayuno++;
            }
            if (isset($tiposComidaDelDia['cena'])) {
                $diasConCena++;
            }
            if (isset($tiposComidaDelDia['media_manana']) || isset($tiposComidaDelDia['merienda']) || isset($tiposComidaDelDia['snack'])) {
                $diasConSnack++;
            }

            // Consolidar hora representativa del día por tipo
            foreach (['desayuno', 'almuerzo', 'cena', 'snack'] as $cat) {
                if (! empty($minutosPorTipoEnDia[$cat])) {
                    $horaMediaDia = array_sum($minutosPorTipoEnDia[$cat]) / count($minutosPorTipoEnDia[$cat]);
                    $horariosPorTipoComida[$cat][] = $horaMediaDia;
                }
            }
        }

        // Promedios y dispersión calórica
        if ($diasRegistrados > 0) {
            $promedioCalorias = round(array_sum(array_column($diasData, 'calorias')) / $diasRegistrados, 2);
            $promedioProteina = round(array_sum(array_column($diasData, 'proteina')) / $diasRegistrados, 2);
            $promedioCarbohidratos = round(array_sum(array_column($diasData, 'carbohidratos')) / $diasRegistrados, 2);
            $promedioGrasas = round(array_sum(array_column($diasData, 'grasas')) / $diasRegistrados, 2);
            $promedioFibra = round(array_sum(array_column($diasData, 'fibra')) / $diasRegistrados, 2);
            $promedioAzucar = round(array_sum(array_column($diasData, 'azucar')) / $diasRegistrados, 2);
            $promedioSodio = round(array_sum(array_column($diasData, 'sodio')) / $diasRegistrados, 2);

            $stdCalorias = $this->calcularDesviacionEstandar(array_column($diasData, 'calorias'));

            // Porcentajes de macronutrientes por energía derivada (P*4, C*4, G*9)
            $energiaPro = $promedioProteina * 4.0;
            $energiaCar = $promedioCarbohidratos * 4.0;
            $energiaGra = $promedioGrasas * 9.0;
            $energiaTotalMacros = $energiaPro + $energiaCar + $energiaGra;

            if ($energiaTotalMacros > 0) {
                $pctProteina = round(($energiaPro / $energiaTotalMacros) * 100, 2);
                $pctCarbohidratos = round(($energiaCar / $energiaTotalMacros) * 100, 2);
                $pctGrasa = round(($energiaGra / $energiaTotalMacros) * 100, 2);
            } else {
                $pctProteina = 0.00;
                $pctCarbohidratos = 0.00;
                $pctGrasa = 0.00;
            }

            $frecuenciaDesayuno = round($diasConDesayuno / $diasRegistrados, 4);
            $frecuenciaCena = round($diasConCena / $diasRegistrados, 4);
            $frecuenciaSnack = round($diasConSnack / $diasRegistrados, 4);
            $promedioComidasDia = round(array_sum($momentosComidaPorDia) / $diasRegistrados, 2);
        } else {
            $promedioCalorias = 0.00;
            $promedioProteina = 0.00;
            $promedioCarbohidratos = 0.00;
            $promedioGrasas = 0.00;
            $promedioFibra = 0.00;
            $promedioAzucar = 0.00;
            $promedioSodio = 0.00;
            $stdCalorias = 0.00;
            $pctProteina = 0.00;
            $pctCarbohidratos = 0.00;
            $pctGrasa = 0.00;
            $frecuenciaDesayuno = 0.0000;
            $frecuenciaCena = 0.0000;
            $frecuenciaSnack = 0.0000;
            $promedioComidasDia = 0.00;
        }

        // Variabilidad horaria agregada (desviación estándar en minutos por momento de comida)
        $variabilidadesTipo = [];
        foreach ($horariosPorTipoComida as $cat => $minutosArray) {
            if (count($minutosArray) >= 2) {
                $variabilidadesTipo[] = $this->calcularDesviacionEstandar($minutosArray);
            }
        }
        $variabilidadHorariaMin = ! empty($variabilidadesTipo)
            ? round(array_sum($variabilidadesTipo) / count($variabilidadesTipo), 2)
            : null;

        $coberturaComposicion = $totalAlimentos > 0
            ? round(($totalAlimentosConComposicion / $totalAlimentos) * 100, 2)
            : 0.00;

        // Contexto antropométrico y demográfico
        $imc = $this->obtenerImcMasReciente($pacienteModel, $fin);
        $cambioPeso = $this->obtenerCambioPesoEstructurado($pacienteModel, $fin);
        $edad = $this->calcularEdad($pacienteModel, $fin);
        $sexo = $this->normalizarSexo($pacienteModel);

        $vector = [
            'cod_pacientes_fnu' => $pacienteModel->cod_pacientes,
            'version_features_fnu' => self::VERSION,
            'fec_inicio_fnu' => $inicio->toDateString(),
            'fec_fin_fnu' => $fin->toDateString(),
            'ventana_dias_fnu' => $ventanaDias,
            'dias_registrados_fnu' => $diasRegistrados,
            'adherencia_registro_fnu' => $adherenciaRegistro,
            'ratio_validacion_fnu' => $ratioValidacion,
            'promedio_calorias_fnu' => $promedioCalorias,
            'std_calorias_fnu' => $stdCalorias,
            'promedio_proteina_g_fnu' => $promedioProteina,
            'promedio_carbohidratos_g_fnu' => $promedioCarbohidratos,
            'promedio_grasas_g_fnu' => $promedioGrasas,
            'promedio_fibra_g_fnu' => $promedioFibra,
            'promedio_azucar_g_fnu' => $promedioAzucar,
            'promedio_sodio_mg_fnu' => $promedioSodio,
            'pct_carbohidratos_fnu' => $pctCarbohidratos,
            'pct_proteina_fnu' => $pctProteina,
            'pct_grasa_fnu' => $pctGrasa,
            'frecuencia_desayuno_fnu' => $frecuenciaDesayuno,
            'frecuencia_cena_fnu' => $frecuenciaCena,
            'frecuencia_snack_fnu' => $frecuenciaSnack,
            'promedio_comidas_dia_fnu' => $promedioComidasDia,
            'variabilidad_horaria_min_fnu' => $variabilidadHorariaMin,
            'cobertura_composicion_pct_fnu' => $coberturaComposicion,
            'dias_calorias_altas_fnu' => null, // NULL en v1.0 hasta documentar umbrales
            'dias_proteina_baja_fnu' => null,  // NULL en v1.0
            'dias_fibra_baja_fnu' => null,     // NULL en v1.0
            'dias_azucar_alta_fnu' => null,    // NULL en v1.0
            'imc_fnu' => $imc,
            'cambio_peso_fnu' => $cambioPeso,
            'edad_fnu' => $edad,
            'sexo_fnu' => $sexo,
        ];

        $vector['vector_json_fnu'] = $vector;

        return $vector;
    }

    /**
     * Extrae y guarda un snapshot inmutable de features. Si ya existe y no se fuerza, retorna el existente.
     */
    public function guardarSnapshot(
        Paciente|int $paciente, 
        ?string $fechaFin = null, 
        int $ventanaDias = 7, 
        bool $forzar = false,
        string $origenDatos = FeatureNutricional::ORIGEN_CLINICO_REAL,
        ?string $referenciaOrigen = null
    ): FeatureNutricional {
        $vector = $this->extraerVector($paciente, $fechaFin, $ventanaDias);
        $vector['origen_datos_fnu'] = $origenDatos;
        $vector['referencia_origen_fnu'] = $referenciaOrigen;

        $existente = FeatureNutricional::where('cod_pacientes_fnu', $vector['cod_pacientes_fnu'])
            ->where('fec_fin_fnu', $vector['fec_fin_fnu'])
            ->where('ventana_dias_fnu', $vector['ventana_dias_fnu'])
            ->where('version_features_fnu', $vector['version_features_fnu'])
            ->where('origen_datos_fnu', $origenDatos)
            ->first();

        if ($existente && ! $forzar) {
            return $existente;
        }

        return FeatureNutricional::create($vector);
    }

    protected function calcularDesviacionEstandar(array $valores): float
    {
        $n = count($valores);
        if ($n <= 1) {
            return 0.00;
        }

        $media = array_sum($valores) / $n;
        $sumaCuadrados = 0.0;

        foreach ($valores as $val) {
            $sumaCuadrados += pow(((float) $val) - $media, 2);
        }

        return round(sqrt($sumaCuadrados / ($n - 1)), 2);
    }

    protected function obtenerImcMasReciente(Paciente $paciente, Carbon $fechaCorte): ?float
    {
        // 1. Buscar en evaluaciones iniciales estructuradas hasta la fecha de corte
        $evaluacion = EvaluacionInicial::where('cod_pacientes_evi', $paciente->cod_pacientes)
            ->whereDate('fec_evi', '<=', $fechaCorte->toDateString())
            ->whereNotNull('imc_evi')
            ->where('imc_evi', '>', 0)
            ->orderByDesc('fec_evi')
            ->orderByDesc('cod_evaluaciones_iniciales')
            ->first();

        if ($evaluacion && (float) $evaluacion->imc_evi > 0) {
            return (float) $evaluacion->imc_evi;
        }

        // 2. Si no hay evaluación, calcular con peso_base y estatura del perfil
        $peso = (float) ($paciente->pes_pac ?? 0);
        $estatura = (float) ($paciente->estatura_pac ?? 0);

        if ($peso > 0 && $estatura > 0) {
            $estaturaM = $estatura > 3 ? $estatura / 100 : $estatura;
            return round($peso / ($estaturaM * $estaturaM), 2);
        }

        return null;
    }

    protected function obtenerCambioPesoEstructurado(Paciente $paciente, Carbon $fechaCorte): ?float
    {
        // Recopilar mediciones de peso fechadas estructuradas hasta la fecha de corte
        $mediciones = collect();

        // Desde evaluaciones iniciales
        $evals = EvaluacionInicial::where('cod_pacientes_evi', $paciente->cod_pacientes)
            ->whereDate('fec_evi', '<=', $fechaCorte->toDateString())
            ->whereNotNull('peso_evi')
            ->where('peso_evi', '>', 0)
            ->get(['fec_evi as fecha', 'peso_evi as peso']);

        foreach ($evals as $e) {
            $mediciones->push([
                'fecha' => Carbon::parse($e->fecha)->toDateString(),
                'peso' => (float) $e->peso,
            ]);
        }

        // Si tenemos al menos 2 mediciones en fechas distintas
        $unicas = $mediciones->sortBy('fecha')->values();

        if ($unicas->count() >= 2) {
            $primera = $unicas->first();
            $ultima = $unicas->last();
            return round($ultima['peso'] - $primera['peso'], 2);
        }

        return null;
    }

    protected function calcularEdad(Paciente $paciente, Carbon $fechaCorte): ?int
    {
        $fechaNac = $paciente->usuario?->fec_nac_usu;
        if (! $fechaNac) {
            return null;
        }

        return Carbon::parse($fechaNac)->diffInYears($fechaCorte);
    }

    protected function normalizarSexo(Paciente $paciente): ?string
    {
        $sexoVal = $paciente->sex_pac;
        $str = $sexoVal instanceof \App\Enums\SexoPaciente ? $sexoVal->value : (string) $sexoVal;
        $str = mb_strtolower(trim($str));

        if (str_starts_with($str, 'm') || $str === 'masculino') {
            return 'masculino';
        }
        if (str_starts_with($str, 'f') || $str === 'femenino') {
            return 'femenino';
        }

        return $str ?: null;
    }
}
