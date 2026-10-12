<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistroAlimenticioRequest;
use App\Models\Alimento;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\Paciente;
use App\Models\RegistroAlimenticio;
use App\Models\TablaComposicionNutricional;
use App\Models\ValidacionInformacion;
use App\Services\CalculoNutricionalRegistroService;
use App\Services\FoodNameNormalizerService;
use App\Services\UsdaFoodDataCentralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegistroAlimenticioController extends Controller
{
    public function __construct(
        private readonly UsdaFoodDataCentralService $usdaService,
        private readonly CalculoNutricionalRegistroService $calculoNutricionalService,
        private readonly FoodNameNormalizerService $foodNameNormalizer
    ) {
    }

    public function create(Paciente $paciente, Request $request): Response
    {
        $this->autorizarPaciente($paciente);

        $paciente->load('usuario');

        $registro = $paciente->registrosAlimenticios()
            ->with([
                'detalles.alimento.composicionVigente',
                'ultimaValidacionInformacion.nutricionista.usuario',
            ])
            ->whereDate('fec_ral', $request->input('fecha', now()->toDateString()))
            ->first();

        $fechaSeleccionada = $request->input('fecha', now()->toDateString());
        $esFechaEditable = $this->registroPuedeSerEditadoPorPaciente($registro, $fechaSeleccionada);
        $ultimaRetroalimentacionClinica = $this->ultimaRetroalimentacionClinicaPaciente($paciente, $fechaSeleccionada);

        $alimentosFrecuentes = DetalleRegistroAlimenticio::query()
            ->select('cod_alimentos_dra', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereHas('registroAlimenticio', function ($query) use ($paciente) {
                $query->where('cod_pacientes_ral', $paciente->cod_pacientes);
            })
            ->groupBy('cod_alimentos_dra')
            ->orderByDesc('total')
            ->with(['alimento.composicionVigente'])
            ->limit(8)
            ->get()
            ->map(function ($detalle) {
                $alimento = $detalle->alimento;
                if (!$alimento) return null;
                return [
                    'cod_alimentos' => $alimento->cod_alimentos,
                    'nom_ali' => $alimento->nom_ali,
                    'mar_ali' => $alimento->mar_ali,
                    'tip_ali' => $alimento->tip_ali,
                    'pai_ali' => $alimento->pai_ali,
                    'es_boliviano' => $alimento->pai_ali === 'BO',
                    'origen' => 'local',
                    'calorias' => $alimento->composicionVigente?->cal_tcn ? round((float) $alimento->composicionVigente->cal_tcn, 1) : null,
                    'proteina' => $alimento->composicionVigente?->pro_tcn ? round((float) $alimento->composicionVigente->pro_tcn, 1) : null,
                    'carbohidratos' => $alimento->composicionVigente?->car_tcn ? round((float) $alimento->composicionVigente->car_tcn, 1) : null,
                    'grasa' => $alimento->composicionVigente?->gra_tcn ? round((float) $alimento->composicionVigente->gra_tcn, 1) : null,
                    'por_ref' => $alimento->composicionVigente?->por_ref_tcn ?: '100 g',
                    'cant_ref' => (float) ($alimento->composicionVigente?->cant_ref_tcn ?: 100),
                    'peso_porcion' => $this->calculoNutricionalService->resolverPesoPorcionReferencia($alimento, $alimento->composicionVigente),
                    'es_liquido' => $this->calculoNutricionalService->esAlimentoLiquido($alimento),
                    'unidades_permitidas' => $this->calculoNutricionalService->resolverUnidadesPermitidas($alimento),
                    'nutricion' => $this->mapearNutrientes($alimento->composicionVigente),
                ];
            })
            ->filter()
            ->values();

        if ($alimentosFrecuentes->isEmpty()) {
            $alimentosFrecuentes = Alimento::query()
                ->where('est_ali', Alimento::ESTADO_ACTIVO)
                ->where(function ($q) {
                    $q->where('pai_ali', 'BO')
                        ->orWhereIn('nom_ali', [
                            'Silpancho cochabambino',
                            'Sopa de maní boliviana',
                            'Salteña de carne',
                            'Marraqueta paceña (pan de batalla)',
                            'Cuñapé horneado tradicional',
                            'Quinua real cocida',
                            'Pechuga de pollo a la plancha',
                            'Huevo cocido o duro',
                        ]);
                })
                ->with('composicionVigente')
                ->limit(8)
                ->get()
                ->map(fn ($alimento) => [
                    'cod_alimentos' => $alimento->cod_alimentos,
                    'nom_ali' => $alimento->nom_ali,
                    'mar_ali' => $alimento->mar_ali,
                    'tip_ali' => $alimento->tip_ali,
                    'pai_ali' => $alimento->pai_ali,
                    'es_boliviano' => $alimento->pai_ali === 'BO',
                    'origen' => 'local',
                    'calorias' => $alimento->composicionVigente?->cal_tcn ? round((float) $alimento->composicionVigente->cal_tcn, 1) : null,
                    'proteina' => $alimento->composicionVigente?->pro_tcn ? round((float) $alimento->composicionVigente->pro_tcn, 1) : null,
                    'carbohidratos' => $alimento->composicionVigente?->car_tcn ? round((float) $alimento->composicionVigente->car_tcn, 1) : null,
                    'grasa' => $alimento->composicionVigente?->gra_tcn ? round((float) $alimento->composicionVigente->gra_tcn, 1) : null,
                    'por_ref' => $alimento->composicionVigente?->por_ref_tcn ?: '100 g',
                    'cant_ref' => (float) ($alimento->composicionVigente?->cant_ref_tcn ?: 100),
                    'peso_porcion' => $this->calculoNutricionalService->resolverPesoPorcionReferencia($alimento, $alimento->composicionVigente),
                    'es_liquido' => $this->calculoNutricionalService->esAlimentoLiquido($alimento),
                    'unidades_permitidas' => $this->calculoNutricionalService->resolverUnidadesPermitidas($alimento),
                    'nutricion' => $this->mapearNutrientes($alimento->composicionVigente),
                ])
                ->values();
        }

        $objetivosActivos = $paciente->objetivosNutricionales()
            ->whereIn('est_obe', [
                \App\Models\ObjetivoNutricional::ESTADO_ACTIVO,
                \App\Models\ObjetivoNutricional::ESTADO_PAUSADO,
            ])
            ->latest('fec_obe')
            ->latest('cod_objetivos_nutricionales')
            ->get()
            ->map(fn ($o) => [
                'cod_objetivos_nutricionales' => $o->cod_objetivos_nutricionales,
                'des_obe' => $o->des_obe,
                'est_obe' => $o->est_obe,
                'fec_obe' => optional($o->fec_obe)->format('Y-m-d'),
                'progreso_actual' => (int) ($o->progreso_actual ?? 0),
                'meta_progreso' => (int) ($o->meta_progreso ?: 7),
                'unidad_progreso' => $o->unidad_progreso ?: 'veces',
                'porcentaje' => $o->calcularPorcentajeProgreso(),
            ]);

        return Inertia::render('Paciente/RegistrosAlimenticios/Create', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
            ],
            'fechaSeleccionada' => $fechaSeleccionada,
            'esFechaEditable' => $esFechaEditable,
            'motivoBloqueoEdicion' => $this->motivoBloqueoEdicionPaciente($registro, $fechaSeleccionada),
            'unidadesDisponibles' => DetalleRegistroAlimenticio::UNIDADES,
            'tiposComida' => DetalleRegistroAlimenticio::TIPOS_COMIDA,
            'validacionActual' => $this->mapValidacionPaciente($registro?->ultimaValidacionInformacion),
            'ultimaRetroalimentacionClinica' => $ultimaRetroalimentacionClinica,
            'metasNutricionales' => $this->calcularMetasNutricionales($paciente),
            'alimentosFrecuentes' => $alimentosFrecuentes,
            'objetivosActivos' => $objetivosActivos,
            'registroExistente' => $registro ? [
                    'cod_registros_alimenticios' => $registro->cod_registros_alimenticios,
                    'fec_ral' => optional($registro->fec_ral)->format('Y-m-d'),
                    'est_ral' => $esFechaEditable ? $registro->est_ral : 'cerrado',
                    'detalle' => $registro->detalles->map(fn ($detalle) => [
                        'cod_detalle_registro_alimenticio' => $detalle->cod_detalle_registro_alimenticio,
                        'cod_alimentos_dra' => $detalle->cod_alimentos_dra,
                        'tip_dra' => $detalle->tip_dra,
                        'can_dra' => $detalle->can_dra,
                        'uni_dra' => $detalle->uni_dra,
                        'hor_dra' => $detalle->hor_dra ? $detalle->hor_dra->format('H:i') : '00:00',
                        'cant_ref' => (float) ($detalle->alimento?->composicionVigente?->cant_ref_tcn ?: 100),
                        'peso_porcion' => $detalle->alimento
                            ? $this->calculoNutricionalService->resolverPesoPorcionReferencia($detalle->alimento, $detalle->alimento->composicionVigente)
                            : 100,
                        'es_liquido' => $detalle->alimento
                            ? $this->calculoNutricionalService->esAlimentoLiquido($detalle->alimento)
                            : false,
                        'unidades_permitidas' => $detalle->alimento
                            ? $this->calculoNutricionalService->resolverUnidadesPermitidas($detalle->alimento)
                            : ['gramos', 'porcion', 'unidad'],
                        'nutricion' => $detalle->alimento
                            ? $this->mapearNutrientes($detalle->alimento->composicionVigente)
                            : null,
                        'alimento' => $detalle->alimento ? [
                            'cod_alimentos' => $detalle->alimento->cod_alimentos,
                            'nom_ali' => $detalle->alimento->nom_ali,
                            'tip_ali' => $detalle->alimento->tip_ali,
                            'mar_ali' => $detalle->alimento->mar_ali,
                            'pai_ali' => $detalle->alimento->pai_ali,
                            'src_ali' => $detalle->alimento->src_ali,
                        ] : null,
                    ])->values(),
                ] : null,
            ]);
        
    }

    public function copiarRegistroAnterior(Paciente $paciente, Request $request): RedirectResponse
    {
        $this->autorizarPaciente($paciente);

        $fechaDestino = $request->input('fecha_destino', now()->toDateString());

        // Verificar que la fecha de destino sea editable
        $registroDestino = $paciente->registrosAlimenticios()
            ->whereDate('fec_ral', $fechaDestino)
            ->first();

        if (!$this->registroPuedeSerEditadoPorPaciente($registroDestino, $fechaDestino)) {
            return back()->withErrors(['registro' => 'No puedes editar el registro para esta fecha.']);
        }

        // Buscar el último registro anterior que contenga detalles
        $registroAnterior = $paciente->registrosAlimenticios()
            ->where('fec_ral', '<', $fechaDestino)
            ->whereHas('detalles')
            ->orderByDesc('fec_ral')
            ->first();

        if (!$registroAnterior) {
            return back()->withErrors(['registro' => 'No se encontraron registros anteriores con alimentos para copiar.']);
        }

        // Eliminar detalles previos del día de destino para reescribir limpiamente
        if ($registroDestino) {
            $registroDestino->detalles()->delete();
        } else {
            $registroDestino = $paciente->registrosAlimenticios()->create([
                'fec_ral' => $fechaDestino,
                'est_ral' => 'activo',
            ]);
        }

        // Clonar detalles
        foreach ($registroAnterior->detalles as $detalle) {
            $registroDestino->detalles()->create([
                'cod_alimentos_dra' => $detalle->cod_alimentos_dra,
                'tip_dra' => $detalle->tip_dra,
                'can_dra' => $detalle->can_dra,
                'uni_dra' => $detalle->uni_dra,
                'hor_dra' => $detalle->hor_dra,
            ]);
        }

        return redirect()->route('paciente.registros-alimenticios.create', [
            'paciente' => $paciente->cod_pacientes,
            'fecha' => $fechaDestino,
        ])->with('success', 'Registro copiado con éxito desde el día ' . $registroAnterior->fec_ral->format('Y-m-d'));
    }

    public function buscarAlimentos(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:50'],
            'incluir_usda' => ['nullable', 'boolean'],
        ]);

        $query = trim((string) $request->input('q', ''));
        $categoria = trim((string) $request->input('categoria', ''));
        $incluirUsda = $request->boolean('incluir_usda', false);

        if ($query === '' && $categoria === '') {
            return response()->json([
                'resultados' => [],
                'origen_consulta' => 'local',
                'mensaje' => null,
            ]);
        }

        $queryBuilder = Alimento::query()
            ->where('est_ali', Alimento::ESTADO_ACTIVO)
            ->with('composicionVigente');

        // Filtro por categoría rápida
        if ($categoria !== '') {
            match ($categoria) {
                'bolivia', 'boliviano' => $queryBuilder->where('pai_ali', 'BO'),
                'desayuno' => $queryBuilder->where(function ($q) {
                    $q->where('nom_ali', 'like', '%pan%')
                        ->orWhere('nom_ali', 'like', '%marraqueta%')
                        ->orWhere('nom_ali', 'like', '%avena%')
                        ->orWhere('nom_ali', 'like', '%huevo%')
                        ->orWhere('nom_ali', 'like', '%leche%')
                        ->orWhere('nom_ali', 'like', '%queso%')
                        ->orWhere('nom_ali', 'like', '%café%')
                        ->orWhere('nom_ali', 'like', '%té%')
                        ->orWhere('nom_ali', 'like', '%salteña%')
                        ->orWhere('nom_ali', 'like', '%cuñapé%');
                }),
                'plato', 'almuerzo' => $queryBuilder->where('tip_ali', 'plato'),
                'proteina' => $queryBuilder->where(function ($q) {
                    $q->where('nom_ali', 'like', '%pollo%')
                        ->orWhere('nom_ali', 'like', '%carne%')
                        ->orWhere('nom_ali', 'like', '%res%')
                        ->orWhere('nom_ali', 'like', '%cerdo%')
                        ->orWhere('nom_ali', 'like', '%pescado%')
                        ->orWhere('nom_ali', 'like', '%trucha%')
                        ->orWhere('nom_ali', 'like', '%atún%')
                        ->orWhere('nom_ali', 'like', '%huevo%')
                        ->orWhere('nom_ali', 'like', '%charque%');
                }),
                'fruta_verdura' => $queryBuilder->where(function ($q) {
                    $q->where('tip_ali', 'ingrediente')
                        ->where(function ($sub) {
                            $sub->where('nom_ali', 'like', '%manzana%')
                                ->orWhere('nom_ali', 'like', '%plátano%')
                                ->orWhere('nom_ali', 'like', '%naranja%')
                                ->orWhere('nom_ali', 'like', '%papaya%')
                                ->orWhere('nom_ali', 'like', '%frutilla%')
                                ->orWhere('nom_ali', 'like', '%palta%')
                                ->orWhere('nom_ali', 'like', '%tomate%')
                                ->orWhere('nom_ali', 'like', '%lechuga%')
                                ->orWhere('nom_ali', 'like', '%cebolla%')
                                ->orWhere('nom_ali', 'like', '%zanahoria%')
                                ->orWhere('nom_ali', 'like', '%brócoli%')
                                ->orWhere('nom_ali', 'like', '%espinaca%');
                        });
                }),
                'cereal_tuberculo' => $queryBuilder->where(function ($q) {
                    $q->where('nom_ali', 'like', '%arroz%')
                        ->orWhere('nom_ali', 'like', '%papa%')
                        ->orWhere('nom_ali', 'like', '%quinua%')
                        ->orWhere('nom_ali', 'like', '%chuño%')
                        ->orWhere('nom_ali', 'like', '%tunta%')
                        ->orWhere('nom_ali', 'like', '%yuca%')
                        ->orWhere('nom_ali', 'like', '%fideo%')
                        ->orWhere('nom_ali', 'like', '%camote%')
                        ->orWhere('nom_ali', 'like', '%avena%');
                }),
                'bebida' => $queryBuilder->where('tip_ali', 'bebida'),
                default => null,
            };
        }

        // Búsqueda por texto
        if ($query !== '') {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('nom_ali', 'like', "%{$query}%")
                    ->orWhere('nom_ext_ali', 'like', "%{$query}%")
                    ->orWhere('mar_ali', 'like', "%{$query}%");
            });

            // Ordenamiento por relevancia: Coincidencia de prefijo y bolivianos primero
            $queryBuilder->orderByRaw(
                "CASE 
                    WHEN nom_ali LIKE ? THEN 1
                    WHEN nom_ali LIKE ? THEN 2
                    WHEN pai_ali = 'BO' THEN 3
                    ELSE 4
                END",
                ["{$query}%", "%{$query}%"]
            );
        } else {
            // Si solo filtra por categoría, mostrar bolivianos primero y orden alfabético
            $queryBuilder->orderByRaw("CASE WHEN pai_ali = 'BO' THEN 1 ELSE 2 END")
                ->orderBy('nom_ali');
        }

        $locales = $queryBuilder
            ->limit(20)
            ->get()
            ->map(fn ($alimento) => [
                'origen' => 'local',
                'cod_alimentos' => $alimento->cod_alimentos,
                'nom_ali' => $alimento->nom_ali,
                'mar_ali' => $alimento->mar_ali,
                'tip_ali' => $alimento->tip_ali,
                'pai_ali' => $alimento->pai_ali,
                'es_boliviano' => $alimento->pai_ali === 'BO',
                'calorias' => $alimento->composicionVigente?->cal_tcn ? round((float) $alimento->composicionVigente->cal_tcn, 1) : null,
                'proteina' => $alimento->composicionVigente?->pro_tcn ? round((float) $alimento->composicionVigente->pro_tcn, 1) : null,
                'carbohidratos' => $alimento->composicionVigente?->car_tcn ? round((float) $alimento->composicionVigente->car_tcn, 1) : null,
                'grasa' => $alimento->composicionVigente?->gra_tcn ? round((float) $alimento->composicionVigente->gra_tcn, 1) : null,
                'por_ref' => $alimento->composicionVigente?->por_ref_tcn ?: '100 g',
                'cant_ref' => (float) ($alimento->composicionVigente?->cant_ref_tcn ?: 100),
                'uni_ref' => $alimento->composicionVigente?->uni_ref_tcn ?: 'gramos',
            ])
            ->values();

        // Solo consultamos USDA si el usuario lo solicitó explícitamente o si no hubo resultados locales y la consulta tiene >= 3 letras
        $resultadosUsda = collect();
        $usdaError = null;

        $debeConsultarUsda = $query !== '' && strlen($query) >= 3 && ($incluirUsda || $locales->isEmpty());

        if ($debeConsultarUsda) {
            try {
                $queryTraducida = $this->foodNameNormalizer->traducirConsultaAEspanolAIngles($query);
                $api = $this->usdaService->searchFoods($queryTraducida !== '' ? $queryTraducida : $query);
                if (isset($api['foods']) && is_array($api['foods'])) {
                    $resultadosUsda = collect($api['foods'])
                        ->filter(fn ($food) => filled($food['fdc_id']) && filled($food['description']))
                        ->take(10)
                        ->map(fn ($food) => [
                            'origen' => Alimento::FUENTE_USDA,
                            'fdc_id' => $food['fdc_id'],
                            'nom_ali' => $this->foodNameNormalizer->nombreAmigable($food['description']),
                            'nom_ext_ali' => $food['description'],
                            'mar_ali' => $food['brand_name'] ?: 'USDA Global',
                            'tip_ali' => $this->foodNameNormalizer->tipoAmigable($food['data_type'] ?? null, $food['food_category'] ?? null, $food['description'] ?? ''),
                            'tip_ext_ali' => $food['data_type'] ?? null,
                            'pai_ali' => 'US',
                            'es_boliviano' => false,
                            'calorias' => null,
                            'proteina' => null,
                            'carbohidratos' => null,
                            'grasa' => null,
                            'por_ref' => '100 g',
                            'cant_ref' => 100.0,
                            'uni_ref' => 'gramos',
                            'food_category' => $food['food_category'] ?? null,
                        ]);
                }
            } catch (\Throwable $e) {
                $usdaError = 'No se pudo consultar la base de datos externa de USDA en este momento.';
            }
        }

        $combinados = collect($locales)->concat($resultadosUsda)->take(25)->values();

        return response()->json([
            'resultados' => $combinados,
            'total_locales' => $locales->count(),
            'total_usda' => $resultadosUsda->count(),
            'origen_consulta' => $resultadosUsda->isNotEmpty() ? 'local_usda' : 'local',
            'mensaje' => $usdaError,
        ]);
    }

    public function agregarAlimentoTemporal(Request $request): JsonResponse
    {
        $request->validate([
            'origen' => ['required', 'string', 'in:local,usda'],
            'cod_alimentos' => ['nullable', 'integer'],
            'fdc_id' => ['nullable', 'string'],
        ]);

        if ($request->origen === 'local') {
            $alimento = Alimento::where('cod_alimentos', $request->cod_alimentos)
                ->where('est_ali', Alimento::ESTADO_ACTIVO)
                ->with('composicionVigente')
                ->firstOrFail();

            $esLiquido = $this->calculoNutricionalService->esAlimentoLiquido($alimento);
            $unidadesPermitidas = $this->calculoNutricionalService->resolverUnidadesPermitidas($alimento);
            $pesoPorcion = $this->calculoNutricionalService->resolverPesoPorcionReferencia($alimento, $alimento->composicionVigente);

            $unidadDefecto = $esLiquido ? DetalleRegistroAlimenticio::UNIDAD_MILILITROS : DetalleRegistroAlimenticio::UNIDAD_GRAMOS;
            $cantidadDefecto = (float) ($alimento->composicionVigente?->cant_ref_tcn ?: ($esLiquido ? 240 : 100));

            return response()->json([
                'alimento' => [
                    'cod_alimentos_dra' => $alimento->cod_alimentos,
                    'nombre_alimento' => $alimento->nom_ali,
                    'tip_ali' => $alimento->tip_ali,
                    'mar_ali' => $alimento->mar_ali,
                    'pai_ali' => $alimento->pai_ali,
                    'es_boliviano' => $alimento->pai_ali === 'BO',
                    'origen' => $alimento->src_ali ?: 'local',
                    'por_ref' => $alimento->composicionVigente?->por_ref_tcn,
                    'cant_ref' => (float) ($alimento->composicionVigente?->cant_ref_tcn ?: 100),
                    'es_liquido' => $esLiquido,
                    'unidades_permitidas' => $unidadesPermitidas,
                    'peso_porcion' => $pesoPorcion,
                    'detalle_predefinido' => [
                        'tip_dra' => $this->resolverTipoComidaDefecto(),
                        'can_dra' => $cantidadDefecto,
                        'uni_dra' => $unidadDefecto,
                        'hor_dra' => now()->format('H:i'),
                    ],
                    'nutricion' => $this->mapearNutrientes($alimento->composicionVigente),
                ],
            ]);
        }

        $fdcId = $request->string('fdc_id')->toString();

        $existente = null;

        // Evitar duplicados vía transacción y firstOrCreate; garantizar una composición vigente única.
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($fdcId, &$existente) {
                $existente = Alimento::where('src_ali', Alimento::FUENTE_USDA)
                    ->where('ref_ext_ali', $fdcId)
                    ->lockForUpdate()
                    ->first();

                if (! $existente) {
                    $food = $this->usdaService->getFoodById($fdcId);
                    $nombreOriginal = $food['description'] ?? 'USDA food ' . $fdcId;
                    $nombreAmigable = $this->foodNameNormalizer->nombreAmigable($nombreOriginal);

                    $existente = Alimento::firstOrCreate(
                        ['src_ali' => Alimento::FUENTE_USDA, 'ref_ext_ali' => $food['fdc_id']],
                        [
                            'nom_ali' => $nombreAmigable,
                            'nom_ext_ali' => $nombreOriginal,
                            'tip_ali' => $this->foodNameNormalizer->tipoAmigable($food['data_type'] ?? null, $food['food_category'] ?? null, $nombreOriginal),
                            'des_ali' => $nombreOriginal,
                            'mar_ali' => $food['brand_name'] ?: null,
                            'idi_ali' => 'es',
                            'pai_ali' => 'US',
                            'img_ali' => null,
                            'fec_sync_ali' => now(),
                            'est_ali' => Alimento::ESTADO_ACTIVO,
                        ]
                    );

                    // Archivar composición vigente previa (si existe) y crear o actualizar la nueva como vigente
                    TablaComposicionNutricional::where('cod_alimentos_tcn', $existente->cod_alimentos)
                        ->where('est_tcn', TablaComposicionNutricional::ESTADO_VIGENTE)
                        ->update(['est_tcn' => TablaComposicionNutricional::ESTADO_ARCHIVADA]);

                    TablaComposicionNutricional::updateOrCreate(
                        [
                            'cod_alimentos_tcn' => $existente->cod_alimentos,
                            'fec_tcn' => now()->toDateString(),
                        ],
                        [
                            'por_ref_tcn' => $food['portion_description'],
                            'cant_ref_tcn' => $food['portion_amount'],
                            'uni_ref_tcn' => $food['portion_unit'],
                            'cal_tcn' => $food['calories'],
                            'pro_tcn' => $food['protein'],
                            'gra_tcn' => $food['fat'],
                            'car_tcn' => $food['carbohydrate'],
                            'fib_tcn' => $food['fiber'],
                            'azu_tcn' => $food['sugar'],
                            'gra_sat_tcn' => $food['saturated_fat'],
                            'gra_mono_tcn' => $food['monounsaturated_fat'],
                            'gra_poli_tcn' => $food['polyunsaturated_fat'],
                            'sod_tcn' => $food['sodium'],
                            'pot_tcn' => $food['potassium'],
                            'col_tcn' => $food['cholesterol'],
                            'calcio_tcn' => $food['calcium'],
                            'hierro_tcn' => $food['iron'],
                            'vit_a_tcn' => $food['vitamin_a'],
                            'vit_c_tcn' => $food['vitamin_c'],
                            'vit_d_tcn' => $food['vitamin_d'],
                            'fec_tcn' => now()->toDateString(),
                            'est_tcn' => TablaComposicionNutricional::ESTADO_VIGENTE,
                        ]
                    );
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Si hay colisión por uniq index, intentar recuperar el alimento existente.
            $existente = Alimento::where('src_ali', Alimento::FUENTE_USDA)
                ->where('ref_ext_ali', $fdcId)
                ->first();
        }

        if ($existente->est_ali !== Alimento::ESTADO_ACTIVO) {
            $existente->update([
                'est_ali' => Alimento::ESTADO_ACTIVO,
                'fec_sync_ali' => now(),
            ]);
        }

        $existente->load('composicionVigente');
        $esLiquido = $this->calculoNutricionalService->esAlimentoLiquido($existente);
        $unidadesPermitidas = $this->calculoNutricionalService->resolverUnidadesPermitidas($existente);
        $pesoPorcion = $this->calculoNutricionalService->resolverPesoPorcionReferencia($existente, $existente->composicionVigente);

        $unidadDefecto = $esLiquido ? DetalleRegistroAlimenticio::UNIDAD_MILILITROS : DetalleRegistroAlimenticio::UNIDAD_GRAMOS;
        $cantidadDefecto = (float) ($existente->composicionVigente?->cant_ref_tcn ?: ($esLiquido ? 240 : 100));

        return response()->json([
            'alimento' => [
                'cod_alimentos_dra' => $existente->cod_alimentos,
                'nombre_alimento' => $existente->nom_ali,
                'nombre_original' => $existente->nom_ext_ali,
                'origen' => $existente->src_ali,
                'tip_ali' => $existente->tip_ali,
                'mar_ali' => $existente->mar_ali,
                'pai_ali' => $existente->pai_ali,
                'es_boliviano' => false,
                'por_ref' => $existente->composicionVigente?->por_ref_tcn,
                'cant_ref' => (float) ($existente->composicionVigente?->cant_ref_tcn ?: 100),
                'es_liquido' => $esLiquido,
                'unidades_permitidas' => $unidadesPermitidas,
                'peso_porcion' => $pesoPorcion,
                'detalle_predefinido' => [
                    'tip_dra' => DetalleRegistroAlimenticio::TIPO_DESAYUNO,
                    'can_dra' => $cantidadDefecto,
                    'uni_dra' => $unidadDefecto,
                    'hor_dra' => now()->format('H:i'),
                ],
                'nutricion' => $this->mapearNutrientes($existente->composicionVigente),
            ],
        ]);
    }

    public function store(StoreRegistroAlimenticioRequest $request): RedirectResponse
    {
        if ($request->fec_ral !== now()->toDateString()) {
            return back()->withErrors([
                'fec_ral' => 'Solo puedes editar o registrar el consumo del día actual.',
            ]);
        }

        /** @var RegistroAlimenticio|null $registroExistente */
        $registroExistente = RegistroAlimenticio::query()
            ->with('ultimaValidacionInformacion')
            ->where('cod_pacientes_ral', $request->cod_pacientes_ral)
            ->whereDate('fec_ral', $request->fec_ral)
            ->first();

        // Si ya existe un consolidado nutricional, no permitir edición.
        if ($registroExistente && $registroExistente->composicionNutricionalRegistrada()->exists()) {
            return back()->withErrors([
                'registro' => 'Este registro ya tiene un consolidado nutricional y no puede modificarse.',
            ]);
        }

        if ($registroExistente && ! $registroExistente->puedeSerEditadoPorPaciente()) {
            return back()->withErrors([
                'registro' => $this->motivoBloqueoEdicionPaciente($registroExistente, $request->fec_ral)
                    ?? 'Este registro ya no puede modificarse.',
            ]);
        }

        $registro = RegistroAlimenticio::firstOrCreate(
            [
                'cod_pacientes_ral' => $request->cod_pacientes_ral,
                'fec_ral' => $request->fec_ral,
            ],
            [
                'est_ral' => $request->est_ral,
            ]
        );

        if (! $registro->wasRecentlyCreated) {
            // Evitar reabrir un registro que ya tiene validación por un nutricionista.
            if ($request->est_ral === RegistroAlimenticio::ESTADO_ACTIVO
                && $registro->ultimaValidacionInformacion()->exists()) {
                return back()->withErrors([
                    'registro' => 'No es posible reabrir el registro una vez que ha sido revisado por el nutricionista.',
                ]);
            }

            $registro->update([
                'est_ral' => $request->est_ral,
            ]);

            $registro->detalles()->delete();
        }

        foreach ($request->detalle as $item) {
            DetalleRegistroAlimenticio::create([
                'cod_registros_alimenticios_dra' => $registro->cod_registros_alimenticios,
                'cod_alimentos_dra' => $item['cod_alimentos_dra'],
                'tip_dra' => $item['tip_dra'],
                'can_dra' => $item['can_dra'],
                'uni_dra' => $item['uni_dra'] ?? null,
                'hor_dra' => $item['hor_dra'],
            ]);
        }

        $mensaje = $request->est_ral === RegistroAlimenticio::ESTADO_CERRADO
            ? 'Registro alimenticio enviado a revisión correctamente.'
            : 'Registro alimenticio guardado como borrador correctamente.';

        $paciente = Paciente::with('usuario')->find($request->cod_pacientes_ral);
        $pacienteName = $paciente && $paciente->usuario ? $paciente->usuario->nombre_completo : 'Paciente #' . $request->cod_pacientes_ral;
        $comidas = collect($request->detalle)->map(function($item) {
            $alimento = Alimento::find($item['cod_alimentos_dra']);
            $aliNom = $alimento ? $alimento->nom_ali : 'Alimento #' . $item['cod_alimentos_dra'];
            return "{$item['tip_dra']} ({$item['hor_dra']}): {$aliNom} ({$item['can_dra']} " . ($item['uni_dra'] ?? 'gramos') . ")";
        })->implode(', ');
        
        Log::info("ACTIVIDAD: El paciente '{$pacienteName}' ha guardado su registro alimenticio de la fecha {$request->fec_ral} (Estado: {$request->est_ral}). Consumos: [{$comidas}].");

        // Si el registro se cerró (enviado a revisión), encolamos el cálculo automáticamente
        if ($request->est_ral === RegistroAlimenticio::ESTADO_CERRADO) {
            if (! $registro->composicionNutricionalRegistrada()->exists()) {
                \App\Jobs\CalcularComposicionRegistroJob::dispatch($registro->cod_registros_alimenticios);
                $mensaje .= ' Cálculo encolado. Ejecuta el worker (`php artisan queue:work`) para procesarlo.';
            }

            // Notificación al nutricionista
            $paciente = Paciente::with(['usuario', 'asignacionActiva.nutricionista.usuario'])->find($request->cod_pacientes_ral);
            $nutricionistaUsuario = $paciente?->asignacionActiva?->nutricionista?->usuario;
            if ($paciente && $nutricionistaUsuario) {
                \App\Models\Notificacion::create([
                    'cod_usuarios_not' => $nutricionistaUsuario->cod_usuarios,
                    'tit_not' => 'Nuevo registro alimenticio',
                    'des_not' => 'El paciente ' . ($paciente->usuario?->nombre_completo ?? 'paciente') . ' ha enviado su registro alimenticio de la fecha ' . $request->fec_ral . ' para validación.',
                    'tip_not' => 'validacion_pendiente',
                    'url_not' => route('nutricionista.validaciones-informacion.pendientes'),
                    'est_not' => 'no_leido',
                ]);
            }
        }

        return redirect()
            ->route('paciente.registros-alimenticios.create', [
                'paciente' => $request->cod_pacientes_ral,
                'fecha' => $request->fec_ral,
            ])
            ->with('success', $mensaje);
    }

    private function registroPuedeSerEditadoPorPaciente(?RegistroAlimenticio $registro, string $fecha): bool
    {
        if ($fecha !== now()->toDateString()) {
            return false;
        }

        if (! $registro) {
            return true;
        }

        return $registro->puedeSerEditadoPorPaciente();
    }

    private function motivoBloqueoEdicionPaciente(?RegistroAlimenticio $registro, string $fecha): ?string
    {
        if ($fecha !== now()->toDateString()) {
            return 'Este registro pertenece a un día anterior. Se mantiene como historial y no puede modificarse.';
        }

        if (! $registro) {
            return null;
        }

        $validacion = $registro->ultimaValidacionInformacion;

        if ($validacion) {
            return match ($validacion->est_vin) {
                ValidacionInformacion::ESTADO_ACEPTADO => 'Este registro ya fue validado por el nutricionista. Se mantiene como historial.',
                ValidacionInformacion::ESTADO_OBSERVADO => 'Este registro fue observado por el nutricionista. La observación sirve como retroalimentación para tus próximos registros; el registro pasado no debe modificarse.',
                ValidacionInformacion::ESTADO_RECHAZADO => 'Este registro fue rechazado por el nutricionista. Se conserva como antecedente y no debe modificarse.',
                default => 'Este registro ya fue revisado y no puede modificarse.',
            };
        }

        if ($registro->enviadoARevision()) {
            return 'Este registro ya fue enviado a revisión. No puede modificarse porque ya representa lo que declaraste para este día.';
        }

        return null;
    }

    private function mapValidacionPaciente(?\App\Models\ValidacionInformacion $validacion): ?array
    {
        if (! $validacion) {
            return null;
        }

        return [
            'cod_validaciones_informacion' => $validacion->cod_validaciones_informacion,
            'fec_vin' => optional($validacion->fec_vin)->format('Y-m-d'),
            'est_vin' => $validacion->est_vin,
            'obs_vin' => $validacion->obs_vin,
            'nutricionista' => $validacion->nutricionista ? [
                'cod_nutricionistas' => $validacion->nutricionista->cod_nutricionistas,
                'usuario' => $validacion->nutricionista->usuario ? [
                    'nombre_completo' => $validacion->nutricionista->usuario->nombre_completo,
                    'cor_usu' => $validacion->nutricionista->usuario->cor_usu,
                ] : null,
            ] : null,
        ];
    }

    private function ultimaRetroalimentacionClinicaPaciente(Paciente $paciente, string $fechaActual): ?array
    {
        $validacion = ValidacionInformacion::query()
            ->with([
                'registroAlimenticio',
                'nutricionista.usuario',
            ])
            ->whereIn('est_vin', [
                ValidacionInformacion::ESTADO_OBSERVADO,
                ValidacionInformacion::ESTADO_RECHAZADO,
            ])
            ->whereNotNull('obs_vin')
            ->whereHas('registroAlimenticio', function ($query) use ($paciente, $fechaActual) {
                $query->where('cod_pacientes_ral', $paciente->getKey())
                    ->whereDate('fec_ral', '<', $fechaActual);
            })
            ->orderByDesc('fec_vin')
            ->orderByDesc('cod_validaciones_informacion')
            ->first();

        if (! $validacion) {
            return null;
        }

        return [
            'cod_validaciones_informacion' => $validacion->cod_validaciones_informacion,
            'fec_vin' => optional($validacion->fec_vin)->format('Y-m-d'),
            'est_vin' => $validacion->est_vin,
            'obs_vin' => $validacion->obs_vin,
            'registro' => $validacion->registroAlimenticio ? [
                'cod_registros_alimenticios' => $validacion->registroAlimenticio->cod_registros_alimenticios,
                'fec_ral' => optional($validacion->registroAlimenticio->fec_ral)->format('Y-m-d'),
                'est_ral' => $validacion->registroAlimenticio->est_ral,
            ] : null,
            'nutricionista' => $validacion->nutricionista ? [
                'cod_nutricionistas' => $validacion->nutricionista->cod_nutricionistas,
                'usuario' => $validacion->nutricionista->usuario ? [
                    'nombre_completo' => $validacion->nutricionista->usuario->nombre_completo,
                    'cor_usu' => $validacion->nutricionista->usuario->cor_usu,
                ] : null,
            ] : null,
        ];
    }

    protected function resolverTipoAlimento(array $food): string
    {
        $dataType = Str::lower((string) ($food['data_type'] ?? ''));
        $brandName = Str::lower((string) ($food['brand_name'] ?? ''));

        if (str_contains($dataType, 'branded') || $brandName !== '') {
            return 'producto';
        }

        return 'ingrediente';
    }

    protected function resolverTipoComidaDefecto(): string
    {
        $hora = (int) now()->format('H');

        if ($hora < 10) {
            return 'desayuno';
        }

        if ($hora < 12) {
            return 'media_manana';
        }

        if ($hora < 15) {
            return 'almuerzo';
        }

        if ($hora < 19) {
            return 'merienda';
        }

        if ($hora < 23) {
            return 'cena';
        }

        return 'snack';
    }

    protected function resolverCantidadDefecto(null|string|float|int $cantidad): float
    {
        if ($cantidad === null || $cantidad === '') {
            return 100.0;
        }

        $valor = (float) $cantidad;

        return $valor > 0 ? round($valor, 2) : 100.0;
    }

    protected function normalizarUnidadDefecto(null|string $unidad): string
    {
        $texto = Str::lower(trim((string) $unidad));

        if ($texto === '') {
            return 'gramos';
        }

        return match (true) {
            in_array($texto, ['g', 'gr', 'gram', 'grams', 'gramo', 'gramos'], true) => 'gramos',
            in_array($texto, ['ml', 'milliliter', 'milliliters', 'mililitro', 'mililitros'], true) => 'mililitros',
            in_array($texto, ['unit', 'units', 'unidad', 'unidades', 'piece', 'pieces'], true) => 'unidad',
            in_array($texto, ['portion', 'portions', 'porcion', 'porciones', 'serving', 'servings'], true) => 'porcion',
            in_array($texto, ['cup', 'cups', 'taza', 'tazas'], true) => 'taza',
            in_array($texto, ['tablespoon', 'tablespoons', 'tbsp', 'cucharada', 'cucharadas'], true) => 'cucharada',
            default => 'gramos',
        };
    }

    protected function mapearNutrientes(?TablaComposicionNutricional $composicion): ?array
    {
        if (! $composicion) {
            return null;
        }

        return [
            'porcion_ref' => $composicion->por_ref_tcn,
            'cantidad_ref' => $composicion->cant_ref_tcn,
            'unidad_ref' => $composicion->uni_ref_tcn,
            'calorias' => $composicion->cal_tcn,
            'proteina' => $composicion->pro_tcn,
            'grasa' => $composicion->gra_tcn,
            'carbohidratos' => $composicion->car_tcn,
            'fibra' => $composicion->fib_tcn,
            'azucar' => $composicion->azu_tcn,
            'grasa_saturada' => $composicion->gra_sat_tcn,
            'sodio' => $composicion->sod_tcn,
            'potasio' => $composicion->pot_tcn,
        ];
    }

    public function historial(Paciente $paciente): Response
    {
        $this->autorizarPaciente($paciente);

        $paciente->load('usuario');

        $registros = $paciente->registrosAlimenticios()
            ->with([
                'ultimaValidacionInformacion.nutricionista.usuario',
            ])
            ->withCount('detalles')
            ->orderByDesc('fec_ral')
            ->orderByDesc('cod_registros_alimenticios')
            ->get()
            ->map(fn ($registro) => [
                'cod_registros_alimenticios' => $registro->cod_registros_alimenticios,
                'fec_ral' => optional($registro->fec_ral)->format('Y-m-d'),
                'est_ral' => $registro->est_ral,
                'detalles_count' => $registro->detalles_count,
                'ultima_validacion_informacion' => $this->mapValidacionPaciente(
                    $registro->ultimaValidacionInformacion
                ),
            ]);

        return Inertia::render('Paciente/RegistrosAlimenticios/Historial', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
            ],
            'registros' => $registros,
        ]);
    }

    public function historialCalculos(Paciente $paciente): Response
    {
        $this->autorizarPaciente($paciente);

        $paciente->load('usuario');

        $registrosConCalculo = $paciente->registrosAlimenticios()
            ->withCount('detalles')
            ->with('composicionNutricionalRegistrada')
            ->whereHas('composicionNutricionalRegistrada')
            ->latest('fec_ral')
            ->get()
            ->map(fn ($registro) => [
                'cod_registros_alimenticios' => $registro->cod_registros_alimenticios,
                'fec_ral' => optional($registro->fec_ral)->format('Y-m-d'),
                'est_ral' => $registro->est_ral,
                'detalles_count' => $registro->detalles_count,
                'calculo' => [
                    'cal_cnr' => $registro->composicionNutricionalRegistrada->cal_cnr,
                    'pro_cnr' => $registro->composicionNutricionalRegistrada->pro_cnr,
                    'gra_cnr' => $registro->composicionNutricionalRegistrada->gra_cnr,
                    'car_cnr' => $registro->composicionNutricionalRegistrada->car_cnr,
                    'vit_cnr' => $registro->composicionNutricionalRegistrada->vit_cnr,
                    'min_cnr' => $registro->composicionNutricionalRegistrada->min_cnr,
                    'fecha_calculo' => optional($registro->composicionNutricionalRegistrada->updated_at)->format('Y-m-d H:i'),
                ],
            ])
            ->values();

        return Inertia::render('Paciente/RegistrosAlimenticios/HistorialCalculos', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
            ],
            'registros' => $registrosConCalculo,
        ]);
    }

    public function show(RegistroAlimenticio $registroAlimenticio): Response
    {
        $this->autorizarPacientePropietario($registroAlimenticio);

        $registroAlimenticio->load([
            'paciente.usuario',
            'detalles.alimento.composicionVigente',
            'ultimaValidacionInformacion.nutricionista.usuario',
        ]);

        $resumen = ['cal' => 0.0, 'pro' => 0.0, 'gra' => 0.0, 'car' => 0.0, 'vit' => 0.0, 'min' => 0.0];
        $conDatos = 0;

        $detallesData = $registroAlimenticio->detalles->map(function ($detalle) use (&$resumen, &$conDatos) {
            $comp = $detalle->alimento?->composicionVigente;
            $aporte = null;

            if ($comp) {
                $cantRef = (float) $comp->cant_ref_tcn;
                $cantConsumo = (float) $detalle->can_dra;

                if ($cantRef > 0 && $cantConsumo > 0) {
                    $factor = $cantConsumo / $cantRef;

                    $aporte = [
                        'cal' => round(((float) $comp->cal_tcn) * $factor, 2),
                        'pro' => round(((float) $comp->pro_tcn) * $factor, 2),
                        'gra' => round(((float) $comp->gra_tcn) * $factor, 2),
                        'car' => round(((float) $comp->car_tcn) * $factor, 2),
                    ];

                    $resumen['cal'] += $aporte['cal'];
                    $resumen['pro'] += $aporte['pro'];
                    $resumen['gra'] += $aporte['gra'];
                    $resumen['car'] += $aporte['car'];
                    $resumen['vit'] += (((float) $comp->vit_a_tcn) + ((float) $comp->vit_c_tcn) + ((float) $comp->vit_d_tcn)) * $factor;
                    $resumen['min'] += (((float) $comp->sod_tcn) + ((float) $comp->pot_tcn) + ((float) $comp->calcio_tcn) + ((float) $comp->hierro_tcn)) * $factor;

                    $conDatos++;
                }
            }

            return [
                'cod_detalle_registro_alimenticio' => $detalle->cod_detalle_registro_alimenticio,
                'tip_dra' => $detalle->tip_dra,
                'can_dra' => $detalle->can_dra,
                'uni_dra' => $detalle->uni_dra,
                'hor_dra' => substr((string) $detalle->hor_dra, 0, 5),
                'aporte' => $aporte,
                'alimento' => $detalle->alimento ? [
                    'cod_alimentos' => $detalle->alimento->cod_alimentos,
                    'nom_ali' => $detalle->alimento->nom_ali,
                    'mar_ali' => $detalle->alimento->mar_ali,
                    'tip_ali' => $detalle->alimento->tip_ali,
                ] : null,
            ];
        })->values();

        $resumen = array_map(fn ($v) => round($v, 2), $resumen);

        return Inertia::render('Paciente/RegistrosAlimenticios/Show', [
            'registro' => [
                'cod_registros_alimenticios' => $registroAlimenticio->cod_registros_alimenticios,
                'fec_ral' => optional($registroAlimenticio->fec_ral)->format('Y-m-d'),
                'est_ral' => $registroAlimenticio->est_ral,
                'paciente' => $registroAlimenticio->paciente ? [
                    'cod_pacientes' => $registroAlimenticio->paciente->cod_pacientes,
                    'usuario' => $registroAlimenticio->paciente->usuario ? [
                        'nombre_completo' => $registroAlimenticio->paciente->usuario->nombre_completo,
                        'cor_usu' => $registroAlimenticio->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
                'ultima_validacion_informacion' => $registroAlimenticio->ultimaValidacionInformacion ? [
                    'cod_validaciones_informacion' => $registroAlimenticio->ultimaValidacionInformacion->cod_validaciones_informacion,
                    'fec_vin' => optional($registroAlimenticio->ultimaValidacionInformacion->fec_vin)->format('Y-m-d'),
                    'est_vin' => $registroAlimenticio->ultimaValidacionInformacion->est_vin,
                    'obs_vin' => $registroAlimenticio->ultimaValidacionInformacion->obs_vin,
                    'nutricionista' => $registroAlimenticio->ultimaValidacionInformacion->nutricionista ? [
                        'cod_nutricionistas' => $registroAlimenticio->ultimaValidacionInformacion->nutricionista->cod_nutricionistas,
                        'usuario' => $registroAlimenticio->ultimaValidacionInformacion->nutricionista->usuario ? [
                            'nombre_completo' => $registroAlimenticio->ultimaValidacionInformacion->nutricionista->usuario->nombre_completo,
                            'cor_usu' => $registroAlimenticio->ultimaValidacionInformacion->nutricionista->usuario->cor_usu,
                        ] : null,
                    ] : null,
                ] : null,
                'detalles' => $detallesData,
            ],
            'resumen' => array_merge($resumen, [
                'total_alimentos' => $registroAlimenticio->detalles->count(),
                'con_datos' => $conDatos,
            ]),
        ]);
    }

    public function consolidado(RegistroAlimenticio $registroAlimenticio): Response|RedirectResponse
    {
        $this->autorizarPacientePropietario($registroAlimenticio);

        $registroAlimenticio->load([
            'paciente.usuario',
            'composicionNutricionalRegistrada',
            'detalles.alimento',
        ]);

        if (! $registroAlimenticio->composicionNutricionalRegistrada) {
            return redirect()
                ->route('paciente.registros-alimenticios.show', $registroAlimenticio->cod_registros_alimenticios)
                ->withErrors([
                    'calculo' => 'Este registro aun no tiene consolidado nutricional calculado.',
                ]);
        }

        return Inertia::render('Paciente/RegistrosAlimenticios/Consolidado', [
            'registro' => [
                'cod_registros_alimenticios' => $registroAlimenticio->cod_registros_alimenticios,
                'fec_ral' => optional($registroAlimenticio->fec_ral)->format('Y-m-d'),
                'est_ral' => $registroAlimenticio->est_ral,
                'paciente' => $registroAlimenticio->paciente ? [
                    'cod_pacientes' => $registroAlimenticio->paciente->cod_pacientes,
                    'usuario' => $registroAlimenticio->paciente->usuario ? [
                        'nombre_completo' => $registroAlimenticio->paciente->usuario->nombre_completo,
                        'cor_usu' => $registroAlimenticio->paciente->usuario->cor_usu,
                    ] : null,
                ] : null,
                'detalles_count' => $registroAlimenticio->detalles->count(),
            ],
            'consolidado' => [
                'cal_cnr' => $registroAlimenticio->composicionNutricionalRegistrada->cal_cnr,
                'pro_cnr' => $registroAlimenticio->composicionNutricionalRegistrada->pro_cnr,
                'gra_cnr' => $registroAlimenticio->composicionNutricionalRegistrada->gra_cnr,
                'car_cnr' => $registroAlimenticio->composicionNutricionalRegistrada->car_cnr,
                'vit_cnr' => $registroAlimenticio->composicionNutricionalRegistrada->vit_cnr,
                'min_cnr' => $registroAlimenticio->composicionNutricionalRegistrada->min_cnr,
                'actualizado_en' => optional($registroAlimenticio->composicionNutricionalRegistrada->updated_at)->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function calcularComposicion(RegistroAlimenticio $registroAlimenticio): RedirectResponse
    {
        $this->autorizarPacientePropietario($registroAlimenticio);

        // Validar condiciones básicas antes de encolar el cálculo
        if ($registroAlimenticio->composicionNutricionalRegistrada()->exists()) {
            return back()->withErrors([
                'calculo' => 'Este registro ya tiene un consolidado nutricional calculado.',
            ]);
        }

        $validacion = $this->calculoNutricionalService->validarCalculable($registroAlimenticio, true);

        if (! $validacion['valido']) {
            return back()->withErrors([
                'calculo' => $this->construirMensajeErrorCalculo($validacion['errores']),
            ]);
        }

        // Encolar el cálculo para procesarlo en background
        \App\Jobs\CalcularComposicionRegistroJob::dispatch($registroAlimenticio->cod_registros_alimenticios);

        return redirect()
            ->route('paciente.registros-alimenticios.show', $registroAlimenticio->cod_registros_alimenticios)
            ->with('success', 'Cálculo encolado. Ejecuta el worker (`php artisan queue:work`) para procesarlo.');
    }

    protected function autorizarPaciente(Paciente $paciente): void
    {
        $usuario = Auth::user();

        abort_if(
            (int) $paciente->cod_usuarios_pac !== (int) $usuario?->cod_usuarios,
            403,
            'No tienes permiso para acceder a esta informacion.'
        );
    }

    protected function autorizarPacientePropietario(RegistroAlimenticio $registroAlimenticio): void
    {
        $registroAlimenticio->loadMissing('paciente');

        $this->autorizarPaciente($registroAlimenticio->paciente);
    }

    protected function construirMensajeErrorCalculo(array $errores): string
    {
        $mensajes = [];

        foreach ($errores as $error) {
            if (($error['codigo'] ?? '') === 'detalle_no_calculable' && ! empty($error['detalles'])) {
                $nombres = collect($error['detalles'])
                    ->map(fn ($detalle) => $detalle['alimento'] ?? "Alimento #{$detalle['cod_alimentos_dra']}")
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($nombres)) {
                    $mensajes[] = 'Faltan composiciones o hay unidades incompatibles en: '.implode(', ', $nombres).'.';
                    continue;
                }
            }

            if (! empty($error['mensaje'])) {
                $mensajes[] = $error['mensaje'];
            }
        }

        return empty($mensajes)
            ? 'No se pudo calcular la composicion nutricional del registro.'
            : implode(' ', $mensajes);
    }

    private function calcularMetasNutricionales(Paciente $paciente): array
    {
        $evaluacion = $paciente->evaluacionesIniciales()->where('est_evi', 'activa')->first();
        
        $peso = $evaluacion?->peso_evi ?? $paciente->pes_pac ?? 70;
        $estatura = $evaluacion?->estatura_evi ?? $paciente->estatura_pac ?? 1.70;
        $sexoVal = $paciente->sex_pac;
        $sexoString = $sexoVal instanceof \App\Enums\SexoPaciente ? $sexoVal->value : ($sexoVal ?? 'm');
        $sexo = strtolower($sexoString);
        $edad = $paciente->edad ?? 30;

        if ($estatura > 3) {
            $estatura = $estatura / 100;
        }
        $estaturaCm = $estatura * 100;

        if (str_starts_with($sexo, 'f') || $sexo === 'femenino') {
            $tmb = 447.593 + (9.247 * floatval($peso)) + (3.098 * floatval($estaturaCm)) - (4.330 * intval($edad));
        } else {
            $tmb = 88.362 + (13.397 * floatval($peso)) + (4.799 * floatval($estaturaCm)) - (5.677 * intval($edad));
        }

        $caloriasMeta = round($tmb * 1.375);

        if ($caloriasMeta < 1200 || $caloriasMeta > 5000) {
            $caloriasMeta = 2000;
        }

        $carbohidratosMeta = round(($caloriasMeta * 0.55) / 4);
        $proteinaMeta = round(($caloriasMeta * 0.15) / 4);
        $grasaMeta = round(($caloriasMeta * 0.30) / 9);

        return [
            'calorias' => $caloriasMeta,
            'proteina' => $proteinaMeta,
            'carbohidratos' => $carbohidratosMeta,
            'grasa' => $grasaMeta,
        ];
    }
}