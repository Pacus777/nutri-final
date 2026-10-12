<?php

namespace App\Services;

use Illuminate\Support\Str;

class FoodNameNormalizerService
{
    private const DICCIONARIO = [
        // Platos y preparaciones compuestas frecuentes
        'chicken breast' => 'pechuga de pollo',
        'chicken thigh' => 'muslo de pollo',
        'chicken drumstick' => 'pierna de pollo',
        'chicken wing' => 'ala de pollo',
        'ground beef' => 'carne molida de res',
        'beef steak' => 'bife de carne',
        'pork chop' => 'chuleta de cerdo',
        'boiled egg' => 'huevo duro',
        'fried egg' => 'huevo frito',
        'scrambled eggs' => 'huevos revueltos',
        'mashed potatoes' => 'puré de papas',
        'french fries' => 'papas fritas',
        'potato chips' => 'papas fritas en bolsa',
        'peanut butter' => 'mantequilla de maní',
        'olive oil' => 'aceite de oliva',
        'sunflower oil' => 'aceite de girasol',
        'vegetable oil' => 'aceite vegetal',
        'soy milk' => 'leche de soya',
        'almond milk' => 'leche de almendras',
        'oat milk' => 'leche de avena',
        'orange juice' => 'jugo de naranja',
        'apple juice' => 'jugo de manzana',
        'green tea' => 'té verde',
        'black tea' => 'té negro',
        'bottled water' => 'agua embotellada',
        'sparkling water' => 'agua con gas',
        'mineral water' => 'agua mineral',
        'whole wheat bread' => 'pan integral',
        'white bread' => 'pan blanco',
        'pita bread' => 'pan pita',

        // Cereales, granos y carbohidratos
        'rice' => 'arroz',
        'white rice' => 'arroz blanco',
        'brown rice' => 'arroz integral',
        'parboiled rice' => 'arroz precocido',
        'quinoa' => 'quinua',
        'bread' => 'pan',
        'wheat' => 'trigo',
        'oat' => 'avena',
        'oats' => 'avena',
        'oatmeal' => 'avena cocida',
        'corn' => 'maíz',
        'sweet corn' => 'choclo dulce',
        'cornmeal' => 'harina de maíz',
        'pasta' => 'pasta',
        'spaghetti' => 'fideo espagueti',
        'macaroni' => 'macarrón',
        'noodle' => 'fideo',
        'noodles' => 'fideos',
        'potato' => 'papa',
        'potatoes' => 'papas',
        'sweet potato' => 'camote',
        'cassava' => 'yuca',
        'flour' => 'harina',
        'wheat flour' => 'harina de trigo',
        'cereal' => 'cereal',
        'granola' => 'granola',

        // Proteínas animales (Carnes, Aves, Pescados)
        'chicken' => 'pollo',
        'beef' => 'carne de res',
        'pork' => 'cerdo',
        'fish' => 'pescado',
        'tuna' => 'atún',
        'salmon' => 'salmón',
        'trout' => 'trucha',
        'cod' => 'bacalao',
        'tilapia' => 'tilapia',
        'sardine' => 'sardina',
        'sardines' => 'sardinas',
        'egg' => 'huevo',
        'eggs' => 'huevos',
        'egg white' => 'clara de huevo',
        'egg yolk' => 'yema de huevo',
        'turkey' => 'pavo',
        'meat' => 'carne',
        'lamb' => 'cordero',
        'ham' => 'jamón',
        'bacon' => 'tocino',
        'sausage' => 'salchicha',
        'shrimp' => 'camarón',
        'prawn' => 'langostino',

        // Lácteos y derivados
        'milk' => 'leche',
        'cheese' => 'queso',
        'cottage cheese' => 'queso cottage',
        'cream cheese' => 'queso crema',
        'parmesan cheese' => 'queso parmesano',
        'cheddar cheese' => 'queso cheddar',
        'mozzarella cheese' => 'queso mozzarella',
        'yogurt' => 'yogur',
        'greek yogurt' => 'yogur griego',
        'butter' => 'mantequilla',
        'margarine' => 'margarina',
        'cream' => 'crema',
        'sour cream' => 'crema agria',
        'heavy cream' => 'crema de leche',

        // Frutas
        'apple' => 'manzana',
        'banana' => 'plátano',
        'orange' => 'naranja',
        'tangerine' => 'mandarina',
        'mandarin' => 'mandarina',
        'grape' => 'uva',
        'grapes' => 'uvas',
        'strawberry' => 'frutilla',
        'strawberries' => 'frutillas',
        'blueberry' => 'arándano',
        'blueberries' => 'arándanos',
        'raspberry' => 'frambuesa',
        'blackberry' => 'mora',
        'mango' => 'mango',
        'papaya' => 'papaya',
        'pear' => 'pera',
        'pineapple' => 'piña',
        'watermelon' => 'sandía',
        'melon' => 'melón',
        'cantaloupe' => 'melón cantalupo',
        'peach' => 'durazno',
        'plum' => 'ciruela',
        'avocado' => 'palta',
        'lemon' => 'limón',
        'lime' => 'lima',
        'kiwi' => 'kiwi',
        'coconut' => 'coco',
        'guava' => 'guayaba',
        'passion fruit' => 'maracuyá',

        // Verduras y hortalizas
        'tomato' => 'tomate',
        'tomatoes' => 'tomates',
        'lettuce' => 'lechuga',
        'onion' => 'cebolla',
        'red onion' => 'cebolla morada',
        'white onion' => 'cebolla blanca',
        'garlic' => 'ajo',
        'carrot' => 'zanahoria',
        'carrots' => 'zanahorias',
        'broccoli' => 'brócoli',
        'cauliflower' => 'coliflor',
        'spinach' => 'espinaca',
        'cabbage' => 'repollo',
        'cucumber' => 'pepino',
        'zucchini' => 'zapallito',
        'pumpkin' => 'zapallo',
        'squash' => 'calabaza',
        'pepper' => 'pimiento',
        'bell pepper' => 'pimiento morrón',
        'green pepper' => 'pimiento verde',
        'red pepper' => 'pimiento rojo',
        'mushroom' => 'champiñón',
        'mushrooms' => 'champiñones',
        'celery' => 'apio',
        'radish' => 'rábano',
        'beet' => 'betarraga',
        'beetroot' => 'remolacha',
        'green beans' => 'vainitas',
        'asparagus' => 'espárrago',

        // Legumbres y frutos secos
        'bean' => 'frijol',
        'beans' => 'frijoles',
        'black beans' => 'frijoles negros',
        'red beans' => 'frijoles rojos',
        'lentil' => 'lenteja',
        'lentils' => 'lentejas',
        'pea' => 'arveja',
        'peas' => 'arvejas',
        'chickpea' => 'garbanzo',
        'chickpeas' => 'garbanzos',
        'soybean' => 'soya',
        'soybeans' => 'granos de soya',
        'peanut' => 'maní',
        'peanuts' => 'maní',
        'walnut' => 'nuez',
        'walnuts' => 'nueces',
        'almond' => 'almendra',
        'almonds' => 'almendras',
        'cashew' => 'castaña de cajú',
        'chia seeds' => 'semillas de chía',
        'flaxseed' => 'semillas de linaza',
        'sunflower seeds' => 'semillas de girasol',

        // Bebidas y líquidos
        'water' => 'agua',
        'juice' => 'jugo',
        'coffee' => 'café',
        'tea' => 'té',
        'beverages' => 'bebida',
        'beverage' => 'bebida',
        'soda' => 'gaseosa',
        'soft drink' => 'refresco',
        'beer' => 'cerveza',
        'wine' => 'vino',

        // Descriptores de preparación y estado
        'breast' => 'pechuga',
        'thigh' => 'muslo',
        'drumstick' => 'pierna',
        'wing' => 'ala',
        'wings' => 'alas',
        'tenderloin' => 'lomo fino',
        'sirloin' => 'lomo',
        'fillet' => 'filete',
        'fillets' => 'filetes',
        'ground' => 'molido',
        'nuts' => 'frutos secos',
        'nut' => 'fruto seco',
        'greek' => 'griego',
        'atlantic' => 'del atlántico',
        'pacific' => 'del pacífico',
        'light' => 'claro',
        'avocados' => 'palta/aguacate',
        'whole grain' => 'grano entero',
        'rolled' => 'en hojuelas',
        'old fashioned' => 'tradicional',
        'vitamin' => 'vitamina',
        'with added' => 'con adición de',
        'without added' => 'sin adición de',
        'crustaceans' => 'mariscos',
        'farm raised' => 'de criadero',
        'mixed species' => 'variedades mixtas',
        'raw' => 'crudo',
        'cooked' => 'cocido',
        'boiled' => 'hervido',
        'fried' => 'frito',
        'roasted' => 'asado',
        'grilled' => 'a la parrilla',
        'baked' => 'horneado',
        'steamed' => 'al vapor',
        'canned' => 'enlatado',
        'frozen' => 'congelado',
        'dried' => 'seco',
        'fresh' => 'fresco',
        'whole' => 'entero',
        'chopped' => 'picado',
        'sliced' => 'en rodajas',
        'peeled' => 'pelado',
        'unpeeled' => 'con cáscara',
        'boneless' => 'deshuesado',
        'skinless' => 'sin piel',
        'with skin' => 'con piel',
        'meat only' => 'solo carne',

        // Descriptores técnicos USDA y cortes
        'enriched' => 'enriquecido',
        'regular' => 'regular',
        'long-grain' => 'grano largo',
        'medium-grain' => 'grano medio',
        'short-grain' => 'grano corto',
        'without salt' => 'sin sal',
        'with salt' => 'con sal',
        'salt added' => 'con sal añadida',
        'no salt added' => 'sin sal añadida',
        'low sodium' => 'bajo en sodio',
        'unsweetened' => 'sin azúcar',
        'sweetened' => 'endulzado',
        'whole milk' => 'leche entera',
        'skim milk' => 'leche descremada',
        'low fat milk' => 'leche semidescremada',
        'low fat' => 'bajo en grasa',
        'reduced fat' => 'reducido en grasa',
        'nonfat' => 'descremado',
        'fat-free' => 'libre de grasa',
        'non-carbonated' => 'sin gas',
        'carbonated' => 'con gas',
        'bottled' => 'embotellado',
        'plain' => 'natural',
        'extra virgin olive oil' => 'aceite de oliva extra virgen',
        'atlantic salmon' => 'salmón del atlántico',
        'sockeye salmon' => 'salmón rojo',
        'pink salmon' => 'salmón rosado',
        'canned tuna' => 'atún enlatado',
        'light tuna' => 'atún claro',
        'white tuna' => 'atún blanco',
        'in water' => 'al agua',
        'in oil' => 'en aceite',
        'canned in water' => 'en lata al agua',
        'canned in oil' => 'en lata en aceite',
        'drained solids' => 'escurrido',
        'drained' => 'escurrido',
        'solids and liquids' => 'sólidos y líquido',
        'meat and skin' => 'carne con piel',
        'separable lean and fat' => 'carne magra y grasa',
        'separable lean only' => 'solo carne magra',
        'lean' => 'magro',
        'extra lean' => 'extra magro',
        'fillet' => 'filete',
        'fillets' => 'filetes',
        'rolled oats' => 'avena en hojuelas',
        'quick oats' => 'avena instantánea',
        'steel cut oats' => 'avena en grano cortado',
        'boneless, skinless' => 'deshuesado y sin piel',
        'skinless, boneless' => 'deshuesado y sin piel',
        'egg whole' => 'huevo entero',
        'whole egg' => 'huevo entero',
        'whole without shell' => 'entero sin cáscara',
        'large' => 'grande',
        'medium' => 'mediano',
        'small' => 'pequeño',
        'wild' => 'salvaje',
        'farmed' => 'de criadero',
        'all types' => 'todos los tipos',

        // Condimentos e ingredientes varios
        'sugar' => 'azúcar',
        'brown sugar' => 'azúcar morena',
        'honey' => 'miel',
        'salt' => 'sal',
        'oil' => 'aceite',
        'vinegar' => 'vinagre',
        'sauce' => 'salsa',
        'soy sauce' => 'salsa de soya',
        'mustard' => 'mostaza',
        'mayonnaise' => 'mayonesa',
        'ketchup' => 'kétchup',
        'chocolate' => 'chocolate',
    ];

