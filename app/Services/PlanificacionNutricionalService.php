<?php

namespace App\Services;

use App\Models\ContextoPaciente;
use App\Models\EvaluacionInicial;
use App\Models\ObjetivoNutricional;
use App\Models\AjusteObjetivoNutricional;
use App\Models\PlanNutricional;
use App\Models\Paciente;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;

class PlanificacionNutricionalService
{
    protected $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Validar que el paciente está asignado activamente al nutricionista.
     *
     * @param int $pacienteId
     * @param int $nutricionistaId
     * @return bool
     */
    public function validarPacienteAsignadoANutricionista($pacienteId, $nutricionistaId): bool
    {
        $paciente = Paciente::find($pacienteId);

        if (!$paciente) {
            return false;
        }

        return $paciente->tieneAsignacionActivaCon($nutricionistaId);
    }

    /**
     * Crear una evaluación inicial con cálculo automático de IMC.
     *
     * @param array $data
     * @return EvaluacionInicial
     * @throws \Exception
     */
    public function crearEvaluacionInicial(array $data): EvaluacionInicial
    {
        return DB::transaction(function () use ($data) {
            // Calcular IMC si se proporcionan peso y estatura
            if (isset($data['peso_evi']) && isset($data['estatura_evi'])) {
                $calculoService = app(CalculoClinicoBasicoService::class);
                $data['imc_evi'] = $calculoService->calcularImc(
                    $data['peso_evi'],
                    $data['estatura_evi']
                );
            }

            $evaluacion = EvaluacionInicial::create($data);

            // Si se proporciona contexto general, crear un contexto del paciente
            if (isset($data['contexto_general_evi']) && $data['contexto_general_evi']) {
                ContextoPaciente::create([
                    'cod_pacientes_cxp' => $data['cod_pacientes_evi'],
                    'des_cxp' => $data['contexto_general_evi'],
                    'fec_cxp' => $data['fec_evi'],
                    'tip_cxp' => 'contexto_evaluacion',
                    'est_cxp' => 'activo',
                ]);
            }

            return $evaluacion;
        });
    }

    /**
     * Crear un objetivo nutricional.
     *
     * @param array $data
     * @return ObjetivoNutricional
     */
    public function crearObjetivo(array $data): ObjetivoNutricional
    {
        return ObjetivoNutricional::create($data);
    }

    /**
     * Ajustar un objetivo nutricional con historial.
     *
     * @param int $objetivoId
     * @param array $data
     * @return AjusteObjetivoNutricional
     * @throws \Exception
     */
    public function ajustarObjetivo($objetivoId, array $data): AjusteObjetivoNutricional
    {
        return DB::transaction(function () use ($objetivoId, $data) {
            $objetivo = ObjetivoNutricional::findOrFail($objetivoId);

            // Guardar estados anteriores
            $data['cod_objetivos_nutricionales_ajn'] = $objetivoId;
            $data['estado_anterior_ajn'] = $objetivo->est_obe;
            $data['descripcion_anterior_ajn'] = $objetivo->des_obe;

            $ajuste = AjusteObjetivoNutricional::create($data);

            // Actualizar el objetivo si se proporciona nuevo estado/descripción
            if (isset($data['estado_nuevo_ajn'])) {
                $objetivo->est_obe = $data['estado_nuevo_ajn'];
            }

            if (isset($data['descripcion_nueva_ajn'])) {
                $objetivo->des_obe = $data['descripcion_nueva_ajn'];
            }

            $objetivo->save();

            return $ajuste;
        });
    }

