"""
Módulo de Entrenamiento y Validación de Decision Tree para Clasificación de Riesgo Nutricional (Fase 2E).
Implementa separación GroupKFold por paciente (80% Dev / 20% Test) y selección por F1-macro.
"""

import os
import json
import hashlib
import numpy as np
import pandas as pd
import joblib
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

from sklearn.tree import DecisionTreeClassifier, export_text, plot_tree
from sklearn.model_selection import GroupKFold
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
    classification_report
)


class DecisionTreeRiskTrainer:
    """Entrenador de árbol de decisión con validación por grupos (GroupKFold) y aislamiento de test."""

    FEATURES = [
        "promedio_calorias_fnu",
        "pct_proteina_fnu",
        "pct_carbohidratos_fnu",
        "pct_grasa_fnu",
        "fibra_por_1000kcal",
        "azucar_por_1000kcal",
        "sodio_por_1000kcal",
        "frecuencia_desayuno_fnu",
        "frecuencia_cena_fnu",
        "promedio_comidas_dia_fnu",
        "variabilidad_horaria_min_fnu",
        "imc_fnu",
    ]

    TARGET = "nivel_riesgo_erp"
    CLASSES = ["bajo", "moderado", "alto"]

    def __init__(self, seed: int = 42):
        self.seed = seed

    def split_dev_test_by_patient(
        self, df: pd.DataFrame, test_patient_ratio: float = 0.20
    ) -> tuple[pd.DataFrame, pd.DataFrame]:
        """
        Divide el dataset en 80% Desarrollo y 20% Test Final garantizando que ningún
        patient_group_id aparezca simultáneamente en ambos conjuntos.
        """
        unique_patients = np.array(df["patient_group_id"].unique())
        rng = np.random.default_rng(self.seed)
        shuffled_patients = rng.permutation(unique_patients)

        n_test = int(np.round(len(shuffled_patients) * test_patient_ratio))
        test_patients = set(shuffled_patients[:n_test])
        dev_patients = set(shuffled_patients[n_test:])

        df_dev = df[df["patient_group_id"].isin(dev_patients)].copy().reset_index(drop=True)
        df_test = df[df["patient_group_id"].isin(test_patients)].copy().reset_index(drop=True)

        return df_dev, df_test

    def run_groupkfold_search(
        self, df_dev: pd.DataFrame, n_splits: int = 5, param_grid: list[dict] = None
    ) -> dict:
        """
        Ejecuta búsqueda de hiperparámetros controlada mediante GroupKFold(5) en el conjunto Dev.
        Evalúa y selecciona el mejor candidato basado en F1-macro medio y desviación estándar.
        """
        if param_grid is None:
            param_grid = [
                {"max_depth": 3, "min_samples_split": 15, "min_samples_leaf": 8, "criterion": "gini", "class_weight": None},
                {"max_depth": 4, "min_samples_split": 15, "min_samples_leaf": 8, "criterion": "gini", "class_weight": None},
                {"max_depth": 4, "min_samples_split": 20, "min_samples_leaf": 10, "criterion": "gini", "class_weight": "balanced"},
                {"max_depth": 5, "min_samples_split": 15, "min_samples_leaf": 8, "criterion": "gini", "class_weight": None},
                {"max_depth": 5, "min_samples_split": 20, "min_samples_leaf": 10, "criterion": "entropy", "class_weight": None},
                {"max_depth": 6, "min_samples_split": 10, "min_samples_leaf": 5, "criterion": "gini", "class_weight": None},
                {"max_depth": 6, "min_samples_split": 20, "min_samples_leaf": 10, "criterion": "gini", "class_weight": None},
            ]

        X_dev = df_dev[self.FEATURES].values
        y_dev = df_dev[self.TARGET].values
        groups_dev = df_dev["patient_group_id"].values

        gkf = GroupKFold(n_splits=n_splits)
        results = []

        for params in param_grid:
            fold_acc = []
            fold_prec = []
            fold_rec = []
            fold_f1 = []

            for train_idx, val_idx in gkf.split(X_dev, y_dev, groups=groups_dev):
                X_tr, y_tr = X_dev[train_idx], y_dev[train_idx]
                X_va, y_va = X_dev[val_idx], y_dev[val_idx]

                clf = DecisionTreeClassifier(random_state=self.seed, **params)
                clf.fit(X_tr, y_tr)
                y_pred = clf.predict(X_va)

                fold_acc.append(accuracy_score(y_va, y_pred))
                fold_prec.append(precision_score(y_va, y_pred, average="macro", zero_division=0))
                fold_rec.append(recall_score(y_va, y_pred, average="macro", zero_division=0))
                fold_f1.append(f1_score(y_va, y_pred, average="macro", zero_division=0))

            mean_f1 = float(np.mean(fold_f1))
            std_f1 = float(np.std(fold_f1))
            mean_acc = float(np.mean(fold_acc))
            mean_prec = float(np.mean(fold_prec))
            mean_rec = float(np.mean(fold_rec))

            results.append({
                "params": params,
                "mean_f1_macro": round(mean_f1, 4),
                "std_f1_macro": round(std_f1, 4),
                "mean_accuracy": round(mean_acc, 4),
                "mean_precision_macro": round(mean_prec, 4),
                "mean_recall_macro": round(mean_rec, 4),
                "score_selection": round(mean_f1 - (0.5 * std_f1), 4),  # Penalizar alta varianza entre folds
            })

        # Ordenar por F1 medio penalizado por varianza
        results.sort(key=lambda r: r["score_selection"], reverse=True)
        best_candidate = results[0]

        return {
            "all_experiments": results,
            "best_params": best_candidate["params"],
            "best_cv_metrics": best_candidate,
        }

    def train_final_and_evaluate_test(
        self, df_dev: pd.DataFrame, df_test: pd.DataFrame, best_params: dict
    ) -> dict:
        """Entrena el árbol con los mejores hiperparámetros en todo Dev y evalúa en Test Final."""
        X_dev = df_dev[self.FEATURES].values
        y_dev = df_dev[self.TARGET].values

        X_test = df_test[self.FEATURES].values
        y_test = df_test[self.TARGET].values

        model = DecisionTreeClassifier(random_state=self.seed, **best_params)
        model.fit(X_dev, y_dev)

        y_test_pred = model.predict(X_test)

        acc = float(accuracy_score(y_test, y_test_pred))
        prec_macro = float(precision_score(y_test, y_test_pred, average="macro", zero_division=0))
        rec_macro = float(recall_score(y_test, y_test_pred, average="macro", zero_division=0))
        f1_macro = float(f1_score(y_test, y_test_pred, average="macro", zero_division=0))

        cm = confusion_matrix(y_test, y_test_pred, labels=self.CLASSES)
        cr_dict = classification_report(y_test, y_test_pred, labels=self.CLASSES, output_dict=True, zero_division=0)

        # Importancia de features
        importances = {
            feat: round(float(imp), 4)
            for feat, imp in zip(self.FEATURES, model.feature_importances_)
        }
        sorted_importances = dict(sorted(importances.items(), key=lambda item: item[1], reverse=True))

        # Reglas en texto
        rules_text = export_text(model, feature_names=self.FEATURES)

        return {
            "model": model,
            "test_metrics": {
                "accuracy": round(acc, 4),
                "precision_macro": round(prec_macro, 4),
                "recall_macro": round(rec_macro, 4),
                "f1_macro": round(f1_macro, 4),
                "tree_depth": int(model.get_depth()),
                "n_leaves": int(model.get_n_leaves()),
            },
            "per_class_metrics": {
                c: {
                    "precision": round(cr_dict[c]["precision"], 4),
                    "recall": round(cr_dict[c]["recall"], 4),
                    "f1_score": round(cr_dict[c]["f1-score"], 4),
                    "support": int(cr_dict[c]["support"]),
                }
                for c in self.CLASSES if c in cr_dict
            },
            "confusion_matrix": cm.tolist(),
            "feature_importances": sorted_importances,
            "rules_text": rules_text,
        }

    def save_artifacts(
        self,
        model: DecisionTreeClassifier,
        cv_results: dict,
        test_eval: dict,
        df_full: pd.DataFrame,
        output_dir: str = "nutrition-analytics/artifacts",
        reports_dir: str = "nutrition-analytics/reports"
    ) -> dict:
        """Guarda el artefacto .joblib, metadata JSON, gráficos y reportes."""
        os.makedirs(output_dir, exist_ok=True)
        os.makedirs(reports_dir, exist_ok=True)

        model_path = os.path.join(output_dir, "decision_tree_v1_candidate.joblib")
        joblib.dump(model, model_path)

        # Hash del dataset completo
        dataset_bytes = df_full.to_csv(index=False).encode("utf-8")
        dataset_hash = hashlib.sha256(dataset_bytes).hexdigest()

        # Hash del artefacto del modelo
        with open(model_path, "rb") as f:
            model_hash = hashlib.sha256(f.read()).hexdigest()

        metadata = {
            "modelo": {
                "nombre_mml": "Decision Tree Riesgo Nutricional Experimental v1",
                "version_mml": "v1.0-dt-candidate",
                "tipo_algoritmo_mml": "decision_tree",
                "tarea_mml": "clasificacion_riesgo",
                "version_features_mml": "v1.0",
                "ruta_artefacto_mml": "nutrition-analytics/artifacts/decision_tree_v1_candidate.joblib",
                "origen_dataset_mml": "sintetico_controlado",
                "hash_dataset_mml": dataset_hash,
                "hash_artefacto_mml": model_hash,
                "numero_muestras_mml": len(df_full),
                "numero_pacientes_mml": int(df_full["patient_group_id"].nunique()),
                "random_seed_mml": self.seed,
                "parametros_mml": cv_results["best_params"],
                "metricas_rendimiento_mml": {
                    "cv_groupkfold_5": cv_results["best_cv_metrics"],
                    "test_final_holdout_20pct": test_eval["test_metrics"],
                    "per_class_test": test_eval["per_class_metrics"],
                    "confusion_matrix_test": test_eval["confusion_matrix"],
                },
                "features_requeridas_mml": self.FEATURES,
                "clases_target": self.CLASSES,
                "protocolo_sintetico": "protocolo_sintetico_riesgo_v1.0",
                "est_mml": "validacion",
            },
            "feature_importances": test_eval["feature_importances"],
            "all_cv_experiments": cv_results["all_experiments"],
        }

        meta_path = os.path.join(output_dir, "decision_tree_v1_metadata.json")
        with open(meta_path, "w", encoding="utf-8") as f:
            json.dump(metadata, f, indent=2, ensure_ascii=False)

        # Guardar visualización de matriz de confusión
        self._plot_confusion_matrix(
            np.array(test_eval["confusion_matrix"]),
            os.path.join(reports_dir, "decision_tree_confusion_matrix.png")
        )

        # Guardar visualización de importancia de features
        self._plot_feature_importances(
            test_eval["feature_importances"],
            os.path.join(reports_dir, "decision_tree_feature_importances.png")
        )

        # Guardar visualización del árbol
        self._plot_tree_diagram(
            model,
            os.path.join(reports_dir, "decision_tree_diagram.png")
        )

        # Guardar reglas textuales
        with open(os.path.join(reports_dir, "decision_tree_rules.txt"), "w", encoding="utf-8") as f:
            f.write(test_eval["rules_text"])

        return {
            "model_path": model_path,
            "metadata_path": meta_path,
            "model_hash": model_hash,
            "dataset_hash": dataset_hash,
        }

    def _plot_confusion_matrix(self, cm: np.ndarray, output_path: str):
        fig, ax = plt.subplots(figsize=(6, 5))
        cax = ax.matshow(cm, cmap=plt.cm.Blues, alpha=0.8)
        fig.colorbar(cax)

        for i in range(cm.shape[0]):
            for j in range(cm.shape[1]):
                ax.text(x=j, y=i, s=cm[i, j], va='center', ha='center', size='x-large')

        ax.set_xticks(range(len(self.CLASSES)))
        ax.set_yticks(range(len(self.CLASSES)))
        ax.set_xticklabels(self.CLASSES)
        ax.set_yticklabels(self.CLASSES)
        ax.set_xlabel('Predicción (Test)', fontsize=12)
        ax.set_ylabel('Ground Truth (Sintético)', fontsize=12)
        ax.set_title('Matriz de Confusión - Test Final (20% Pacientes)', fontsize=13, pad=15)
        plt.tight_layout()
        plt.savefig(output_path, dpi=200)
        plt.close()

    def _plot_feature_importances(self, importances: dict, output_path: str):
        feats = list(reversed(list(importances.keys())))
        vals = list(reversed(list(importances.values())))

        fig, ax = plt.subplots(figsize=(8, 6))
        bars = ax.barh(feats, vals, color='#10b981')
        ax.set_xlabel('Importancia (Gini Importance)', fontsize=11)
        ax.set_title('Ranking de Importancia de Variables - Decision Tree v1', fontsize=12, pad=10)

        for bar in bars:
            w = bar.get_width()
            ax.text(w + 0.005, bar.get_y() + bar.get_height() / 2, f"{w:.3f}", va='center', fontsize=9)

        ax.set_xlim(0, max(vals) * 1.18 if vals else 1.0)
        plt.tight_layout()
        plt.savefig(output_path, dpi=200)
        plt.close()

    def _plot_tree_diagram(self, model: DecisionTreeClassifier, output_path: str):
        fig, ax = plt.subplots(figsize=(20, 10))
        plot_tree(
            model,
            feature_names=self.FEATURES,
            class_names=self.CLASSES,
            filled=True,
            rounded=True,
            fontsize=8,
            ax=ax
        )
        ax.set_title('Árbol de Decisión Entrenado (Reglas Clínicas Experimentales v1)', fontsize=14, pad=12)
        plt.tight_layout()
        plt.savefig(output_path, dpi=250)
        plt.close()
