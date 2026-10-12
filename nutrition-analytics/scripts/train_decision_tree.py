"""
Script de Entrenamiento y Experimentación del Decision Tree Supervisado (Fase 2E).
Ejecuta la generación del dataset sintético, partición sin leakage (GroupKFold),
búsqueda de hiperparámetros, evaluación en Test Final y persistencia de artefactos.
"""

import os
import sys

# Añadir raíz de nutrition-analytics al sys.path
BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from src.data.supervised_synthetic_generator import SupervisedSyntheticGenerator
from src.models.decision_tree_trainer import DecisionTreeRiskTrainer


def main():
    print("=" * 75)
    print("  FASE 2E — ENTRENAMIENTO DE DECISION TREE EXPERIMENTAL V1 (RIESGO NUTRICIONAL)")
    print("=" * 75)

    # 1. Generación del Dataset Sintético Supervisado
    dataset_path = os.path.join(BASE_DIR, "datasets", "synthetic", "supervised_synthetic_w7_v1.0.csv")
    generator = SupervisedSyntheticGenerator(seed=42)
    
    print("\n[Paso 1] Generando dataset sintético supervisado...")
    df = generator.generate_supervised_dataset(
        n_patients=200,
        windows_per_patient=3,
        output_path=dataset_path
    )

    print(f"-> Total observaciones: {len(df)}")
    print(f"-> Total pacientes únicos: {df['patient_group_id'].nunique()}")
    print("-> Distribución de clases target:")
    for clase, count in df['nivel_riesgo_erp'].value_counts().items():
        print(f"   - {clase.upper():<10}: {count} ({count/len(df)*100:.1f}%)")

    # 2. Inicializar Entrenador
    trainer = DecisionTreeRiskTrainer(seed=42)

    # 3. Partición 80% Dev / 20% Test aislada por patient_group_id
    print("\n[Paso 2] Dividiendo dataset por paciente (80% Dev / 20% Test Final)...")
    df_dev, df_test = trainer.split_dev_test_by_patient(df, test_patient_ratio=0.20)

    print(f"-> Dev Set : {len(df_dev)} muestras ({df_dev['patient_group_id'].nunique()} pacientes)")
    print(f"-> Test Set: {len(df_test)} muestras ({df_test['patient_group_id'].nunique()} pacientes)")

    # Comprobar ausencia de fuga entre grupos
    dev_patients = set(df_dev["patient_group_id"])
    test_patients = set(df_test["patient_group_id"])
    overlap = dev_patients.intersection(test_patients)
    assert len(overlap) == 0, f"ERROR: Fuga de pacientes detectada: {overlap}"
    print("-> Verificación de Fuga: 0 pacientes solapados (Aislamiento Total OK)")

    # 4. Búsqueda de Hiperparámetros mediante GroupKFold(5) en Dev
    print("\n[Paso 3] Ejecutando búsqueda controlada con GroupKFold(n_splits=5) en Dev...")
    cv_results = trainer.run_groupkfold_search(df_dev, n_splits=5)

    print("\nResultados de Experimentos en GroupKFold:")
    print(f"{'MaxDepth':<10}{'MinSplit':<10}{'MinLeaf':<10}{'Crit':<10}{'Weight':<10}{'F1 Macro (Mean±Std)':<24}{'Acc (Mean)':<12}")
    print("-" * 86)
    for exp in cv_results["all_experiments"]:
        p = exp["params"]
        f1_str = f"{exp['mean_f1_macro']:.4f} ± {exp['std_f1_macro']:.4f}"
        print(f"{p['max_depth']:<10}{p['min_samples_split']:<10}{p['min_samples_leaf']:<10}{p['criterion']:<10}{str(p['class_weight']):<10}{f1_str:<24}{exp['mean_accuracy']:.4f}")

    best_p = cv_results["best_params"]
    print(f"\n-> Configuración Seleccionada (Mayor F1 penalizado por varianza): {best_p}")
    print(f"   F1 Macro CV: {cv_results['best_cv_metrics']['mean_f1_macro']:.4f} (± {cv_results['best_cv_metrics']['std_f1_macro']:.4f})")
    print(f"   Accuracy CV: {cv_results['best_cv_metrics']['mean_accuracy']:.4f}")

    # 5. Entrenamiento Final en Dev y Evaluación en Test Final
    print("\n[Paso 4] Entrenando en Dev completo y evaluando sobre TEST FINAL (120 observaciones)...")
    test_eval = trainer.train_final_and_evaluate_test(df_dev, df_test, best_p)

    m = test_eval["test_metrics"]
    print("\n" + "=" * 50)
    print("  RESULTADOS SOBRE TEST FINAL AISLADO (40 PACIENTES)")
    print("=" * 50)
    print(f"  Accuracy       : {m['accuracy']:.4f}")
    print(f"  Precision Macro: {m['precision_macro']:.4f}")
    print(f"  Recall Macro   : {m['recall_macro']:.4f}")
    print(f"  F1 Macro       : {m['f1_macro']:.4f}")
    print(f"  Profundidad    : {m['tree_depth']} niveles")
    print(f"  Número de Hojas: {m['n_leaves']} hojas")

    print("\nMétricas por Clase en Test Final:")
    print(f"{'Clase':<12}{'Precision':<12}{'Recall':<12}{'F1-Score':<12}{'Support':<10}")
    print("-" * 58)
    for c, metrics in test_eval["per_class_metrics"].items():
        print(f"{c.upper():<12}{metrics['precision']:<12.4f}{metrics['recall']:<12.4f}{metrics['f1_score']:<12.4f}{metrics['support']:<10}")

    print("\nMatriz de Confusión en Test Final (Filas: Real, Columnas: Predicción):")
    print(f"{'':<12}{'Bajo (Pred)':<14}{'Moderado (Pred)':<18}{'Alto (Pred)':<14}")
    for idx, c in enumerate(trainer.CLASSES):
        row = test_eval["confusion_matrix"][idx]
        print(f"{c.upper() + ' (Real)':<12}{row[0]:<14}{row[1]:<18}{row[2]:<14}")

    print("\nRanking de Importancia de Variables (Gini):")
    for feat, imp in test_eval["feature_importances"].items():
        if imp > 0.001:
            print(f"  - {feat:<30}: {imp:.4f} ({imp*100:.1f}%)")

    # 6. Guardar Artefactos
    print("\n[Paso 5] Guardando artefactos y reportes...")
    artifacts = trainer.save_artifacts(
        model=test_eval["model"],
        cv_results=cv_results,
        test_eval=test_eval,
        df_full=df,
        output_dir=os.path.join(BASE_DIR, "artifacts"),
        reports_dir=os.path.join(BASE_DIR, "reports")
    )

    print(f"-> Modelo .joblib: {artifacts['model_path']}")
    print(f"-> Metadata JSON : {artifacts['metadata_path']}")
    print(f"-> SHA256 Modelo : {artifacts['model_hash']}")
    print(f"-> SHA256 Dataset: {artifacts['dataset_hash']}")

    print("\n" + "=" * 75)
    print("  ENTRENAMIENTO Y REGISTRO DE ARTEFACTOS COMPLETADO EXITOSAMENTE")
    print("=" * 75)


if __name__ == "__main__":
    main()
