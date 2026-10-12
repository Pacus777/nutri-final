#!/usr/bin/env python
"""
Script de experimentación para Clustering Nutricional K-Means (K=2..6).
Calcula métricas, genera gráficos de codo/silhouette/PCA, serializa artefactos y metadatos del candidato.
"""

import argparse
import json
from pathlib import Path
import sys
import pandas as pd

# Asegurar que el paquete src esté en el sys.path
current_dir = Path(__file__).resolve().parent
project_root = current_dir.parent
sys.path.insert(0, str(project_root))

from src.data.loader import (
    load_feature_dataset,
    CLUSTERING_FEATURES_V1,
)
from src.data.filter import filter_eligible_snapshots
from src.data.synthetic_generator import generate_synthetic_nutritional_dataset
from src.features.preprocessor import FeaturePreprocessor
from src.models.kmeans_trainer import KMeansExperimenter
from src.visualization.plots import (
    plot_elbow_curve,
    plot_silhouette_curve,
    plot_pca_clusters,
    plot_cluster_radar_profiles,
)


def run_experiments(
    dataset_path: str = None,
    candidate_k: int = 4,
    random_state: int = 42,
    figures_dir: str = None,
    artifacts_dir: str = None,
):
    # Configurar directorios
    figures_p = Path(figures_dir) if figures_dir else project_root / "reports" / "figures"
    artifacts_p = Path(artifacts_dir) if artifacts_dir else project_root / "artifacts"
    figures_p.mkdir(parents=True, exist_ok=True)
    artifacts_p.mkdir(parents=True, exist_ok=True)

    # 1. Dataset Resolution / Generation
    if dataset_path is None:
        synth_path = project_root / "datasets" / "synthetic" / "synthetic_features_v1.0.csv"
        if not synth_path.exists():
            print(f"[Dataset] Generando dataset sintético en {synth_path}...")
            generate_synthetic_nutritional_dataset(
                n_profiles=300,
                n_ineligible=30,
                random_state=random_state,
                output_path=str(synth_path),
            )
        dataset_path = str(synth_path)

    print(f"\n============================================================")
    print(f" EXPERIMENTACIÓN K-MEANS NUTRICIONAL (K=2..6)")
    print(f"============================================================")
    print(f" Dataset de entrada : {dataset_path}")
    print(f" Random seed        : {random_state}")
    print(f" Candidato evaluado : K={candidate_k}")
    print(f" Directorio figuras : {figures_p}")
    print(f" Directorio artefactos: {artifacts_p}")
    print(f"============================================================\n")

    # 2. Cargar y Transformar Densidades
    raw_df = load_feature_dataset(dataset_path)
    print(f"[1/6] Dataset cargado: {len(raw_df)} registros totales.")

    # 3. Filtrar según Criterios de Suficiencia (Ventana 7 días, Adherencia >= 0.7143)
    eligible_df, stats = filter_eligible_snapshots(
        raw_df, target_window=7, min_adherence=0.7143, verbose=True
    )
    n_samples = len(eligible_df)
    n_unique_patients = eligible_df["paciente_anonimo_id"].nunique() if "paciente_anonimo_id" in eligible_df.columns else n_samples
    print(f"[2/6] Filtrado completado: {n_samples} snapshots elegibles ({n_unique_patients} pacientes únicos).")

    # 4. Preprocesamiento (StandardScaler)
    preprocessor = FeaturePreprocessor(feature_names=CLUSTERING_FEATURES_V1)
    X_scaled = preprocessor.fit_transform(eligible_df)
    
    # Guardar Scaler
    scaler_path = artifacts_p / "scaler_v1.joblib"
    preprocessor.save(scaler_path)
    print(f"[3/6] Scaler ajustado y guardado en {scaler_path}")

    # 5. Experimentación K=2..6
    print(f"[4/6] Entrenando modelos K-Means para K=2, 3, 4, 5, 6 (seed={random_state})...")
    experimenter = KMeansExperimenter(
        k_range=range(2, 7),
        random_state=random_state,
        n_init=10,
        max_iter=300,
    )
    results = experimenter.fit_experiments(X_scaled, preprocessor, CLUSTERING_FEATURES_V1)

    # Mostrar Tabla Comparativa
    comp_table = experimenter.get_comparison_table()
    print("\n------------------------------------------------------------")
    print(" TABLA COMPARATIVA DE MODELOS (K=2 a 6)")
    print("------------------------------------------------------------")
    print(comp_table.to_string(index=False))
    print("------------------------------------------------------------\n")

    # 6. Generación de Gráficos
    print("[5/6] Generando gráficos de diagnóstico y visualización...")
    k_list = list(results.keys())
    inertias = [results[k].inertia for k in k_list]
    silhouettes = [results[k].silhouette for k in k_list]

    elbow_file = plot_elbow_curve(
        k_list, inertias, figures_p / "elbow_curve_k2_k6.png", selected_k=candidate_k
    )
    sil_file = plot_silhouette_curve(
        k_list, silhouettes, figures_p / "silhouette_curve_k2_k6.png", selected_k=candidate_k
    )
    pca_file = plot_pca_clusters(
        X_scaled, results[candidate_k].labels, candidate_k, figures_p / f"pca_2d_clusters_k{candidate_k}.png", random_state=random_state
    )
    centroids_bar_file = plot_cluster_radar_profiles(
        results[candidate_k].centroids_denormalized, figures_p / f"centroids_comparison_k{candidate_k}.png"
    )

    print(f"  -> Gráfico del Codo       : {elbow_file}")
    print(f"  -> Gráfico de Silhouette  : {sil_file}")
    print(f"  -> Gráfico PCA 2D (K={candidate_k})  : {pca_file}")
    print(f"  -> Comparativa Centroides : {centroids_bar_file}")

    # 7. Persistencia del Modelo Candidato y Metadatos JSON
    print(f"\n[6/6] Guardando artefacto y metadatos del candidato K={candidate_k}...")
    model_artifact_path = artifacts_p / f"kmeans_v1_k{candidate_k}_candidate.joblib"
    experimenter.save_model_artifact(candidate_k, preprocessor, model_artifact_path)

    metadata_payload = experimenter.generate_candidate_metadata(
        k=candidate_k,
        dataset_path=dataset_path,
        artifact_path=str(model_artifact_path),
        n_samples=n_samples,
        n_patients=n_unique_patients,
        feature_names=CLUSTERING_FEATURES_V1,
        version_mml=f"v1.0.0-k{candidate_k}",
        version_features_mml="v1.0",
    )

    metadata_json_path = artifacts_p / "candidate_model_metadata.json"
    with open(metadata_json_path, "w", encoding="utf-8") as f:
        json.dump(metadata_payload, f, indent=2, ensure_ascii=False)

    print(f"  -> Artefacto serializado  : {model_artifact_path}")
    print(f"  -> Metadatos JSON exportados: {metadata_json_path}")
    print(f"  -> Hash SHA256 Dataset    : {metadata_payload['modelo']['hash_dataset_mml']}")

    # Imprimir Centroides Desnormalizados
    print("\n============================================================")
    print(f" CENTROIDES DESNORMALIZADOS DEL CANDIDATO (K={candidate_k})")
    print("============================================================")
    cand_res = results[candidate_k]
    pd.set_option("display.max_columns", 15)
    pd.set_option("display.width", 1000)
    print(cand_res.centroids_denormalized.round(2).to_string())

    print("\n============================================================")
    print(f" DESCRIPCIÓN AUTOMÁTICA DE CLUSTERS (Sin etiquetas clínicas definitivas)")
    print("============================================================")
    for cl in metadata_payload["clusters"]:
        print(f"[{cl['numero_cluster']}] {cl['descripcion_automatica']}")

    return results, metadata_payload


def main():
    parser = argparse.ArgumentParser(description="Ejecutar experimentación K-Means nutricional.")
    parser.add_argument("--dataset", type=str, default=None, help="Ruta al CSV de entrenamiento")
    parser.add_argument("--k", type=int, default=4, help="Número de clusters del modelo candidato sugerido (default: 4)")
    parser.add_argument("--seed", type=int, default=42, help="Semilla aleatoria (default: 42)")
    parser.add_argument("--figures", type=str, default=None, help="Directorio para guardar figuras")
    parser.add_argument("--artifacts", type=str, default=None, help="Directorio para guardar artefactos")

    args = parser.parse_args()
    run_experiments(
        dataset_path=args.dataset,
        candidate_k=args.k,
        random_state=args.seed,
        figures_dir=args.figures,
        artifacts_dir=args.artifacts,
    )


if __name__ == "__main__":
    main()
