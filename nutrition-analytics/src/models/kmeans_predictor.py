"""
Motor de inferencia para clustering nutricional K-Means sin reentrenamiento.
Utiliza el StandardScaler ajustado y el artefacto serializado de KMeans.
"""

from pathlib import Path
from typing import Any, Dict, List, Optional, Union
import numpy as np
import pandas as pd

from ..data.loader import (
    calculate_density_per_1000kcal,
    compute_nutrient_densities,
    CLUSTERING_FEATURES_V1,
)
from ..features.preprocessor import FeaturePreprocessor
from .kmeans_trainer import KMeansExperimenter


class KMeansPredictor:
    """
    Componente de inferencia para clustering K-Means v1 listo para producción.
    Aplica estrictamente la transformación del scaler (nunca fit) y calcula
    distancias euclidianas exactas a todos los centroides de cluster.
    """

    EXPERIMENTAL_DISCLAIMER = (
        "Modelo experimental — entrenado con datos sintéticos controlados. "
        "Resultado orientativo, pendiente de validación con datos clínicos reales."
    )

    def __init__(
        self,
        scaler_path: Union[str, Path],
        model_path: Union[str, Path],
        feature_names: Optional[List[str]] = None,
    ):
        self.scaler_path = Path(scaler_path)
        self.model_path = Path(model_path)
        self.feature_names = feature_names or CLUSTERING_FEATURES_V1

        # 1. Cargar scaler ajustado
        self.preprocessor = FeaturePreprocessor.load(self.scaler_path)
        if not self.preprocessor.is_fitted:
            raise RuntimeError(f"El scaler cargado desde {self.scaler_path} no está ajustado.")

        # 2. Cargar artefacto del modelo entrenado
        import joblib
        artifact = joblib.load(self.model_path)
        self.model = artifact.get("kmeans_model")
        self.k = artifact.get("k", getattr(self.model, "n_clusters", 4))
        self.random_state = artifact.get("random_state", 42)
        self.version_mml = artifact.get("version_mml", f"v1.0.0-k{self.k}")
        self.version_features_mml = artifact.get("version_features_mml", "v1.0")
        self.origen_dataset_mml = "sintetico_controlado"

        # Precalcular centroides desnormalizados
        self.centroids_scaled = self.model.cluster_centers_
        self.centroids_raw = self.preprocessor.inverse_transform(self.centroids_scaled)

    def prepare_vector(self, raw_input: Union[Dict[str, Any], pd.DataFrame, pd.Series]) -> pd.DataFrame:
        """
        Convierte el diccionario/registro de entrada en un DataFrame de una fila con densidades derivadas.
        """
        if isinstance(raw_input, dict):
            df = pd.DataFrame([raw_input])
        elif isinstance(raw_input, pd.Series):
            df = pd.DataFrame([raw_input.to_dict()])
        elif isinstance(raw_input, pd.DataFrame):
            df = raw_input.copy()
        else:
            raise ValueError(f"Tipo de entrada no soportado: {type(raw_input)}")

        # Calcular densidades por 1000 kcal si no están presentes
        df = compute_nutrient_densities(df)

        # Comprobar que las 13 características requeridas estén presentes
        missing = [col for col in self.feature_names if col not in df.columns]
        if missing:
            raise ValueError(f"Faltan features requeridas para inferencia: {missing}")

        return df

    def predict(self, raw_input: Union[Dict[str, Any], pd.DataFrame, pd.Series]) -> Dict[str, Any]:
        """
        Ejecuta inferencia sin reentrenar:
        1. Calcula densidades derivadas.
        2. Aplica preprocessor.transform() (ESTRICTAMENTE TRANSFORM, NUNCA FIT).
        3. Calcula kmeans.predict().
        4. Calcula distancias euclidianas al centroide asignado y a todos los centroides.
        5. Retorna estructura JSON sin falsos scores de probabilidad.
        """
        df = self.prepare_vector(raw_input)
        
        # Guardar instantánea del estado del scaler para verificar que no se altere en inferencia
        mean_before = np.copy(self.preprocessor.scaler.mean_)
        
        # Transformación estricta
        X_scaled = self.preprocessor.transform(df)

        # Verificar que el scaler no fue reajustado
        assert np.array_equal(mean_before, self.preprocessor.scaler.mean_), "El scaler no debe ser modificado en inferencia"

        # Predecir etiqueta de cluster (0..K-1)
        cluster_idx = int(self.model.predict(X_scaled)[0])
        vector_scaled = X_scaled[0]

        # Calcular distancias euclidianas a todos los centroides en espacio escalado
        distances_all = {}
        for c_idx in range(self.k):
            centroid_c = self.centroids_scaled[c_idx]
            dist = float(np.linalg.norm(vector_scaled - centroid_c))
            distances_all[f"Cluster {c_idx}"] = round(dist, 4)

        assigned_dist = distances_all[f"Cluster {cluster_idx}"]

        # Extraer valores de variables clave de la fila de entrada
        row = df.iloc[0]
        indicadores = {
            "promedio_calorias_fnu": round(float(row["promedio_calorias_fnu"]), 2),
            "std_calorias_fnu": round(float(row.get("std_calorias_fnu", 0.0)), 2),
            "pct_proteina_fnu": round(float(row["pct_proteina_fnu"]), 2),
            "pct_carbohidratos_fnu": round(float(row["pct_carbohidratos_fnu"]), 2),
            "pct_grasa_fnu": round(float(row["pct_grasa_fnu"]), 2),
            "fibra_por_1000kcal": round(float(row["fibra_por_1000kcal"]), 2),
            "azucar_por_1000kcal": round(float(row["azucar_por_1000kcal"]), 2),
            "sodio_por_1000kcal": round(float(row["sodio_por_1000kcal"]), 2),
            "frecuencia_desayuno_fnu": round(float(row["frecuencia_desayuno_fnu"]), 4),
            "frecuencia_cena_fnu": round(float(row["frecuencia_cena_fnu"]), 4),
            "frecuencia_snack_fnu": round(float(row["frecuencia_snack_fnu"]), 4),
            "promedio_comidas_dia_fnu": round(float(row["promedio_comidas_dia_fnu"]), 2),
            "variabilidad_horaria_min_fnu": round(float(row["variabilidad_horaria_min_fnu"]), 2),
        }

        # Centroide del cluster asignado en escala original para comparación
        centroide_asignado_raw = {
            col: round(float(val), 2)
            for col, val in zip(self.feature_names, self.centroids_raw[cluster_idx])
        }

        return {
            "cluster_asignado": cluster_idx,
            "resultado_principal": f"Cluster {cluster_idx}",
            "distancia_centroide_asignado": assigned_dist,
            "distancias_centroides": distances_all,
            "version_modelo": self.version_mml,
            "version_features": self.version_features_mml,
            "origen_dataset": self.origen_dataset_mml,
            "score_confianza": None,        # K-Means no genera probabilidades bayesianas/supervisadas
            "probabilidades_clases": None,  # No inventar probabilidades en K-Means
            "etiqueta_experimental": self.EXPERIMENTAL_DISCLAIMER,
            "indicadores_principales": indicadores,
            "centroide_asignado_valores": centroide_asignado_raw,
        }