    /**
     * Cerrar un objetivo nutricional.
     *
     * @param int $objetivoId
     * @param string $razon
     * @return bool
     */
    public function cerrarObjetivo($objetivoId, $razon = ''): bool
    {
        return DB::transaction(function () use ($objetivoId, $razon) {
            $objetivo = ObjetivoNutricional::findOrFail($objetivoId);
            $objetivo->cerrar();

            // Registrar el cierre como un ajuste
            AjusteObjetivoNutricional::create([
                'cod_objetivos_nutricionales_ajn' => $objetivoId,
                'cod_nutricionistas_ajn' => $objetivo->cod_nutricionistas_obe,
                'fec_ajn' => now()->toDateString(),
                'des_ajn' => 'Objetivo cerrado' . ($razon ? ': ' . $razon : ''),
                'motivo_ajn' => 'cierre',
                'estado_anterior_ajn' => $objetivo->est_obe,
                'estado_nuevo_ajn' => 'cerrado',
            ]);

            return true;
        });
    }

    /**
     * Crear un plan nutricional, reemplazando plan anterior si es activo.
     *
     * @param array $data
     * @return PlanNutricional
     * @throws \Exception
     */
    public function crearPlan(array $data): PlanNutricional
    {
        return DB::transaction(function () use ($data) {
            $pacienteId = $data['cod_pacientes_pan'];

            // Si el plan es activo/vigente, reemplazar plan anterior
            if (in_array($data['est_pan'], ['activo', 'vigente'])) {
                $planAnterior = PlanNutricional::where('cod_pacientes_pan', $pacienteId)
                    ->whereIn('est_pan', ['activo', 'vigente'])
                    ->first();

                if ($planAnterior) {
                    $planAnterior->reemplazar();
                }
            }

            return PlanNutricional::create($data);
        });
    }

    /**
     * Cerrar un plan nutricional.
     *
     * @param int $planId
     * @return bool
     */
    public function cerrarPlan($planId): bool
    {
        $plan = PlanNutricional::findOrFail($planId);
        return $plan->cerrar();
    }

    /**
     * Reemplazar el plan vigente de un paciente.
     *
     * @param int $pacienteId
     * @param array $nuevoPlanData
     * @return PlanNutricional
     */
    public function reemplazarPlanVigente($pacienteId, array $nuevoPlanData): PlanNutricional
    {
        return DB::transaction(function () use ($pacienteId, $nuevoPlanData) {
            // Buscar y reemplazar plan anterior
            $planAnterior = PlanNutricional::where('cod_pacientes_pan', $pacienteId)
                ->whereIn('est_pan', ['activo', 'vigente'])
                ->first();

            if ($planAnterior) {
                $planAnterior->reemplazar();
            }

            // Crear nuevo plan
            $nuevoPlanData['cod_pacientes_pan'] = $pacienteId;
            $nuevoPlanData['est_pan'] = 'vigente';

            return PlanNutricional::create($nuevoPlanData);
        });
    }

    /**
     * Obtener la planificación completa de un paciente.
     *
     * @param int $pacienteId
     * @return array
     */
    public function obtenerPlanificacionCompleta($pacienteId): array
    {
        $paciente = Paciente::with([
            'usuario',
            'asignacionActiva.nutricionista.usuario',
            'evaluacionInicialActiva.nutricionista',
            'contextosPaciente',
            'objetivosActivos',
            'planNutricionalVigente',
        ])->findOrFail($pacienteId);

        $objetivosGlobales = ObjetivoNutricional::with([
            'nutricionista.usuario',
            'ajustes.nutricionista.usuario',
        ])
            ->whereNull('cod_pacientes_obe')
            ->activos()
            ->latest('fec_obe')
            ->get();

        return [
            'paciente' => $paciente,
            'evaluacion_inicial_activa' => $paciente->evaluacionInicialActiva,
            'contextos' => $paciente->contextosPaciente()->activos()->get(),
            'objetivos_activos' => $paciente->objetivosActivos
                ->merge($objetivosGlobales)
                ->sortByDesc(fn ($objetivo) => optional($objetivo->fec_obe)->timestamp ?? 0)
                ->values(),
            'plan_vigente' => $paciente->planNutricionalVigente,
        ];
    }
}