    private const RUIDO_USDA = [
        'the coca-cola company',
        'coca-cola company',
        'dasani',
        'nestle',
        'pepsico',
        'kellogg company',
        'general mills',
        'kraft foods',
        'nfs',
        'not further specified',
        'not specified',
        'usda',
        'commodity',
        'food distribution program',
        'home prepared',
        'commercial',
        'broilers or fryers',
        'grade a',
        'grade aa',
        'grade b',
        'u.s.',
        'all commercial varieties',
        'commercial varieties',
        'all varieties',
    ];

    private const CONSULTA_ESPANOL_INGLES = [
        'huevo' => 'egg',
        'huevos' => 'eggs',
        'clara' => 'egg white',
        'claras' => 'egg whites',
        'yema' => 'egg yolk',
        'pollo' => 'chicken',
        'pechuga' => 'chicken breast',
        'pechuga de pollo' => 'chicken breast',
        'muslo' => 'chicken thigh',
        'ala' => 'chicken wing',
        'carne' => 'beef',
        'res' => 'beef',
        'ternera' => 'veal',
        'cerdo' => 'pork',
        'puerco' => 'pork',
        'chuleta' => 'pork chop',
        'pavo' => 'turkey',
        'tocino' => 'bacon',
        'jamon' => 'ham',
        'jamón' => 'ham',
        'pescado' => 'fish',
        'atun' => 'tuna',
        'atún' => 'tuna',
        'salmon' => 'salmon',
        'salmón' => 'salmon',
        'trucha' => 'trout',
        'camaron' => 'shrimp',
        'camarón' => 'shrimp',
        'camarones' => 'shrimp',
        'sardina' => 'sardines',
        'sardinas' => 'sardines',
        'bacalao' => 'cod',
        'tilapia' => 'tilapia',
        'leche' => 'milk',
        'leche entera' => 'whole milk',
        'leche descremada' => 'skim milk',
        'yogur' => 'yogurt',
        'yogurt' => 'yogurt',
        'yogur griego' => 'greek yogurt',
        'queso' => 'cheese',
        'mozzarella' => 'mozzarella cheese',
        'cheddar' => 'cheddar cheese',
        'parmesano' => 'parmesan cheese',
        'cottage' => 'cottage cheese',
        'mantequilla' => 'butter',
        'arroz' => 'rice',
        'arroz blanco' => 'white rice',
        'arroz integral' => 'brown rice',
        'avena' => 'oats',
        'pan' => 'bread',
        'pan integral' => 'whole wheat bread',
        'pan blanco' => 'white bread',
        'fideo' => 'pasta',
        'fideos' => 'pasta',
        'pasta' => 'pasta',
        'espagueti' => 'spaghetti',
        'papa' => 'potato',
        'papas' => 'potatoes',
        'camote' => 'sweet potato',
        'yuca' => 'cassava',
        'lenteja' => 'lentils',
        'lentejas' => 'lentils',
        'garbanzo' => 'chickpeas',
        'garbanzos' => 'chickpeas',
        'frijol' => 'beans',
        'frijoles' => 'black beans',
        'poroto' => 'beans',
        'arveja' => 'peas',
        'arvejas' => 'green peas',
        'quinua' => 'quinoa',
        'manzana' => 'apple',
        'platano' => 'banana',
        'plátano' => 'banana',
        'banana' => 'banana',
        'fresa' => 'strawberries',
        'fresas' => 'strawberries',
        'frutilla' => 'strawberries',
        'frutillas' => 'strawberries',
        'naranja' => 'orange',
        'palta' => 'avocado',
        'aguacate' => 'avocado',
        'limon' => 'lemon',
        'limón' => 'lemon',
        'uva' => 'grapes',
        'uvas' => 'grapes',
        'pina' => 'pineapple',
        'piña' => 'pineapple',
        'sandia' => 'watermelon',
        'sandía' => 'watermelon',
        'durazno' => 'peach',
        'arandano' => 'blueberries',
        'arándano' => 'blueberries',
        'arandanos' => 'blueberries',
        'arándanos' => 'blueberries',
        'brocoli' => 'broccoli',
        'brócoli' => 'broccoli',
        'espinaca' => 'spinach',
        'espinacas' => 'spinach',
        'zanahoria' => 'carrots',
        'zanahorias' => 'carrots',
        'tomate' => 'tomatoes',
        'tomates' => 'tomatoes',
        'lechuga' => 'lettuce',
        'cebolla' => 'onion',
        'cebollas' => 'onions',
        'pepino' => 'cucumber',
        'ajo' => 'garlic',
        'almendra' => 'almonds',
        'almendras' => 'almonds',
        'nuez' => 'walnuts',
        'nueces' => 'walnuts',
        'mani' => 'peanuts',
        'maní' => 'peanuts',
        'chia' => 'chia seeds',
        'chía' => 'chia seeds',
        'linaza' => 'flaxseed',
        'aceite de oliva' => 'olive oil',
        'aceite' => 'oil',
        'cafe' => 'coffee',
        'café' => 'coffee',
        'te' => 'tea',
        'té' => 'tea',
        'agua' => 'water',
        'miel' => 'honey',
    ];

