<?php

namespace App\Console\Commands;

use App\Models\Alimento;
use App\Models\TablaComposicionNutricional;
use App\Services\FoodNameNormalizerService;
use App\Services\UsdaFoodDataCentralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportarAlimentosUsdaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alimentos:importar-usda 
                            {--categoria=todo : Categoría a importar (proteinas, pescados, lacteos, cereales, frutas, verduras, grasas, todo)}
                            {--query= : Consulta libre de alimento para buscar e importar (en español o inglés)}
                            {--limite=5 : Número máximo de alimentos a importar por cada consulta}
                            {--dry-run : Simula la importación mostrando los datos sin guardarlos en la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa y normaliza alimentos de la API de USDA al catálogo local traducidos al español con su composición nutricional';

    /**
     * Catálogo curado de consultas esenciales por categoría.
     * Enfocado en alimentos básicos y Foundation/SR Legacy de alta precisión nutricional.
     */
    private const CONSULTAS_POR_CATEGORIA = [
        'proteinas' => [
            'chicken breast raw',
            'chicken breast cooked',
            'beef steak raw',
            'ground beef 90% lean',
            'pork tenderloin raw',
            'turkey breast raw',
            'egg whole',
            'egg white',
        ],
        'pescados' => [
            'salmon raw',
            'tuna canned in water',
            'trout raw',
            'tilapia raw',
            'shrimp raw',
            'sardines canned',
            'cod raw',
        ],
        'lacteos' => [
            'milk whole',
            'milk skim',
            'greek yogurt plain',
            'mozzarella cheese',
            'cheddar cheese',
            'cottage cheese',
            'parmesan cheese',
        ],
        'cereales' => [
            'rolled oats',
            'brown rice raw',
            'white rice raw',
            'quinoa raw',
            'lentils raw',
            'chickpeas raw',
            'black beans raw',
            'whole wheat bread',
        ],
        'frutas' => [
            'apple raw',
            'banana raw',
            'strawberries raw',
            'blueberries raw',
            'avocado raw',
            'orange raw',
            'pineapple raw',
            'watermelon raw',
            'peach raw',
        ],
        'verduras' => [
            'broccoli raw',
            'spinach raw',
            'carrots raw',
            'tomatoes raw',
            'cucumber raw',
            'lettuce romaine raw',
            'bell pepper raw',
            'onion raw',
        ],
        'grasas' => [
            'olive oil',
            'almonds raw',
            'walnuts raw',
            'chia seeds',
            'flaxseed',
            'peanut butter',
        ],
    ];

    public function __construct(
        private readonly UsdaFoodDataCentralService $usdaService,
        private readonly FoodNameNormalizerService $foodNameNormalizer
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $categoria = strtolower((string) $this->option('categoria'));
        $queryLibre = (string) $this->option('query');
        $limite = max(1, (int) $this->option('limite'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info('===========================================================');
        $this->info('  IMPORTADOR AJUSTABLE DE ALIMENTOS USDA A ESPAÑOL         ');
        $this->info('===========================================================');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN ACTIVO: No se guardarán cambios en la base de datos.');
        }

        // Determinar qué consultas ejecutar
        $consultas = [];
        if ($queryLibre !== '') {
            $traducida = $this->foodNameNormalizer->traducirConsultaAEspanolAIngles($queryLibre);
            $consultas[] = $traducida;
            $this->line("Consulta personalizada: <comment>{$queryLibre}</comment> -> Buscando en USDA como: <info>{$traducida}</info>");
        } elseif ($categoria === 'todo') {
            foreach (self::CONSULTAS_POR_CATEGORIA as $cat => $items) {
                foreach ($items as $item) {
                    $consultas[] = $item;
                }
            }
            $this->line('Importando todas las categorías curadas (' . count($consultas) . ' términos de búsqueda)...');
        } elseif (isset(self::CONSULTAS_POR_CATEGORIA[$categoria])) {
            $consultas = self::CONSULTAS_POR_CATEGORIA[$categoria];
            $this->line("Importando categoría <comment>{$categoria}</comment> (" . count($consultas) . ' términos)...');
        } else {
            $this->error("Categoría '{$categoria}' no reconocida. Opciones válidas: proteinas, pescados, lacteos, cereales, frutas, verduras, grasas, todo");
            return self::FAILURE;
        }

        $totalImportados = 0;
        $totalExistentes = 0;
        $totalErrores = 0;
        $filasResumen = [];

        $progressBar = $this->output->createProgressBar(count($consultas));
        $progressBar->start();

        foreach ($consultas as $consulta) {
            try {
                $resultado = $this->usdaService->searchFoods(
                    query: $consulta,
                    pageNumber: 1,
                    pageSize: $limite,
                    dataType: ['Foundation', 'SR Legacy', 'Survey (FNDDS)']
                );
                $alimentosEncontrados = $resultado['foods'] ?? [];

                foreach ($alimentosEncontrados as $foodItem) {
                    $fdcId = (string) ($foodItem['fdc_id'] ?? '');
                    if ($fdcId === '') {
                        continue;
                    }

                    // Verificar si ya existe en la base local
                    $existente = Alimento::where('src_ali', Alimento::FUENTE_USDA)
                        ->where('ref_ext_ali', $fdcId)
                        ->first();

                    if ($existente) {
                        $totalExistentes++;
                        continue;
                    }

                    // Obtener detalles completos de composición nutricional
                    $detalle = $this->usdaService->getFoodById($fdcId);
                    $nombreOriginal = $detalle['description'] ?? ($foodItem['description'] ?? 'USDA Food');
                    $nombreAmigable = $this->foodNameNormalizer->nombreAmigable($nombreOriginal);

                    if (! $dryRun) {
                        DB::transaction(function () use ($fdcId, $detalle, $nombreOriginal, $nombreAmigable) {
                            $alimento = Alimento::create([
                                'nom_ali' => $nombreAmigable,
                                'nom_ext_ali' => $nombreOriginal,
                                'tip_ali' => $this->foodNameNormalizer->tipoAmigable($detalle['data_type'] ?? null),
                                'des_ali' => $detalle['food_category'] ?: 'Alimento USDA FoodData Central',
                                'mar_ali' => $detalle['brand_name'] ?: 'USDA Foundation / SR Legacy',
                                'src_ali' => Alimento::FUENTE_USDA,
                                'ref_ext_ali' => $fdcId,
                                'idi_ali' => 'es',
                                'pai_ali' => 'US',
                                'fec_sync_ali' => now(),
                                'est_ali' => Alimento::ESTADO_ACTIVO,
                            ]);

                            TablaComposicionNutricional::create([
                                'cod_alimentos_tcn' => $alimento->cod_alimentos,
                                'por_ref_tcn' => $detalle['portion_description'] ?: '100 g',
                                'cant_ref_tcn' => (float) ($detalle['portion_amount'] ?: 100),
                                'uni_ref_tcn' => 'gramos',
                                'cal_tcn' => (float) ($detalle['calories'] ?? 0),
                                'pro_tcn' => (float) ($detalle['protein'] ?? 0),
                                'gra_tcn' => (float) ($detalle['fat'] ?? 0),
                                'car_tcn' => (float) ($detalle['carbohydrate'] ?? 0),
                                'fib_tcn' => (float) ($detalle['fiber'] ?? 0),
                                'azu_tcn' => (float) ($detalle['sugar'] ?? 0),
                                'gra_sat_tcn' => (float) ($detalle['saturated_fat'] ?? 0),
                                'gra_mono_tcn' => (float) ($detalle['monounsaturated_fat'] ?? 0),
                                'gra_poli_tcn' => (float) ($detalle['polyunsaturated_fat'] ?? 0),
                                'sod_tcn' => (float) ($detalle['sodium'] ?? 0),
                                'pot_tcn' => (float) ($detalle['potassium'] ?? 0),
                                'col_tcn' => (float) ($detalle['cholesterol'] ?? 0),
                                'calcio_tcn' => (float) ($detalle['calcium'] ?? 0),
                                'hierro_tcn' => (float) ($detalle['iron'] ?? 0),
                                'vit_a_tcn' => (float) ($detalle['vitamin_a'] ?? 0),
                                'vit_c_tcn' => (float) ($detalle['vitamin_c'] ?? 0),
                                'vit_d_tcn' => (float) ($detalle['vitamin_d'] ?? 0),
                                'fec_tcn' => now()->toDateString(),
                                'est_tcn' => TablaComposicionNutricional::ESTADO_VIGENTE,
                            ]);
                        });
                    }

                    $totalImportados++;

                    if (count($filasResumen) < 20) {
                        $filasResumen[] = [
                            $fdcId,
                            mb_strimwidth($nombreAmigable, 0, 38, '...'),
                            round((float) ($detalle['calories'] ?? 0), 1) . ' kcal',
                            round((float) ($detalle['protein'] ?? 0), 1) . 'g',
                            round((float) ($detalle['fat'] ?? 0), 1) . 'g',
                            round((float) ($detalle['carbohydrate'] ?? 0), 1) . 'g',
                        ];
                    }
                }
            } catch (Throwable $e) {
                $totalErrores++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if (! empty($filasResumen)) {
            $this->info('Muestra de alimentos procesados:');
            $this->table(
                ['FDC ID', 'Nombre en Español', 'Calorías', 'Proteína', 'Grasa', 'Carbos'],
                $filasResumen
            );
        }

        $this->info("Resumen de la operación:");
        $this->line("- Alimentos importados/procesados: <info>{$totalImportados}</info>");
        $this->line("- Alimentos que ya existían previamente: <comment>{$totalExistentes}</comment>");
        if ($totalErrores > 0) {
            $this->line("- Consultas con advertencias/errores: <error>{$totalErrores}</error>");
        }

        return self::SUCCESS;
    }
}
