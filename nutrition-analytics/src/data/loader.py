"""
Carga de datasets y transformación de densidades de nutrientes para variables nutricionales.
"""

from pathlib import Path
from typing import Union
import numpy as np
import pandas as pd

# Las 13 variables analíticas principales seleccionadas para el clustering K-Means v1
CLUSTERING_FEATURES_V1 = [
    "promedio_calorias_fnu",
    "std_calorias_fnu",
    "pct_proteina_fnu",
    "pct_carbohidratos_fnu",
    "pct_grasa_fnu",
    "fibra_por_1000kcal",
    "azucar_por_1000kcal",
    "sodio_por_1000kcal",
    "frecuencia_desayuno_fnu",
    "frecuencia_cena_fnu",
    "frecuencia_snack_fnu",
    "promedio_comidas_dia_fnu",
    "variabilidad_horaria_min_fnu",
]

# Columnas de nutrientes crudos esperadas en los snapshots sintéticos/exportados
RAW_NUTRIENT_COLUMNS = [
    "promedio_fibra_g_fnu",
    "promedio_azucar_g_fnu",
    "promedio_sodio_mg_fnu",
]

# Metadatos clínicos y de seguimiento no utilizados directamente en la formación de clusters
METADATA_COLUMNS_V1 = [
    "paciente_anonimo_id",
    "snapshot_id",
    "version_features",
    "fec_inicio",
    "fec_fin",
    "ventana_dias",
    "dias_registrados",
    "adherencia_registro_fnu",
    "ratio_validacion_fnu",
    "cobertura_composicion_pct_fnu",
    "edad_fnu",
    "sexo_fnu",
    "imc_fnu",
    "cambio_peso_fnu",
]


def calculate_density_per_1000kcal(
    nutrient_value: Union[float, np.ndarray, pd.Series],
    calories: Union[float, np.ndarray, pd.Series],
) -> Union[float, np.ndarray, pd.Series]:
    """
    Calcula (nutriente / calorías) * 1000 protegiendo contra división por cero.
    Retorna 0.0 si calorías <= 0, NaN o nulo.
    """
    if isinstance(nutrient_value, pd.Series) or isinstance(calories, pd.Series):
        c = pd.to_numeric(calories, errors="coerce").fillna(0.0)
        n = pd.to_numeric(nutrient_value, errors="coerce").fillna(0.0)
        mask = c > 0
        result = pd.Series(0.0, index=c.index)
        result[mask] = (n[mask] / c[mask]) * 1000.0
        return result

    if isinstance(nutrient_value, np.ndarray) or isinstance(calories, np.ndarray):
        c = np.asarray(calories, dtype=float)
        n = np.asarray(nutrient_value, dtype=float)
        with np.errstate(divide="ignore", invalid="ignore"):
            result = np.where(c > 0, (n / c) * 1000.0, 0.0)
            result = np.nan_to_num(result, nan=0.0, posinf=0.0, neginf=0.0)
        return result

    # Valor flotante escalar
    try:
        c_val = float(calories)
        n_val = float(nutrient_value)
        if c_val <= 0.0 or np.isnan(c_val) or np.isnan(n_val):
            return 0.0
        return (n_val / c_val) * 1000.0
    except (ValueError, TypeError):
        return 0.0


def compute_nutrient_densities(df: pd.DataFrame) -> pd.DataFrame:
    """
    Calcula las densidades de fibra, azúcar y sodio por 1000 kcal si los valores crudos están presentes
    y las columnas de densidad no han sido precalculadas.
    """
    data = df.copy()

    # Fibra por 1000 kcal
    if "fibra_por_1000kcal" not in data.columns:
        if "promedio_fibra_g_fnu" in data.columns and "promedio_calorias_fnu" in data.columns:
            data["fibra_por_1000kcal"] = calculate_density_per_1000kcal(
                data["promedio_fibra_g_fnu"], data["promedio_calorias_fnu"]
            )
        else:
            data["fibra_por_1000kcal"] = 0.0

    # Azúcar por 1000 kcal
    if "azucar_por_1000kcal" not in data.columns:
        if "promedio_azucar_g_fnu" in data.columns and "promedio_calorias_fnu" in data.columns:
            data["azucar_por_1000kcal"] = calculate_density_per_1000kcal(
                data["promedio_azucar_g_fnu"], data["promedio_calorias_fnu"]
            )
        else:
            data["azucar_por_1000kcal"] = 0.0

    # Sodio por 1000 kcal
    if "sodio_por_1000kcal" not in data.columns:
        if "promedio_sodio_mg_fnu" in data.columns and "promedio_calorias_fnu" in data.columns:
            data["sodio_por_1000kcal"] = calculate_density_per_1000kcal(
                data["promedio_sodio_mg_fnu"], data["promedio_calorias_fnu"]
            )
        else:
            data["sodio_por_1000kcal"] = 0.0

    return data


def load_feature_dataset(csv_path: Union[str, Path]) -> pd.DataFrame:
    """
    Carga el dataset de variables desde CSV y calcula las densidades de nutrientes.
    """
    path = Path(csv_path)
    if not path.exists():
        raise FileNotFoundError(f"El dataset no fue encontrado en: {path}")

    df = pd.read_csv(path)
    df = compute_nutrient_densities(df)
    return df