    /**
     * Traduce consultas cotidianas escritas en español al inglés para consultar la API de USDA.
     */
    public function traducirConsultaAEspanolAIngles(string $consulta): string
    {
        $limpio = mb_strtolower(trim($consulta), 'UTF-8');
        if ($limpio === '') {
            return '';
        }

        if (isset(self::CONSULTA_ESPANOL_INGLES[$limpio])) {
            return self::CONSULTA_ESPANOL_INGLES[$limpio];
        }

        // Búsqueda por subcadenas ordenadas por longitud descendente
        $diccionarioOrdenado = collect(self::CONSULTA_ESPANOL_INGLES)
            ->sortByDesc(fn ($val, $key) => mb_strlen((string) $key));

        foreach ($diccionarioOrdenado as $es => $en) {
            if (str_contains($limpio, (string) $es)) {
                return $en;
            }
        }

        return $consulta;
    }

    public function nombreAmigable(?string $nombreOriginal): string
    {
        $nombreOriginal = trim((string) $nombreOriginal);

        if ($nombreOriginal === '') {
            return 'Alimento USDA sin nombre';
        }

        // Casos conocidos específicos para transformar en nombres limpios
        $nombreLower = mb_strtolower($nombreOriginal);
        if (str_contains($nombreLower, 'dasani') && str_contains($nombreLower, 'water')) {
            return str_contains($nombreLower, 'non-carbonated') || str_contains($nombreLower, 'still')
                ? 'Agua mineral Dasani sin gas'
                : 'Agua Dasani';
        }

        if (str_contains($nombreLower, 'coca-cola') || str_contains($nombreLower, 'coca cola')) {
            if (str_contains($nombreLower, 'zero')) return 'Gaseosa Coca-Cola Zero';
            if (str_contains($nombreLower, 'diet') || str_contains($nombreLower, 'light')) return 'Gaseosa Coca-Cola Light';
            return 'Gaseosa Coca-Cola original';
        }

        // Caso especial de huevos en USDA
        if (str_contains($nombreLower, 'eggs') || str_contains($nombreLower, 'egg')) {
            if (str_contains($nombreLower, 'white')) return 'Clara de huevo fresco';
            if (str_contains($nombreLower, 'yolk')) return 'Yema de huevo fresco';
            if (str_contains($nombreLower, 'whole')) return 'Huevo de gallina entero fresco';
        }

        $normalizado = $this->limpiarTexto($nombreOriginal);

        $partes = collect(explode(',', $normalizado))
            ->map(fn ($parte) => trim($parte))
            ->filter(fn ($parte) => ! $this->esRuido($parte))
            ->map(fn ($parte) => $this->traducirFrase($parte))
            ->filter()
            ->unique()
            ->values();

        $resultado = $partes->implode(' ');

        $resultado = preg_replace('/\s+/', ' ', $resultado);
        $resultado = trim((string) $resultado);

        if ($resultado === '') {
            return Str::headline($nombreOriginal);
        }

        return Str::ucfirst($resultado);
    }

