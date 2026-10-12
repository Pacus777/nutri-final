"""
Entrenador y motor de evaluación de clustering K-Means para patrones nutricionales.
"""

from dataclasses import dataclass, field
import hashlib
from pathlib import Path
from typing import Any, Dict, List, Optional, Union
import joblib
import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score

from ..features.preprocessor import FeaturePreprocessor


@dataclass
class KMeansResult:
    """Almacena resultados y métricas para una configuración específica de K."""
    k: int
    inertia: float
    silhouette: float
    cluster_sizes: Dict[int, int]
    cluster_percentages: Dict[int, float]
    centroids_scaled: np.ndarray
    centroids_denormalized: pd.DataFrame
    model: KMeans
    labels: np.ndarray


class KMeansExperimenter:
    """
    Ejecuta el entrenamiento de K-Means en un rango de clusters (K=2..6)
    y calcula métricas de diagnóstico (Inercia, Silhouette, Centroides, Tamaños de cluster).
    """

    def __init__(
        self,
        k_range: range = range(2, 7),
        random_state: int = 42,
        n_init: int = 10,
        max_iter: int = 300,
    ):
        self.k_range = list(k_range)
        self.random_state = random_state
        self.n_init = n_init
        self.max_iter = max_iter
        self.results: Dict[int, KMeansResult] = {}

    def fit_experiments(
        self,
        X_scaled: np.ndarray,
        preprocessor: FeaturePreprocessor,
        feature_names: List[str],
    ) -> Dict[int, KMeansResult]:
        """
        Ajusta modelos KMeans para cada K en k_range sobre la matriz de variables escaladas.
        """
        self.results = {}

        for k in self.k_range:
            kmeans = KMeans(
                n_clusters=k,
                init="k-means++",
                n_init=self.n_init,
                max_iter=self.max_iter,
                random_state=self.random_state,
            )
            labels = kmeans.fit_predict(X_scaled)
            inertia = float(kmeans.inertia_)
            sil_score = float(silhouette_score(X_scaled, labels))

            # Conteo y distribución de clusters
            unique, counts = np.unique(labels, return_counts=True)
            sizes = {int(cluster_idx): int(count) for cluster_idx, count in zip(unique, counts)}
            total_samples = len(labels)
            percentages = {c: round((cnt / total_samples) * 100, 2) for c, cnt in sizes.items()}

            # Centroides desnormalizados
            centroids_scaled = kmeans.cluster_centers_
            centroids_raw = preprocessor.inverse_transform(centroids_scaled)
            centroids_df = pd.DataFrame(
                centroids_raw,
                columns=feature_names,
                index=[f"Cluster_{i}" for i in range(k)],
            )

            result = KMeansResult(
                k=k,
                inertia=inertia,
                silhouette=sil_score,
                cluster_sizes=sizes,
                cluster_percentages=percentages,
                centroids_scaled=centroids_scaled,
                centroids_denormalized=centroids_df,
                model=kmeans,
                labels=labels,
            )
            self.results[k] = result

        return self.results

    def get_comparison_table(self) -> pd.DataFrame:
        """Retorna un DataFrame comparativo a través de todos los valores de K evaluados."""
        rows = []
        for k, res in self.results.items():
            sizes_str = ", ".join([f"C{c}:{res.cluster_sizes[c]} ({res.cluster_percentages[c]}%)" for c in range(k)])
            rows.append({
                "K": k,
                "Inertia (WCSS)": round(res.inertia, 2),
                "Silhouette Score": round(res.silhouette, 4),
                "Tamaño de Clusters": sizes_str,
            })
        return pd.DataFrame(rows)

    def save_model_artifact(
        self,
        k: int,
        preprocessor: FeaturePreprocessor,
        filepath: Union[str, Path],
    ) -> str:
        """Serializa el modelo KMeans y el preprocesador en un único artefacto joblib."""
        if k not in self.results:
            raise ValueError(f"No hay modelo entrenado para K={k}")

        path = Path(filepath)
        path.parent.mkdir(parents=True, exist_ok=True)

        artifact = {
            "model_type": "kmeans",
            "k": k,
            "random_state": self.random_state,
            "kmeans_model": self.results[k].model,
            "preprocessor": preprocessor,
            "feature_names": preprocessor.feature_names,
            "cluster_sizes": self.results[k].cluster_sizes,
            "cluster_percentages": self.results[k].cluster_percentages,
            "silhouette": self.results[k].silhouette,
            "inertia": self.results[k].inertia,
        }
        joblib.dump(artifact, path)
        return str(path)

    def generate_candidate_metadata(
        self,
        k: int,
        dataset_path: Union[str, Path],
        artifact_path: str,
        n_samples: int,
        n_patients: int,
        feature_names: List[str],
        version_mml: str = "v1.0.0",
        version_features_mml: str = "v1.0",
    ) -> Dict[str, Any]:
        """
        Construye la estructura de metadatos lista para registro en modelos_ml y clusters_modelo.
        """
        if k not in self.results:
            raise ValueError(f"K={k} no ha sido entrenado.")

        res = self.results[k]

        # Calcular SHA256 del dataset de entrenamiento
        dataset_p = Path(dataset_path)
        sha256_hash = hashlib.sha256()
        with open(dataset_p, "rb") as f:
            for byte_block in iter(lambda: f.read(4096), b""):
                sha256_hash.update(byte_block)
        dataset_hash = sha256_hash.hexdigest()

        # Construir perfiles estadísticos de clusters sin etiquetas clínicas definitivas
        clusters_data = []
        for cluster_idx in range(k):
            centroid_dict = res.centroids_denormalized.iloc[cluster_idx].to_dict()
            centroid_clean = {col: round(float(val), 4) for col, val in centroid_dict.items()}

            size = res.cluster_sizes.get(cluster_idx, 0)
            pct = res.cluster_percentages.get(cluster_idx, 0.0)

            # Descripción estadística automatizada (sin juicio clínico definitivo)
            cal_val = centroid_clean.get("promedio_calorias_fnu", 0)
            pro_val = centroid_clean.get("pct_proteina_fnu", 0)
            car_val = centroid_clean.get("pct_carbohidratos_fnu", 0)
            gra_val = centroid_clean.get("pct_grasa_fnu", 0)
            fib_val = centroid_clean.get("fibra_por_1000kcal", 0)
            azu_val = centroid_clean.get("azucar_por_1000kcal", 0)
            var_val = centroid_clean.get("variabilidad_horaria_min_fnu", 0)

            desc = (
                f"Cluster {cluster_idx} (N={size}, {pct}%): "
                f"Calorías media {cal_val:.1f} kcal (disp. {centroid_clean.get('std_calorias_fnu', 0):.1f}); "
                f"Macros P:{pro_val:.1f}%, C:{car_val:.1f}%, G:{gra_val:.1f}%; "
                f"Densidades: Fibra {fib_val:.1f}g/1000kcal, Azúcar {azu_val:.1f}g/1000kcal, Sodio {centroid_clean.get('sodio_por_1000kcal', 0):.1f}mg/1000kcal; "
                f"Frecuencias D/C/S: {centroid_clean.get('frecuencia_desayuno_fnu', 0):.2f}/{centroid_clean.get('frecuencia_cena_fnu', 0):.2f}/{centroid_clean.get('frecuencia_snack_fnu', 0):.2f}; "
                f"Comidas/día: {centroid_clean.get('promedio_comidas_dia_fnu', 0):.1f}; "
                f"Var horaria: {var_val:.1f} min."
            )

            clusters_data.append({
                "numero_cluster": cluster_idx,
                "centroide": centroid_clean,
                "descripcion_automatica": desc,
                "tamano": size,
                "porcentaje": pct,
            })

        metadata = {
            "modelo": {
                "nombre_mml": f"Clustering de Patrones Alimenticios K={k}",
                "tipo_algoritmo_mml": "kmeans",
                "tarea_mml": "clustering",
                "version_mml": version_mml,
                "version_features_mml": version_features_mml,
                "ruta_artefacto_mml": str(artifact_path).replace("\\", "/"),
                "origen_dataset_mml": "sintetico_controlado",
                "hash_dataset_mml": dataset_hash,
                "numero_muestras_mml": n_samples,
                "numero_pacientes_mml": n_patients,
                "fec_inicio_dataset_mml": "2026-09-01",
                "fec_fin_dataset_mml": "2026-09-07",
                "random_seed_mml": self.random_state,
                "parametros_mml": {
                    "n_clusters": k,
                    "init": "k-means++",
                    "n_init": self.n_init,
                    "max_iter": self.max_iter,
                    "random_state": self.random_state,
                },
                "metricas_rendimiento_mml": {
                    "silhouette_score": round(res.silhouette, 4),
                    "inertia": round(res.inertia, 2),
                    "cluster_sizes": res.cluster_sizes,
                    "cluster_percentages": res.cluster_percentages,
                },
                "features_requeridas_mml": feature_names,
                "est_mml": "validacion",
                "entrenado_en_mml": pd.Timestamp.now().strftime("%Y-%m-%d %H:%M:%S"),
            },
            "clusters": clusters_data,
        }

        return metadata
