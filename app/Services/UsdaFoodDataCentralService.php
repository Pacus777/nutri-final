<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UsdaFoodDataCentralService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $defaultPageSize;

    public function __construct()
    {
        $this->apiKey = (string) config('services.usda.api_key');
        $this->baseUrl = rtrim((string) config('services.usda.base_url'), '/');
        $this->defaultPageSize = (int) config('services.usda.default_page_size', 20);
    }

    public function searchFoods(string $query, int $pageNumber = 1, ?int $pageSize = null, ?array $dataType = null): array
    {
        $pageSize = $pageSize ?: $this->defaultPageSize;
        $dataType = $dataType ?: [
            'Foundation',
            'SR Legacy',
            'Survey (FNDDS)',
            'Branded',
        ];

        $response = $this->client()
            ->post("{$this->baseUrl}/foods/search", [
                'query' => $query,
                'pageSize' => min($pageSize, 50),
                'pageNumber' => max($pageNumber, 1),
                'dataType' => $dataType,
            ])
            ->throw()
            ->json();

        return $this->normalizeSearchResponse($response);
    }

    public function getFoodById(string|int $fdcId): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/food/{$fdcId}")
            ->throw()
            ->json();

        return $this->normalizeFoodDetail($response);
    }

    protected function client(): PendingRequest
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('USDA no está configurado. Revisa USDA_API_KEY en el archivo .env.');
        }

        $request = Http::acceptJson()
            ->timeout(20)
            ->withQueryParameters([
                'api_key' => $this->apiKey,
            ]);

        if (app()->isLocal() || ! config('services.usda.verify_ssl', true)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    protected function normalizeSearchResponse(array $response): array
    {
        $foods = collect($response['foods'] ?? [])
            ->filter(fn ($food) => filled($food['fdcId'] ?? null) && filled($food['description'] ?? null))
            ->map(function ($food) {
                return [
                    'fdc_id' => $this->toText($food['fdcId'] ?? ''),
                    'description' => $this->toText($food['description'] ?? ''),
                    'brand_name' => $this->toText($food['brandOwner'] ?? $food['brandName'] ?? ''),
                    'data_type' => $this->toText($food['dataType'] ?? ''),
                    'food_category' => $this->toText($food['foodCategory'] ?? ''),
                    'published_date' => $this->toText($food['publishedDate'] ?? ''),
                ];
            })->values()->all();

        return [
            'foods' => $foods,
            'current_page' => (int) ($response['currentPage'] ?? 1),
            'total_pages' => (int) ($response['totalPages'] ?? 1),
            'total_hits' => (int) ($response['totalHits'] ?? count($foods)),
        ];
    }

    protected function normalizeFoodDetail(array $food): array
    {
        $nutrients = collect($food['foodNutrients'] ?? []);

        $getNutrientValue = function (array $names) use ($nutrients): float {
            $match = $nutrients->first(function ($item) use ($names) {
                $name = strtolower((string) ($item['nutrient']['name'] ?? $item['nutrientName'] ?? ''));
                foreach ($names as $candidate) {
                    if ($name === strtolower($candidate)) {
                        return true;
                    }
                }
                return false;
            });

            return (float) ($match['amount'] ?? 0);
        };

        $portion = collect($food['foodPortions'] ?? [])->first();

        $protein = $getNutrientValue(['Protein']);
        $fat = $getNutrientValue(['Total lipid (fat)']);
        $carbohydrate = max(0.0, $getNutrientValue(['Carbohydrate, by difference']));

        $calories = $getNutrientValue([
            'Energy',
            'Energy (Atwater Specific Factors)',
            'Energy (Atwater General Factors)',
        ]);

        if ($calories <= 0 && ($protein > 0 || $fat > 0 || $carbohydrate > 0)) {
            $calories = round(($protein * 4) + ($carbohydrate * 4) + ($fat * 9), 1);
        }

        $category = strtolower($this->toText($food['foodCategory'] ?? ''));
        $description = strtolower($this->toText($food['description'] ?? ''));
        $isBeverage = str_contains($category, 'beverage') ||
            str_contains($category, 'water') ||
            str_contains($category, 'juice') ||
            str_contains($category, 'coffee') ||
            str_contains($category, 'tea') ||
            preg_match('/\b(water|juice|coffee|tea|soda|drink|milk)\b/i', $description);

        if ($isBeverage && preg_match('/\b(tuna|in water|powder)\b/i', $description)) {
            $isBeverage = false;
        }

        $portionUnit = $isBeverage ? 'mililitros' : 'gramos';

        return [
            'fdc_id' => $this->toText($food['fdcId'] ?? ''),
            'description' => $this->toText($food['description'] ?? ''),
            'data_type' => $this->toText($food['dataType'] ?? ''),
            'brand_name' => $this->toText($food['brandOwner'] ?? $food['brandName'] ?? ''),
            'food_category' => $this->toText($food['foodCategory'] ?? ''),
            'portion_description' => $this->toText($portion['modifier'] ?? data_get($portion, 'measureUnit.name') ?? ($isBeverage ? '240 ml' : '100 g')),
            'portion_amount' => (float) ($portion['gramWeight'] ?? ($isBeverage ? 240 : 100)),
            'portion_unit' => $portionUnit,

            'calories' => $calories,
            'protein' => $protein,
            'fat' => $fat,
            'carbohydrate' => $carbohydrate,
            'fiber' => $getNutrientValue(['Fiber, total dietary']),
            'sugar' => $getNutrientValue(['Sugars, total including NLEA']),
            'saturated_fat' => $getNutrientValue(['Fatty acids, total saturated']),
            'monounsaturated_fat' => $getNutrientValue(['Fatty acids, total monounsaturated']),
            'polyunsaturated_fat' => $getNutrientValue(['Fatty acids, total polyunsaturated']),
            'cholesterol' => $getNutrientValue(['Cholesterol']),
            'sodium' => $getNutrientValue(['Sodium, Na']),
            'potassium' => $getNutrientValue(['Potassium, K']),
            'calcium' => $getNutrientValue(['Calcium, Ca']),
            'iron' => $getNutrientValue(['Iron, Fe']),
            'vitamin_a' => $getNutrientValue(['Vitamin A, RAE']),
            'vitamin_c' => $getNutrientValue(['Vitamin C, total ascorbic acid']),
            'vitamin_d' => $getNutrientValue(['Vitamin D (D2 + D3)']),
        ];
    }

    protected function toText(mixed $value): string
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_scalar($item) || $item === null) {
                    $text = trim((string) $item);

                    if ($text !== '') {
                        return $text;
                    }
                }
            }

            return '';
        }

        return trim((string) $value);
    }
}