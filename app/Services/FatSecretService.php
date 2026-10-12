<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FatSecretService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $defaultLanguage;
    protected string $defaultRegion;
    protected int $resultLimit;

    public function __construct()
    {
        $this->clientId = (string) config('services.fatsecret.client_id');
        $this->clientSecret = (string) config('services.fatsecret.client_secret');
        $this->baseUrl = rtrim((string) config('services.fatsecret.base_url'), '/');
        $this->tokenUrl = (string) config('services.fatsecret.token_url');
        $this->defaultLanguage = (string) config('services.fatsecret.default_language', 'es');
        $this->defaultRegion = (string) config('services.fatsecret.default_region', 'BO');
        $this->resultLimit = (int) config('services.fatsecret.result_limit', 20);
    }

    public function searchFoods(string $query, int $page = 0, ?int $limit = null): array
    {
        $limit = $limit ?: $this->resultLimit;

        $response = $this->client()
            ->get("{$this->baseUrl}/foods/search/v3", [
                'search_expression' => $query,
                'page_number' => $page,
                'max_results' => min($limit, 50),
                'format' => 'json',
            ])
            ->throw()
            ->json();

        return $this->normalizeSearchResponse($response);
    }

    public function getFoodById(string|int $foodId): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/food/v4", [
                'food_id' => $foodId,
                'format' => 'json',
            ])
            ->throw()
            ->json();

        return $this->normalizeFoodDetail($response);
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->timeout(20);
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('fatsecret_access_token', now()->addMinutes(50), function () {
            if ($this->clientId === '' || $this->clientSecret === '') {
                throw new RuntimeException('FatSecret no está configurado. Revisa FATSECRET_CLIENT_ID y FATSECRET_CLIENT_SECRET.');
            }

            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'scope' => 'basic',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ])
                ->throw()
                ->json();

            if (!isset($response['access_token'])) {
                throw new RuntimeException('No se pudo obtener el token de FatSecret.');
            }

            return $response['access_token'];
        });
    }

    protected function normalizeSearchResponse(array $response): array
    {
        $foodsNode = $response['foods']['food'] ?? [];

        if (isset($foodsNode['food_id'])) {
            $foodsNode = [$foodsNode];
        }

        $foods = collect($foodsNode)->map(function ($food) {
            return [
                'food_id' => (string) ($food['food_id'] ?? ''),
                'food_name' => (string) ($food['food_name'] ?? ''),
                'food_type' => (string) ($food['food_type'] ?? ''),
                'brand_name' => (string) ($food['brand_name'] ?? ''),
                'food_description' => (string) ($food['food_description'] ?? ''),
                'food_url' => (string) ($food['food_url'] ?? ''),
            ];
        })->values()->all();

        return [
            'foods' => $foods,
            'page_number' => (int) ($response['foods']['page_number'] ?? 0),
            'max_results' => (int) ($response['foods']['max_results'] ?? count($foods)),
            'total_results' => (int) ($response['foods']['total_results'] ?? count($foods)),
        ];
    }

    protected function normalizeFoodDetail(array $response): array
    {
        $food = $response['food'] ?? $response;

        $servings = $food['servings']['serving'] ?? [];

        if (isset($servings['serving_id'])) {
            $servings = [$servings];
        }

        $servings = collect($servings)->map(function ($serving) {
            return [
                'serving_id' => (string) ($serving['serving_id'] ?? ''),
                'serving_description' => (string) ($serving['serving_description'] ?? ''),
                'metric_serving_amount' => (float) ($serving['metric_serving_amount'] ?? 0),
                'metric_serving_unit' => (string) ($serving['metric_serving_unit'] ?? ''),
                'number_of_units' => (float) ($serving['number_of_units'] ?? 0),
                'measurement_description' => (string) ($serving['measurement_description'] ?? ''),
                'calories' => (float) ($serving['calories'] ?? 0),
                'carbohydrate' => (float) ($serving['carbohydrate'] ?? 0),
                'protein' => (float) ($serving['protein'] ?? 0),
                'fat' => (float) ($serving['fat'] ?? 0),
                'saturated_fat' => (float) ($serving['saturated_fat'] ?? 0),
                'polyunsaturated_fat' => (float) ($serving['polyunsaturated_fat'] ?? 0),
                'monounsaturated_fat' => (float) ($serving['monounsaturated_fat'] ?? 0),
                'cholesterol' => (float) ($serving['cholesterol'] ?? 0),
                'sodium' => (float) ($serving['sodium'] ?? 0),
                'potassium' => (float) ($serving['potassium'] ?? 0),
                'fiber' => (float) ($serving['fiber'] ?? 0),
                'sugar' => (float) ($serving['sugar'] ?? 0),
                'vitamin_a' => (float) ($serving['vitamin_a'] ?? 0),
                'vitamin_c' => (float) ($serving['vitamin_c'] ?? 0),
                'calcium' => (float) ($serving['calcium'] ?? 0),
                'iron' => (float) ($serving['iron'] ?? 0),
                'vitamin_d' => (float) ($serving['vitamin_d'] ?? 0),
            ];
        })->values()->all();

        return [
            'food_id' => (string) ($food['food_id'] ?? ''),
            'food_name' => (string) ($food['food_name'] ?? ''),
            'food_type' => (string) ($food['food_type'] ?? ''),
            'brand_name' => (string) ($food['brand_name'] ?? ''),
            'food_url' => (string) ($food['food_url'] ?? ''),
            'servings' => $servings,
        ];
    }
}