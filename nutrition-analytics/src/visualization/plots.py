"""
Gráficos y diagramas de diagnóstico para Clustering Nutricional K-Means.
"""

from pathlib import Path
from typing import Dict, List, Union
import matplotlib
matplotlib.use("Agg")  # Backend no interactivo para ejecución en servidor/scripts
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from sklearn.decomposition import PCA


def plot_elbow_curve(
    k_range: List[int],
    inertias: List[float],
    output_path: Union[str, Path],
    selected_k: int = 4,
) -> str:
    """Genera y guarda la curva del Método del Codo (Inercia vs K)."""
    fig, ax = plt.subplots(figsize=(8, 5), dpi=150)
    
    ax.plot(k_range, inertias, "o-", color="#2563eb", linewidth=2.5, markersize=8, label="Inertia (WCSS)")
    
    # Resaltar puntos
    for k, inert in zip(k_range, inertias):
        ax.annotate(
            f"{inert:.1f}",
            (k, inert),
            textcoords="offset points",
            xytext=(0, 10),
            ha="center",
            fontsize=9,
            fontweight="semibold",
            color="#1e3a8a",
        )

    if selected_k in k_range:
        ax.axvline(x=selected_k, color="#dc2626", linestyle="--", alpha=0.7, label=f"Candidato Sugerido (K={selected_k})")

    ax.set_title("Método del Codo (Elbow Method) - K-Means Nutricional", fontsize=13, fontweight="bold", pad=12)
    ax.set_xlabel("Número de Clusters (K)", fontsize=11, fontweight="medium")
    ax.set_ylabel("Inercia (Within-Cluster Sum of Squares)", fontsize=11, fontweight="medium")
    ax.set_xticks(k_range)
    ax.grid(True, linestyle=":", alpha=0.6)
    ax.legend(frameon=True, facecolor="white", loc="upper right")
    plt.tight_layout()

    out = Path(output_path)
    out.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(out)
    plt.close(fig)
    return str(out)


def plot_silhouette_curve(
    k_range: List[int],
    silhouette_scores: List[float],
    output_path: Union[str, Path],
    selected_k: int = 4,
) -> str:
    """Genera y guarda la curva de Coeficiente de Silhouette por K."""
    fig, ax = plt.subplots(figsize=(8, 5), dpi=150)
    
    ax.plot(k_range, silhouette_scores, "s-", color="#059669", linewidth=2.5, markersize=8, label="Silhouette Score")
    
    for k, score in zip(k_range, silhouette_scores):
        ax.annotate(
            f"{score:.4f}",
            (k, score),
            textcoords="offset points",
            xytext=(0, 10),
            ha="center",
            fontsize=9,
            fontweight="semibold",
            color="#064e3b",
        )

    if selected_k in k_range:
        ax.axvline(x=selected_k, color="#dc2626", linestyle="--", alpha=0.7, label=f"Candidato Sugerido (K={selected_k})")

    ax.set_title("Coeficiente de Silhouette por Número de Clusters (K)", fontsize=13, fontweight="bold", pad=12)
    ax.set_xlabel("Número de Clusters (K)", fontsize=11, fontweight="medium")
    ax.set_ylabel("Silhouette Score [-1 a 1]", fontsize=11, fontweight="medium")
    ax.set_xticks(k_range)
    ax.grid(True, linestyle=":", alpha=0.6)
    ax.legend(frameon=True, facecolor="white", loc="lower right")
    plt.tight_layout()

    out = Path(output_path)
    out.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(out)
    plt.close(fig)
    return str(out)


