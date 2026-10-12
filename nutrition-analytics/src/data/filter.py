"""
Filtro de suficiencia y elegibilidad para snapshots analíticos.
"""

from typing import Tuple
import pandas as pd
from .loader import CLUSTERING_FEATURES_V1


def filter_eligible_snapshots(
    df: pd.DataFrame,
    target_window: int = 7,
    min_adherence: float = 0.7143,
    verbose: bool = True,
) -> Tuple[pd.DataFrame, dict]:
    """
    Filtra snapshots basados en criterios de suficiencia analítica para K-Means v1:
    - ventana_dias == target_window (7 días por defecto)
    - adherencia_registro_fnu >= min_adherence (>= 5/7 días = ~0.7143)
    - Las 13 variables de clustering deben estar presentes y no ser nulas.

    Retorna:
    - filtered_df: DataFrame que cumple con los criterios de suficiencia.
    - filter_stats: diccionario con resumen de filas descartadas.
    """
    total_raw = len(df)
    stats = {
        "total_inicial": total_raw,
        "descartados_ventana": 0,
        "descartados_adherencia": 0,
        "descartados_valores_nulos": 0,
        "total_elegibles": 0,
    }

    filtered = df.copy()

    # 1. Ventana de días
    if "ventana_dias" in filtered.columns:
        mask_window = filtered["ventana_dias"] == target_window
        stats["descartados_ventana"] = int((~mask_window).sum())
        filtered = filtered[mask_window]

    # 2. Criterio de adherencia mínima (>= 0.7143)
    if "adherencia_registro_fnu" in filtered.columns:
        mask_adh = filtered["adherencia_registro_fnu"] >= min_adherence
        stats["descartados_adherencia"] = int((~mask_adh).sum())
        filtered = filtered[mask_adh]

    # 3. Features de clustering completas (sin NaN ni Inf)
    missing_cols = [c for c in CLUSTERING_FEATURES_V1 if c not in filtered.columns]
    if missing_cols:
        raise ValueError(f"Columnas requeridas para clustering faltantes en el dataframe: {missing_cols}")

    mask_valid_features = filtered[CLUSTERING_FEATURES_V1].notnull().all(axis=1)
    stats["descartados_valores_nulos"] = int((~mask_valid_features).sum())
    filtered = filtered[mask_valid_features]

    stats["total_elegibles"] = len(filtered)

    if verbose:
        print(f"[Filtro Suficiencia] Total inicial: {stats['total_inicial']} | "
              f"Descartados ventana: {stats['descartados_ventana']} | "
              f"Descartados adherencia (< {min_adherence}): {stats['descartados_adherencia']} | "
              f"Descartados nulos: {stats['descartados_valores_nulos']} | "
              f"Total elegible: {stats['total_elegibles']}")

    return filtered, stats
