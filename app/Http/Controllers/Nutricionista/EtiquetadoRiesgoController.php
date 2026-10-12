<?php

namespace App\Http\Controllers\Nutricionista;

use App\Http\Controllers\Controller;
use App\Models\EtiquetaRiesgoProfesional;
use App\Models\FeatureNutricional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EtiquetadoRiesgoController extends Controller
{
    /**
     * Muestra la lista de snapshots clínicos disponibles para etiquetado profesional de riesgo.
     */
    public function index(Request $request): Response
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;
        $nutricionistaId = $nutricionista?->cod_nutricionistas;

        $query = FeatureNutricional::query()
            ->with(['paciente.usuario', 'etiquetasRiesgo' => function ($q) use ($nutricionistaId) {
                if ($nutricionistaId) {
                    $q->where('cod_nutricionistas_erp', $nutricionistaId);
                }
            }])
            ->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)
            ->orderByDesc('fec_fin_fnu');

        if ($request->filled('estado')) {
            if ($request->estado === 'evaluado') {
                $query->whereHas('etiquetasRiesgo', function ($q) use ($nutricionistaId) {
                    if ($nutricionistaId) {
                        $q->where('cod_nutricionistas_erp', $nutricionistaId);
                    }
                });
            } elseif ($request->estado === 'pendiente') {
                $query->whereDoesntHave('etiquetasRiesgo', function ($q) use ($nutricionistaId) {
                    if ($nutricionistaId) {
                        $q->where('cod_nutricionistas_erp', $nutricionistaId);
                    }
                });
            }
        }

        $snapshots = $query->paginate(15)->through(function (FeatureNutricional $snap) use ($nutricionistaId) {
            $miEtiqueta = $snap->etiquetasRiesgo->firstWhere('cod_nutricionistas_erp', $nutricionistaId);

            return [
                'cod_features_nutricionales' => $snap->cod_features_nutricionales,
                'paciente' => [
                    'cod_pacientes' => $snap->paciente?->cod_pacientes,
                    'nombre_completo' => $snap->paciente?->usuario ? ($snap->paciente->usuario->nom_usu . ' ' . $snap->paciente->usuario->ape_usu) : 'Paciente #' . $snap->cod_pacientes_fnu,
                    'cedula' => $snap->paciente?->usuario?->ced_usu,
                ],
                'periodo' => [
                    'inicio' => $snap->fec_inicio_fnu ? $snap->fec_inicio_fnu->format('d/m/Y') : '-',
                    'fin' => $snap->fec_fin_fnu ? $snap->fec_fin_fnu->format('d/m/Y') : '-',
                    'ventana_dias' => $snap->ventana_dias_fnu,
                ],
                'dias_registrados' => $snap->dias_registrados_fnu,
                'adherencia_pct' => round(((float) $snap->adherencia_registro_fnu) * 100, 1),
                'promedio_calorias' => $snap->promedio_calorias_fnu,
                'mi_evaluacion' => $miEtiqueta ? [
                    'nivel_riesgo' => $miEtiqueta->nivel_riesgo_erp,
                    'estado' => $miEtiqueta->estado_etiqueta_erp,
                    'tipo_riesgo' => $miEtiqueta->tipo_riesgo_erp,
                    'fec_evaluacion' => $miEtiqueta->fec_evaluacion_erp ? $miEtiqueta->fec_evaluacion_erp->format('d/m/Y H:i') : null,
                ] : null,
            ];
        });

        // Contadores
        $totalSnapshots = FeatureNutricional::where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL)->count();
        $totalEvaluadosPorMi = $nutricionistaId
            ? EtiquetaRiesgoProfesional::where('cod_nutricionistas_erp', $nutricionistaId)
                ->whereHas('featureNutricional', fn ($q) => $q->where('origen_datos_fnu', FeatureNutricional::ORIGEN_CLINICO_REAL))
                ->count()
            : 0;

        return Inertia::render('Nutricionistas/EtiquetadoRiesgo/Index', [
            'snapshots' => $snapshots,
            'filtros' => $request->only(['estado']),
            'estadisticas' => [
                'total_snapshots' => $totalSnapshots,
                'evaluados' => $totalEvaluadosPorMi,
                'pendientes' => max(0, $totalSnapshots - $totalEvaluadosPorMi),
            ],
        ]);
    }

    /**
     * Muestra la pantalla independiente para evaluar un snapshot clínico.
     * PREVENCIÓN DE SESGO: No se incluye ninguna sugerencia algorítmica ni K-Means.
     */
    public function show(FeatureNutricional $featureNutricional): Response
    {
        // Asegurar que sea clínico real
        if ($featureNutricional->origen_datos_fnu !== FeatureNutricional::ORIGEN_CLINICO_REAL) {
            abort(404, 'El snapshot seleccionado no corresponde a un registro clínico operativo.');
        }

        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;
        $nutricionistaId = $nutricionista?->cod_nutricionistas;

        $featureNutricional->loadMissing(['paciente.usuario']);
        $paciente = $featureNutricional->paciente;

        // Evaluación previa del usuario actual si existe
        $miEtiqueta = $nutricionistaId
            ? EtiquetaRiesgoProfesional::where('cod_features_nutricionales_erp', $featureNutricional->cod_features_nutricionales)
                ->where('cod_nutricionistas_erp', $nutricionistaId)
                ->where('version_criterio_erp', 'v1.0')
                ->first()
            : null;

        // Construir datos nutricionales objetivos y limpios (sin heurísticas ni ML)
        $snapshotData = [
            'cod_features_nutricionales' => $featureNutricional->cod_features_nutricionales,
            'paciente' => [
                'cod_pacientes' => $paciente?->cod_pacientes,
                'nombre_completo' => $paciente?->usuario ? ($paciente->usuario->nom_usu . ' ' . $paciente->usuario->ape_usu) : 'Paciente #' . $featureNutricional->cod_pacientes_fnu,
                'edad' => $featureNutricional->edad_fnu ?? $paciente?->edad,
                'sexo' => $featureNutricional->sexo_fnu ?? $paciente?->sex_pac?->value ?? $paciente?->sex_pac,
                'imc' => $featureNutricional->imc_fnu,
                'cambio_peso' => $featureNutricional->cambio_peso_fnu,
            ],
            'periodo' => [
                'fec_inicio' => $featureNutricional->fec_inicio_fnu ? $featureNutricional->fec_inicio_fnu->format('d/m/Y') : '-',
                'fec_fin' => $featureNutricional->fec_fin_fnu ? $featureNutricional->fec_fin_fnu->format('d/m/Y') : '-',
                'ventana_dias' => $featureNutricional->ventana_dias_fnu,
            ],
            'adherencia' => [
                'dias_registrados' => $featureNutricional->dias_registrados_fnu,
                'total_ventana' => $featureNutricional->ventana_dias_fnu,
                'adherencia_pct' => round(((float) $featureNutricional->adherencia_registro_fnu) * 100, 1),
                'ratio_validacion' => round(((float) $featureNutricional->ratio_validacion_fnu) * 100, 1),
            ],
            'nutricion' => [
                'promedio_calorias' => $featureNutricional->promedio_calorias_fnu,
                'std_calorias' => $featureNutricional->std_calorias_fnu,
                'promedio_proteina_g' => $featureNutricional->promedio_proteina_g_fnu,
                'pct_proteina' => $featureNutricional->pct_proteina_fnu,
                'promedio_carbohidratos_g' => $featureNutricional->promedio_carbohidratos_g_fnu,
                'pct_carbohidratos' => $featureNutricional->pct_carbohidratos_fnu,
                'promedio_grasas_g' => $featureNutricional->promedio_grasas_g_fnu,
                'pct_grasa' => $featureNutricional->pct_grasa_fnu,
                'promedio_fibra_g' => $featureNutricional->promedio_fibra_g_fnu,
                'promedio_azucar_g' => $featureNutricional->promedio_azucar_g_fnu,
                'promedio_sodio_mg' => $featureNutricional->promedio_sodio_mg_fnu,
            ],
            'habitos' => [
                'promedio_comidas_dia' => $featureNutricional->promedio_comidas_dia_fnu,
                'frecuencia_desayuno_pct' => round(((float) $featureNutricional->frecuencia_desayuno_fnu) * 100, 1),
                'frecuencia_cena_pct' => round(((float) $featureNutricional->frecuencia_cena_fnu) * 100, 1),
                'frecuencia_snack_pct' => round(((float) $featureNutricional->frecuencia_snack_fnu) * 100, 1),
                'variabilidad_horaria_min' => $featureNutricional->variabilidad_horaria_min_fnu,
                'cobertura_composicion_pct' => $featureNutricional->cobertura_composicion_pct_fnu,
            ],
        ];

        return Inertia::render('Nutricionistas/EtiquetadoRiesgo/Show', [
            'snapshot' => $snapshotData,
            'mi_evaluacion' => $miEtiqueta ? [
                'cod_etiquetas_riesgo_profesional' => $miEtiqueta->cod_etiquetas_riesgo_profesional,
                'nivel_riesgo_erp' => $miEtiqueta->nivel_riesgo_erp,
                'tipo_riesgo_erp' => $miEtiqueta->tipo_riesgo_erp,
                'estado_etiqueta_erp' => $miEtiqueta->estado_etiqueta_erp,
                'observacion_profesional_erp' => $miEtiqueta->observacion_profesional_erp,
                'version_criterio_erp' => $miEtiqueta->version_criterio_erp,
                'fec_evaluacion_erp' => $miEtiqueta->fec_evaluacion_erp ? $miEtiqueta->fec_evaluacion_erp->format('d/m/Y H:i') : null,
            ] : null,
            'catalogo_tipos_riesgo' => EtiquetaRiesgoProfesional::TIPOS_RIESGO_CATALOGO,
        ]);
    }

    /**
     * Guarda o actualiza la etiqueta profesional de riesgo para el snapshot dado.
     */
    public function store(Request $request, FeatureNutricional $featureNutricional): RedirectResponse
    {
        if ($featureNutricional->origen_datos_fnu !== FeatureNutricional::ORIGEN_CLINICO_REAL) {
            abort(403, 'Solo se permite etiquetar snapshots con procedencia clínica real.');
        }

        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;

        if (! $nutricionista) {
            abort(403, 'El usuario autenticado no posee un perfil activo de nutricionista.');
        }

        $request->validate([
            'opcion_riesgo' => ['required', 'string', 'in:bajo,moderado,alto,insuficiente'],
            'tipo_riesgo_erp' => ['nullable', 'string', 'max:50'],
            'observacion_profesional_erp' => ['nullable', 'string', 'max:1000'],
            'version_criterio_erp' => ['nullable', 'string', 'max:10'],
        ]);

        $opcionRiesgo = $request->input('opcion_riesgo');
        $versionCriterio = $request->input('version_criterio_erp') ?: 'v1.0';

        if ($opcionRiesgo === 'insuficiente') {
            $estadoEtiqueta = EtiquetaRiesgoProfesional::ESTADO_INSUFICIENTE;
            $nivelRiesgo = null;
        } else {
            $estadoEtiqueta = EtiquetaRiesgoProfesional::ESTADO_VALIDA;
            $nivelRiesgo = $opcionRiesgo;
        }

        EtiquetaRiesgoProfesional::updateOrCreate(
            [
                'cod_features_nutricionales_erp' => $featureNutricional->cod_features_nutricionales,
                'cod_nutricionistas_erp' => $nutricionista->cod_nutricionistas,
                'version_criterio_erp' => $versionCriterio,
            ],
            [
                'cod_pacientes_erp' => $featureNutricional->cod_pacientes_fnu,
                'nivel_riesgo_erp' => $nivelRiesgo,
                'tipo_riesgo_erp' => $request->input('tipo_riesgo_erp'),
                'estado_etiqueta_erp' => $estadoEtiqueta,
                'observacion_profesional_erp' => $request->input('observacion_profesional_erp'),
                'fec_evaluacion_erp' => now(),
            ]
        );

        return redirect()->route('nutricionista.etiquetado-riesgo.index')
            ->with('success', 'Evaluación profesional de riesgo registrada correctamente.');
    }
}