def plot_pca_clusters(
    X_scaled: np.ndarray,
    labels: np.ndarray,
    k: int,
    output_path: Union[str, Path],
    random_state: int = 42,
) -> str:
    """
    Proyecta variables a 2D utilizando PCA exclusivamente para interpretación visual y guarda el diagrama de dispersión.
    """
    pca = PCA(n_components=2, random_state=random_state)
    coords = pca.fit_transform(X_scaled)
    var_exp = pca.explained_variance_ratio_

    fig, ax = plt.subplots(figsize=(9, 6), dpi=150)
    
    palette = ["#2563eb", "#dc2626", "#059669", "#d97706", "#7c3aed", "#db2777"]
    
    for cluster_id in range(k):
        mask = labels == cluster_id
        color = palette[cluster_id % len(palette)]
        ax.scatter(
            coords[mask, 0],
            coords[mask, 1],
            c=color,
            label=f"Cluster {cluster_id} (N={np.sum(mask)})",
            alpha=0.75,
            edgecolors="none",
            s=45,
        )

    # Graficar centroides en el espacio 2D de PCA
    pca_centroids = pca.transform(
        np.array([X_scaled[labels == c].mean(axis=0) for c in range(k)])
    )
    ax.scatter(
        pca_centroids[:, 0],
        pca_centroids[:, 1],
        c="black",
        marker="X",
        s=140,
        linewidths=1.5,
        label="Centroides (PCA 2D)",
        zorder=10,
    )

    ax.set_title(
        f"Visualización 2D de Clusters (PCA) - K={k}\n"
        f"Varianza explicada: PC1={var_exp[0]*100:.1f}%, PC2={var_exp[1]*100:.1f}% (Total={sum(var_exp)*100:.1f}%)",
        fontsize=12,
        fontweight="bold",
        pad=12,
    )
    ax.set_xlabel(f"Componente Principal 1 ({var_exp[0]*100:.1f}% var)", fontsize=10)
    ax.set_ylabel(f"Componente Principal 2 ({var_exp[1]*100:.1f}% var)", fontsize=10)
    ax.grid(True, linestyle=":", alpha=0.5)
    ax.legend(frameon=True, facecolor="white", loc="best", fontsize=9)
    plt.tight_layout()

    out = Path(output_path)
    out.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(out)
    plt.close(fig)
    return str(out)


def plot_cluster_radar_profiles(
    centroids_df: pd.DataFrame,
    output_path: Union[str, Path],
) -> str:
    """Genera gráficos de barras comparativos de métricas nutricionales clave entre clusters."""
    k = len(centroids_df)
    key_features = [
        ("promedio_calorias_fnu", "Calorías (kcal/día)"),
        ("pct_proteina_fnu", "% Proteína"),
        ("pct_carbohidratos_fnu", "% Carbohidratos"),
        ("pct_grasa_fnu", "% Grasa"),
        ("fibra_por_1000kcal", "Fibra (g/1000kcal)"),
        ("azucar_por_1000kcal", "Azúcar (g/1000kcal)"),
        ("sodio_por_1000kcal", "Sodio (mg/1000kcal)"),
        ("variabilidad_horaria_min_fnu", "Var. Horaria (min)"),
    ]

    fig, axes = plt.subplots(2, 4, figsize=(16, 7), dpi=150)
    axes = axes.flatten()
    colors = ["#2563eb", "#dc2626", "#059669", "#d97706", "#7c3aed", "#db2777"]

    for idx, (col, title) in enumerate(key_features):
        ax = axes[idx]
        if col in centroids_df.columns:
            vals = centroids_df[col].values
            bars = ax.bar(
                [f"C{i}" for i in range(k)],
                vals,
                color=[colors[i % len(colors)] for i in range(k)],
                alpha=0.85,
            )
            for bar in bars:
                height = bar.get_height()
                ax.annotate(
                    f"{height:.1f}",
                    xy=(bar.get_x() + bar.get_width() / 2, height),
                    xytext=(0, 3),
                    textcoords="offset points",
                    ha="center",
                    fontsize=8,
                )
        ax.set_title(title, fontsize=10, fontweight="bold")
        ax.grid(axis="y", linestyle=":", alpha=0.5)

    plt.suptitle("Comparativa de Centroides por Feature Clave", fontsize=14, fontweight="bold", y=1.02)
    plt.tight_layout()

    out = Path(output_path)
    out.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(out)
    plt.close(fig)
    return str(out)