    public function tipoAmigable(?string $dataType, ?string $foodCategory = null, ?string $description = null): string
    {
        $category = mb_strtolower(trim((string) $foodCategory));
        $desc = mb_strtolower(trim((string) $description));

        // Detección automática de bebidas para garantizar escalabilidad con alimentos futuros
        if (
            str_contains($category, 'beverage') ||
            str_contains($category, 'water') ||
            str_contains($category, 'juice') ||
            str_contains($category, 'coffee') ||
            str_contains($category, 'tea') ||
            str_contains($category, 'soda') ||
            preg_match('/\b(water|juice|coffee|tea|soda|beverage|drink|beer|wine|milk|leche|agua|jugo|refresco)\b/i', $desc)
        ) {
            // Asegurar que no sea algo como "atún en agua" o "leche en polvo"
            if (! preg_match('/\b(tuna|in water|en agua|powder|en polvo)\b/i', $desc)) {
                return 'bebida';
            }
        }

        $dataType = mb_strtolower(trim((string) $dataType));

        return match (true) {
            str_contains($dataType, 'branded') => 'producto',
            str_contains($dataType, 'foundation') => 'ingrediente',
            str_contains($dataType, 'survey') => 'alimento',
            str_contains($dataType, 'sr legacy') => 'ingrediente',
            default => 'alimento',
        };
    }

