<?php

namespace App\Services;

use App\Models\ComposicionNutricionalRegistrada;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\RegistroAlimenticio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CalculoNutricionalRegistroService
{
    public function validarCalculable(RegistroAlimenticio $registro, bool $requiereCerrado = true): array
    {
        $registro->loadMissing(['detalles.alimento.composicionVigente']);

        $errores = [];

        if ($requiereCerrado && ! $this->registroEstaCerradoOHistorico($registro)) {
            $errores[] = [
                'codigo' => 'registro_no_cerrado',
                'mensaje' => 'El registro debe estar cerrado o corresponder a una fecha anterior para calcular la composicion nutricional.',
            ];
        }

        if ($registro->detalles->isEmpty()) {
            $errores[] = [
                'codigo' => 'registro_sin_detalles',
                'mensaje' => 'El registro no tiene alimentos en el detalle.',
            ];
        }

        $detallesIncompletos = $registro->detalles
            ->map(fn (DetalleRegistroAlimenticio $detalle) => $this->evaluarDetalleCalculable($detalle))
            ->filter(fn (array $evaluacion) => ! $evaluacion['valido'])
            ->values()
            ->all();

        if (! empty($detallesIncompletos)) {
            $errores[] = [
                'codigo' => 'detalle_no_calculable',
                'mensaje' => 'Hay alimentos sin composicion nutricional utilizable o con unidades incompatibles.',
                'detalles' => $detallesIncompletos,
            ];
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    public function calcularTotales(RegistroAlimenticio $registro, bool $requiereCerrado = true): array
    {
        $validacion = $this->validarCalculable($registro, $requiereCerrado);

        if (! $validacion['valido']) {
            throw new InvalidArgumentException('El registro no cumple condiciones para el calculo nutricional.');
        }

        $acumulado = [
            'cal_cnr' => 0.0,
            'pro_cnr' => 0.0,
            'gra_cnr' => 0.0,
            'car_cnr' => 0.0,
            'vit_cnr' => 0.0,
            'min_cnr' => 0.0,
        ];

        foreach ($registro->detalles as $detalle) {
            $comp = $detalle->alimento->composicionVigente;

            $factor = $this->calcularFactorConsumo($detalle, $comp);

            $acumulado['cal_cnr'] += ((float) $comp->cal_tcn) * $factor;
            $acumulado['pro_cnr'] += ((float) $comp->pro_tcn) * $factor;
            $acumulado['gra_cnr'] += ((float) $comp->gra_tcn) * $factor;
            $acumulado['car_cnr'] += ((float) $comp->car_tcn) * $factor;

            // Vitaminas agregadas para el consolidado base.
            $acumulado['vit_cnr'] += (
                ((float) $comp->vit_a_tcn)
                + ((float) $comp->vit_c_tcn)
                + ((float) $comp->vit_d_tcn)
            ) * $factor;

            // Minerales agregados para el consolidado base.
            $acumulado['min_cnr'] += (
                ((float) $comp->sod_tcn)
                + ((float) $comp->pot_tcn)
                + ((float) $comp->calcio_tcn)
                + ((float) $comp->hierro_tcn)
            ) * $factor;
        }

        foreach ($acumulado as $campo => $valor) {
            if ($valor < 0) {
                throw new RuntimeException("El valor consolidado {$campo} no puede ser negativo.");
            }

            $acumulado[$campo] = round($valor, 2);
        }

        return $acumulado;
    }

    public function calcularYGuardar(RegistroAlimenticio $registro, bool $requiereCerrado = true): ComposicionNutricionalRegistrada
    {
        $totales = $this->calcularTotales($registro, $requiereCerrado);

        return DB::transaction(function () use ($registro, $totales) {
            return ComposicionNutricionalRegistrada::updateOrCreate(
                [
                    'cod_registros_alimenticios_cnr' => $registro->cod_registros_alimenticios,
                ],
                $totales
            );
        });
    }

    protected function evaluarDetalleCalculable(DetalleRegistroAlimenticio $detalle): array
    {
        $composicion = $detalle->alimento?->composicionVigente;

        if (! $composicion) {
            return [
                'valido' => false,
                'cod_detalle_registro_alimenticio' => $detalle->cod_detalle_registro_alimenticio,
                'cod_alimentos_dra' => $detalle->cod_alimentos_dra,
                'alimento' => $detalle->alimento?->nom_ali,
                'razon' => 'sin_composicion_vigente',
            ];
        }

        $cantidadRef = (float) $composicion->cant_ref_tcn;
        $cantidadConsumo = (float) $detalle->can_dra;

        if ($cantidadRef <= 0 || $cantidadConsumo <= 0) {
            return [
                'valido' => false,
                'cod_detalle_registro_alimenticio' => $detalle->cod_detalle_registro_alimenticio,
                'cod_alimentos_dra' => $detalle->cod_alimentos_dra,
                'alimento' => $detalle->alimento?->nom_ali,
                'razon' => 'cantidad_invalida',
            ];
        }

        if (! $this->sonUnidadesCompatibles($detalle->uni_dra, $composicion->uni_ref_tcn, $detalle->alimento)) {
            return [
                'valido' => false,
                'cod_detalle_registro_alimenticio' => $detalle->cod_detalle_registro_alimenticio,
                'cod_alimentos_dra' => $detalle->cod_alimentos_dra,
                'alimento' => $detalle->alimento?->nom_ali,
                'razon' => 'unidad_incompatible',
                'unidad_detalle' => $detalle->uni_dra,
                'unidad_referencia' => $composicion->uni_ref_tcn,
            ];
        }

        return ['valido' => true];
    }

    public function sonUnidadesCompatibles(?string $unidadDetalle, ?string $unidadReferencia, ?\App\Models\Alimento $alimento = null): bool
    {
        $uDet = $this->normalizarUnidad($unidadDetalle ?: 'gramos');
        $uRef = $this->normalizarUnidad($unidadReferencia ?: 'gramos');

        if ($uDet === null || $uRef === null) {
            return false;
        }

        // Si el alimento es físico/sólido, no puede medirse en mililitros
        if ($alimento && ! $this->esAlimentoLiquido($alimento) && $uDet === 'mililitros') {
            return false;
        }

        // Si el alimento es líquido/bebida, no debe medirse en gramos
        if ($alimento && $this->esAlimentoLiquido($alimento) && $uDet === 'gramos') {
            return false;
        }

        if ($uDet === $uRef) {
            return true;
        }

        // Unidades caseras o de porción compatibles con alimentos
        if (in_array($uDet, ['unidad', 'porcion', 'taza', 'cucharada'], true)) {
            return true;
        }

        // Gramos y mililitros intercambiables únicamente si no hay alimento o si es líquido
        if (in_array($uDet, ['gramos', 'mililitros'], true) && in_array($uRef, ['gramos', 'mililitros'], true)) {
            return true;
        }

        return false;
    }

    public function esAlimentoLiquido(?\App\Models\Alimento $alimento): bool
    {
        if (! $alimento) {
            return false;
        }

        $comp = $alimento->composicionVigente ?? $alimento->composicionesNutricionales()->first();
        $uniRef = mb_strtolower(trim((string) ($comp?->uni_ref_tcn ?? '')));
        $porRef = mb_strtolower(trim((string) ($comp?->por_ref_tcn ?? '')));
        $tipAli = mb_strtolower(trim((string) ($alimento->tip_ali ?? '')));
        $nom = mb_strtolower(trim((string) ($alimento->nom_ali ?? '')));
        $nomExt = mb_strtolower(trim((string) ($alimento->nom_ext_ali ?? '')));

        // 1. Regla directa por unidad volumétrica en tabla nutricional
        if (in_array($uniRef, ['ml', 'mililitros', 'mililitro', 'cc', 'l', 'litro', 'fl oz'], true)) {
            return true;
        }

        // 2. Regla directa por tipo de alimento 'bebida'
        if ($tipAli === 'bebida' || $tipAli === 'beverage') {
            return true;
        }

        // 3. Regla directa si la porción de referencia especifica volumen explícito (ej. '250 ml', '1 vaso')
        if (preg_match('/\b\d+(?:\.\d+)?\s*(?:ml|mililitros|cc|l|litros)\b/iu', $porRef)) {
            return true;
        }

        // GUARDRAILS SÓLIDOS (Negative overrides): Alimentos que jamás son líquidos aunque contengan nombres de líquidos
        // Ejemplos: "atún al agua", "chuño negro cocido", "arroz con leche", "pollo al vino", "dulce de leche"
        if (preg_match('/\b(atún|atun|tuna|chuño|chuno|arroz con leche|dulce de leche|pastel|torta|queque|galleta|galletas|helado|ensalada|sándwich|sandwich|empanada|pan|bife|filete|carne|pollo|pavo|pescado|chuleta|silpancho|pique|majadito|huminta|cuñapé|cunape)\b/iu', $nom)) {
            return false;
        }

        // Guardrail por estado físico seco o deshidratado
        if (preg_match('/\b(en polvo|polvo|seco|seca|liofilizad\w*|deshidratad\w*|grano|granos|semilla|semillas|harina)\b/iu', $nom . ' ' . $nomExt)) {
            return false;
        }

        // Guardrail por tipo de alimento sólido en catálogo
        if (in_array($tipAli, ['carne', 'fruta', 'verdura', 'cereal', 'plato', 'tubérculo', 'tuberculo'], true)) {
            return false;
        }

        // 4. Detección estricta por palabras completas en título para alimentos clasificados genéricamente (ingrediente, producto, alimento)
        $patronesBebida = [
            '/\bagua\s+(mineral|pura|sin gas|con gas|saborizada|de mesa)\b/iu',
            '/\b(agua)\b/iu',
            '/\b(leche)\s+(entera|descremada|semidescremada|evaporada|de soya|de almendra|de avena|pasteurizada)\b/iu',
            '/\b(leche)\b/iu',
            '/\b(jugo|zumo)\s+(de\s+\w+|natural)?\b/iu',
            '/\b(refresco|gaseosa|soda|coca-cola|pepsi|fanta|sprite)\b/iu',
            '/\b(café|cafe)\s+(negro|con leche|filtrado|pasado|expreso|espresso|americano)\b/iu',
            '/\b(café|cafe)\b/iu',
            '/\b(té|te)\s+(verde|negro|rojo|de manzanilla|de hierbas|frío|helado)?\b/iu',
            '/\b(infusión|infusion|mate)\s+(de\s+\w+)?\b/iu',
            '/\b(chicha|mocochinchi|api morado|api amarillo)\b/iu',
            '/\b(cerveza|vino|licor|coctel|cóctel)\b/iu',
            '/\b(smoothie|batido|licuado)\b/iu',
            '/\b(yogur bebible|yogurt bebible)\b/iu',
        ];

        foreach ($patronesBebida as $patron) {
            if (preg_match($patron, $nom)) {
                // Evitar falsos positivos como "al agua" o "en agua"
                if (! preg_match('/\b(al agua|en agua)\b/iu', $nom)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function resolverUnidadesPermitidas(?\App\Models\Alimento $alimento): array
    {
        if ($this->esAlimentoLiquido($alimento)) {
            return ['mililitros', 'porcion', 'taza', 'cucharada'];
        }

        return ['gramos', 'porcion', 'unidad', 'taza', 'cucharada'];
    }

    public function resolverPesoPorcionReferencia(?\App\Models\Alimento $alimento, $comp = null): float
    {
        if (! $comp && $alimento) {
            $comp = $alimento->composicionVigente ?? $alimento->composicionesNutricionales()->first();
        }

        $cantRef = (float) ($comp?->cant_ref_tcn ?? 100.0);
        $porRef = (string) ($comp?->por_ref_tcn ?? '');

        // 1. Base de datos: Comprobar si existe equivalencia registrada en 'equivalencias_alimento_unidad'
        if ($alimento && \Illuminate\Support\Facades\Schema::hasTable('equivalencias_alimento_unidad')) {
            $equiv = \Illuminate\Support\Facades\DB::table('equivalencias_alimento_unidad')
                ->where('cod_alimentos_eau', $alimento->cod_alimentos)
                ->whereIn('uni_eau', ['porcion', 'unidad'])
                ->where('est_eau', 'activo')
                ->orderByDesc('cant_ref_eau')
                ->first();

            if ($equiv && (float) $equiv->gramos_eq_eau > 0) {
                return (float) $equiv->gramos_eq_eau;
            }
        }

        // 2. Base de datos: Extraer peso explícito textual desde por_ref_tcn (ej. "1 porción mediana (250 g)", "1 fillet (198 g)", "1 vaso (250 ml)")
        if (preg_match('/(?:aproximadamente|aprox\.?|\(|\b)(\d+(?:\.\d+)?)\s*(?:g|gr|gramos|ml|mililitros|cc)\b/iu', $porRef, $m)) {
            $peso = (float) $m[1];
            if ($peso > 0 && abs($peso - 100.0) > 0.01) {
                return $peso;
            }
        }

        // 3. Base de datos: Si cant_ref_tcn representa una porción real diferente al estándar químico de 100g (ej. 250g, 450g, 198g, 75g)
        if ($cantRef > 0 && abs($cantRef - 100.0) > 0.01) {
            return $cantRef;
        }

        // 4. Heurística categórica escalable (cuando el alimento solo tiene disponible el estándar químico de 100g)
        $esLiquido = $this->esAlimentoLiquido($alimento);
        if ($esLiquido) {
            return 240.0; // 1 vaso o taza estándar = 240 ml
        }

        if ($alimento) {
            $tipAli = mb_strtolower(trim((string) ($alimento->tip_ali ?? '')));
            $nom = mb_strtolower(trim((string) ($alimento->nom_ali . ' ' . $alimento->nom_ext_ali)));

            // Platos preparados completos (platos bolivianos, guisos, sopas, estofados)
            if ($tipAli === 'plato' || preg_match('/\b(plato|guiso|estofado|sopa|caldo|pique|silpancho|majadito|fricasé|fricase|picante|chicharrón|chicharron|saice|ají|aji|charquekan)\b/iu', $nom)) {
                return 350.0; // 1 plato hondo o mediano = 350g
            }

            // Masas, panes, repostería
            if (preg_match('/\b(salteña|saltena|empanada|tucumana)\b/iu', $nom)) {
                return 120.0;
            }
            if (preg_match('/\b(pan|marraqueta|sarnita|molde|baguette|tostada|galleta|galletas|pastel)\b/iu', $nom)) {
                return 60.0;
            }
            if (preg_match('/\b(cuñapé|cunape|huminta)\b/iu', $nom)) {
                return 50.0;
            }

            // Carnes, aves, pescados y mariscos
            if ($tipAli === 'carne' || preg_match('/\b(pollo|pechuga|carne|res|bife|filete|lomo|chuleta|cerdo|pavo|pescado|salmón|salmon|trucha|atún|atun|tilapia|sardina|hamburguesa|alita|costilla)\b/iu', $nom)) {
                return 150.0; // 1 filete o presa = 150g
            }

            // Guarniciones de carbohidratos cocidos / tubérculos / almidones (chuño, papa, arroz, quinua, fideo, etc.)
            if (preg_match('/\b(chuño|chuno|papa|papas|arroz|quinua|fideo|fideos|pasta|tallarín|tallarin|macarrón|macarron|yuca|camote|puré|pure|plátano cocido|platano cocido|lenteja|lentejas|poroto|porotos|frijol|frijoles|garbanzo|garbanzos)\b/iu', $nom)) {
                return 150.0; // 1 guarnición habitual = 150g
            }

            // Frutas enteras
            if ($tipAli === 'fruta' || preg_match('/\b(manzana|plátano|platano|banana|naranja|mandarina|pera|durazno|mango|papaya|piña|frutilla|uva|sandía|sandia|melón|melon|kiwi)\b/iu', $nom)) {
                return 150.0; // 1 fruta mediana = 150g
            }

            // Verduras y ensaladas
            if ($tipAli === 'verdura' || preg_match('/\b(lechuga|tomate|cebolla|zanahoria|pepino|espinaca|acelga|brócoli|brocoli|coliflor|vainita|zapallo|pimentón|pimenton|ensalada)\b/iu', $nom)) {
                return 100.0; // 1 porción de verdura / ensalada = 100g
            }

            // Huevos
            if (preg_match('/\b(huevo|huevos|egg)\b/iu', $nom)) {
                if (preg_match('/\b(clara|claras)\b/iu', $nom)) {
                    return 33.0; // 1 clara = 33g
                }
                return 50.0; // 1 huevo entero = 50g
            }

            // Quesos y lácteos sólidos
            if (preg_match('/\b(queso|quesillo)\b/iu', $nom)) {
                return 30.0; // 1 rodaja / trozo = 30g
            }

            // Frutos secos y semillas
            if (preg_match('/\b(nuez|nueces|almendra|almendras|maní|mani|cacahuate|chía|chia|lino|linaza|sésamo|sesamo)\b/iu', $nom)) {
                return 30.0;
            }

            // Aceites y grasas
            if (preg_match('/\b(aceite|mantequilla|margarina|mayonesa)\b/iu', $nom)) {
                return 15.0; // 1 cucharada = 15g
            }
        }

        return $cantRef > 0 ? $cantRef : 100.0;
    }

    public function calcularFactorConsumo(DetalleRegistroAlimenticio $detalle, $comp): float
    {
        $uDet = $this->normalizarUnidad($detalle->uni_dra ?: 'gramos');
        $uRef = $this->normalizarUnidad($comp->uni_ref_tcn ?: 'gramos');
        $cantidadRef = (float) $comp->cant_ref_tcn;
        $cantidadConsumo = (float) $detalle->can_dra;

        if ($cantidadRef <= 0 || $cantidadConsumo <= 0) {
            return 0.0;
        }

        // Si la unidad es idéntica
        if ($uDet === $uRef) {
            return $cantidadConsumo / $cantidadRef;
        }

        // Si se especificó como 'unidad' o 'porcion'
        if (in_array($uDet, ['unidad', 'porcion'], true)) {
            $pesoPorcion = $this->resolverPesoPorcionReferencia($detalle->alimento, $comp);
            return ($cantidadConsumo * $pesoPorcion) / $cantidadRef;
        }

        // Si se especificó en tazas (estándar 240g o 240ml)
        if ($uDet === 'taza') {
            return ($cantidadConsumo * 240.0) / $cantidadRef;
        }

        // Si se especificó en cucharadas (estándar 15g o 15ml)
        if ($uDet === 'cucharada') {
            return ($cantidadConsumo * 15.0) / $cantidadRef;
        }

        return $cantidadConsumo / $cantidadRef;
    }

    protected function normalizarUnidad(?string $unidad): ?string
    {
        $texto = mb_strtolower(trim((string) $unidad));

        if ($texto === '') {
            return null;
        }

        return match (true) {
            in_array($texto, ['g', 'gr', 'gram', 'grams', 'gramo', 'gramos'], true) => 'gramos',
            in_array($texto, ['ml', 'milliliter', 'milliliters', 'mililitro', 'mililitros'], true) => 'mililitros',
            in_array($texto, ['unit', 'units', 'unidad', 'unidades', 'piece', 'pieces'], true) => 'unidad',
            in_array($texto, ['portion', 'portions', 'porcion', 'porciones', 'serving', 'servings'], true) => 'porcion',
            in_array($texto, ['cup', 'cups', 'taza', 'tazas'], true) => 'taza',
            in_array($texto, ['tablespoon', 'tablespoons', 'tbsp', 'cucharada', 'cucharadas'], true) => 'cucharada',
            default => null,
        };
    }

    protected function registroEstaCerradoOHistorico(RegistroAlimenticio $registro): bool
    {
        if ($registro->estaCerrado()) {
            return true;
        }

        if (empty($registro->fec_ral)) {
            return false;
        }

        try {
            return Carbon::parse($registro->fec_ral)->startOfDay()->lt(now()->startOfDay());
        } catch (\Throwable) {
            return false;
        }
    }
}
