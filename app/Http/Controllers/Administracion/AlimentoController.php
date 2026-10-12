<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchAlimentoUsdaRequest;
use App\Models\Alimento;
use App\Models\TablaComposicionNutricional;
use App\Services\UsdaFoodDataCentralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AlimentoController extends Controller
{
    public function __construct(
        protected UsdaFoodDataCentralService $usdaService,
        protected \App\Services\FoodNameNormalizerService $foodNameNormalizer
    ) {
    }

    public function buscar(Request $request): Response
    {
        return Inertia::render('Alimentos/Buscar', [
            'query' => $request->string('q')->toString(),
            'resultados' => [],
            'error_api' => null,
        ]);
    }

    public function resultados(SearchAlimentoUsdaRequest $request): Response
    {
        try {
            $rawQuery = $request->string('q')->toString();
            $searchQuery = $this->foodNameNormalizer->traducirConsultaAEspanolAIngles($rawQuery);

            $data = $this->usdaService->searchFoods(
                query: $searchQuery !== '' ? $searchQuery : $rawQuery,
                pageNumber: (int) $request->integer('page', 1)
            );

            $foodsTraducidos = collect($data['foods'])->map(function ($food) {
                $food['description_es'] = $this->foodNameNormalizer->nombreAmigable($food['description']);
                return $food;
            })->all();

            return Inertia::render('Alimentos/Buscar', [
                'query' => $rawQuery,
                'resultados' => $foodsTraducidos,
                'error_api' => null,
            ]);
        } catch (\Throwable $e) {
            return Inertia::render('Alimentos/Buscar', [
                'query' => $request->string('q')->toString(),
                'resultados' => [],
                'error_api' => 'No se pudo consultar USDA FoodData Central en este momento.',
            ]);
        }
    }

    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'fdc_id' => ['required', 'string'],
        ]);

        $fdcId = $request->string('fdc_id')->toString();

        $alimentoExistente = Alimento::where('src_ali', 'usda')
            ->where('ref_ext_ali', $fdcId)
            ->first();

        if ($alimentoExistente) {
            return redirect()
                ->route('alimentos.show', $alimentoExistente->cod_alimentos)
                ->with('success', 'El alimento ya estaba importado en el catálogo local.');
        }

        $food = $this->usdaService->getFoodById($fdcId);

        $nombreAmigable = $this->foodNameNormalizer->nombreAmigable($food['description']);

        $alimento = Alimento::create([
            'nom_ali' => $nombreAmigable,
            'nom_ext_ali' => $food['description'],
            'tip_ali' => $this->resolverTipoAlimento($food),
            'des_ali' => $food['food_category'] ?: null,
            'mar_ali' => $food['brand_name'] ?: null,
            'src_ali' => 'usda',
            'ref_ext_ali' => $food['fdc_id'],
            'idi_ali' => 'es',
            'pai_ali' => 'US',
            'img_ali' => null,
            'fec_sync_ali' => now(),
            'est_ali' => 'activo',
        ]);

        TablaComposicionNutricional::create([
            'cod_alimentos_tcn' => $alimento->cod_alimentos,
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
            'est_tcn' => 'vigente',
        ]);

        return redirect()
            ->route('alimentos.show', $alimento->cod_alimentos)
            ->with('success', 'Alimento importado correctamente desde USDA.');
    }

    public function show(Alimento $alimento): Response
    {
        $alimento->load('composicionVigente');

        return Inertia::render('Alimentos/Show', [
            'alimento' => [
                'cod_alimentos' => $alimento->cod_alimentos,
                'nom_ali' => $alimento->nom_ali,
                'nom_ext_ali' => $alimento->nom_ext_ali,
                'tip_ali' => $alimento->tip_ali,
                'des_ali' => $alimento->des_ali,
                'mar_ali' => $alimento->mar_ali,
                'src_ali' => $alimento->src_ali,
                'ref_ext_ali' => $alimento->ref_ext_ali,
                'idi_ali' => $alimento->idi_ali,
                'pai_ali' => $alimento->pai_ali,
                'img_ali' => $alimento->img_ali,
                'est_ali' => $alimento->est_ali,
                'fec_sync_ali' => optional($alimento->fec_sync_ali)->format('Y-m-d H:i'),
                'composicion_vigente' => $alimento->composicionVigente ? [
                    'por_ref_tcn' => $alimento->composicionVigente->por_ref_tcn,
                    'cant_ref_tcn' => $alimento->composicionVigente->cant_ref_tcn,
                    'uni_ref_tcn' => $alimento->composicionVigente->uni_ref_tcn,
                    'cal_tcn' => $alimento->composicionVigente->cal_tcn,
                    'pro_tcn' => $alimento->composicionVigente->pro_tcn,
                    'gra_tcn' => $alimento->composicionVigente->gra_tcn,
                    'car_tcn' => $alimento->composicionVigente->car_tcn,
                    'fib_tcn' => $alimento->composicionVigente->fib_tcn,
                    'azu_tcn' => $alimento->composicionVigente->azu_tcn,
                    'gra_sat_tcn' => $alimento->composicionVigente->gra_sat_tcn,
                    'gra_mono_tcn' => $alimento->composicionVigente->gra_mono_tcn,
                    'gra_poli_tcn' => $alimento->composicionVigente->gra_poli_tcn,
                    'sod_tcn' => $alimento->composicionVigente->sod_tcn,
                    'pot_tcn' => $alimento->composicionVigente->pot_tcn,
                    'col_tcn' => $alimento->composicionVigente->col_tcn,
                    'calcio_tcn' => $alimento->composicionVigente->calcio_tcn,
                    'hierro_tcn' => $alimento->composicionVigente->hierro_tcn,
                    'vit_a_tcn' => $alimento->composicionVigente->vit_a_tcn,
                    'vit_c_tcn' => $alimento->composicionVigente->vit_c_tcn,
                    'vit_d_tcn' => $alimento->composicionVigente->vit_d_tcn,
                ] : null,
            ],
        ]);
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
}