    private function esRuido(string $texto): bool
    {
        $texto = trim(mb_strtolower($texto));
        foreach (self::RUIDO_USDA as $ruido) {
            if ($texto === $ruido) {
                return true;
            }
        }
        return false;
    }

    private function limpiarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto);

        // Quitar marcas de ruido incrustadas
        foreach (self::RUIDO_USDA as $ruido) {
            $texto = preg_replace('/\b' . preg_quote($ruido, '/') . '\b/u', '', $texto);
        }

        $texto = str_replace(['(', ')', '[', ']', '"', "'"], ' ', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim((string) $texto);
    }

    private function traducirFrase(string $frase): string
    {
        $frase = trim($frase);

        if ($frase === '') {
            return '';
        }

        // Primero intenta traducir frase completa.
        if (isset(self::DICCIONARIO[$frase])) {
            return self::DICCIONARIO[$frase];
        }

        // Luego reemplaza expresiones compuestas antes que palabras sueltas.
        $resultado = $frase;

        $diccionarioOrdenado = collect(self::DICCIONARIO)
            ->sortByDesc(fn ($valor, $clave) => mb_strlen((string) $clave));

        foreach ($diccionarioOrdenado as $ingles => $espanol) {
            $resultado = preg_replace(
                '/\b' . preg_quote((string) $ingles, '/') . '\b/u',
                (string) $espanol,
                (string) $resultado
            );
        }

        return trim((string) $resultado);
    }
}