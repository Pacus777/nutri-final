<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Separación Estricta de Umbrales Nutricionales (Versión v1.0)
    |--------------------------------------------------------------------------
    |
    | 1. UMBRALES APRENDIDOS POR MODELO (Decision Tree v1.0 / K-Means v1.0):
    |    Puntos de corte algorítmicos resultantes de la partición del árbol.
    |    No son prescripciones clínicas universales por sí mismos.
    |
    | 2. CRITERIOS REFERENCIALES EXTERNOS (Analítico Experimental):
    |    Valores de referencia para regularidad y hábitos sin rango de meta
    |    clínica vinculante hasta contar con validación médica formal.
    |
    */

    'version' => 'v1.0',

    // 1. Umbrales del Modelo ML (Decision Tree v1.0)
    'umbrales_modelo_v1' => [
        'azucar_corte_dt' => 44.10,       // g/1000 kcal — Nodo raíz del árbol de decisión (Foco: control glucémico)
        'variabilidad_corte_dt' => 52.10, // min/día — Nodo 1 de partición de estabilidad horaria
        'fibra_corte_dt' => 11.95,        // g/1000 kcal — Nodo de protección metabólica
        'sodio_corte_dt' => 1465.71,      // mg/1000 kcal — Nodo de balance electrolítico
    ],

    // 2. Criterios Referenciales Externos / Analítico-Experimentales
    'criterios_referenciales_externos' => [
        'tipo' => 'analitico_experimental',
        'frecuencia_desayuno_min' => 0.60,       // Mínimo de regularidad semanal (60% de los días)
        'cobertura_minima_calidad' => 0.70,      // Control de integridad del registro alimentario (70%)
        'calorias_referencia_heuristica' => 2300.0, // Estimación referencial para detección de extremos
        'carbohidratos_pct_referencial' => 55,   // Proporción estándar de carbohidratos en dieta equilibrada
        'grasas_pct_referencial' => 35,          // Proporción estándar de lípidos en dieta equilibrada
    ],

    // Mapeo retrocompatible para servicios y alertas
    'densidades' => [
        'azucar_max_por_1000kcal' => 44.10,
        'sodio_max_por_1000kcal' => 1465.71,
        'fibra_min_por_1000kcal' => 11.95,
    ],
    'habitos_y_horarios' => [
        'variabilidad_horaria_max_min' => 52.10,
        'frecuencia_desayuno_min' => 0.60,
        'cobertura_minima_calidad' => 0.70,
    ],
    'heuristica' => [
        'calorias_referencia_max' => 2300.0,
        'carbohidratos_pct_max' => 55,
        'grasas_pct_max' => 35,
        'proteina_min_g' => 45.0,
    ],
];
