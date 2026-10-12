import os
import unittest
from pathlib import Path
import sys
import numpy as np
import pandas as pd
import joblib

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.data.supervised_synthetic_generator import SupervisedSyntheticGenerator
from src.models.decision_tree_trainer import DecisionTreeRiskTrainer


class TestDecisionTreeSupervised(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        cls.generator = SupervisedSyntheticGenerator(seed=42)
        cls.df = cls.generator.generate_supervised_dataset(n_patients=200, windows_per_patient=3)
        cls.trainer = DecisionTreeRiskTrainer(seed=42)

    def test_synthetic_dataset_shape_and_provenance(self):
        """Verifica que el dataset contenga exactamente 200 pacientes x 3 ventanas = 600 filas."""
        self.assertEqual(len(self.df), 600)
        self.assertEqual(self.df["patient_group_id"].nunique(), 200)
        self.assertTrue((self.df["origen_datos"] == "sintetico_controlado").all())
        self.assertTrue((self.df["version_protocolo"] == "protocolo_sintetico_riesgo_v1.0").all())

        # Verificar presencia de las 3 clases target
        unique_targets = set(self.df["nivel_riesgo_erp"].unique())
        self.assertEqual(unique_targets, {"bajo", "moderado", "alto"})

    def test_patient_isolation_and_zero_leakage(self):
        """Verifica que no exista ninguna fuga de pacientes entre Dev y Test."""
        df_dev, df_test = self.trainer.split_dev_test_by_patient(self.df, test_patient_ratio=0.20)

        dev_patients = set(df_dev["patient_group_id"])
        test_patients = set(df_test["patient_group_id"])

        self.assertEqual(len(dev_patients), 160)
        self.assertEqual(len(test_patients), 40)
        self.assertEqual(len(df_dev), 480)
        self.assertEqual(len(df_test), 120)

        # Fuga nula
        intersection = dev_patients.intersection(test_patients)
        self.assertEqual(len(intersection), 0, f"Leakage detectado: {intersection}")

    def test_feature_vector_exclusion_of_adherence_and_metadata(self):
        """Verifica que adherencia_registro_fnu, ratios e IDs estén estrictamente excluidos del vector predictivo."""
        features = self.trainer.FEATURES

        self.assertEqual(len(features), 12)
        self.assertNotIn("adherencia_registro_fnu", features)
        self.assertNotIn("ratio_validacion_fnu", features)
        self.assertNotIn("patient_group_id", features)
        self.assertNotIn("origen_datos", features)
        self.assertNotIn("fec_inicio", features)
        self.assertNotIn("fec_fin", features)

        # Comprobar que todas las 12 features existan en el DataFrame
        for feat in features:
            self.assertIn(feat, self.df.columns)

    def test_reproducibility_with_seed(self):
        """Verifica que la generación y el entrenamiento sean deterministas y reproducibles."""
        gen2 = SupervisedSyntheticGenerator(seed=42)
        df2 = gen2.generate_supervised_dataset(n_patients=200, windows_per_patient=3)

        pd.testing.assert_frame_equal(self.df, df2)

    def test_decision_tree_training_and_serialization(self):
        """Verifica el entrenamiento, serialización .joblib y capacidad de inferencia multiclase."""
        df_dev, df_test = self.trainer.split_dev_test_by_patient(self.df, test_patient_ratio=0.20)
        cv_results = self.trainer.run_groupkfold_search(df_dev, n_splits=5)

        best_params = cv_results["best_params"]
        test_eval = self.trainer.train_final_and_evaluate_test(df_dev, df_test, best_params)

        model = test_eval["model"]
        self.assertIsNotNone(model)
        self.assertLessEqual(model.get_depth(), 5)

        # Test de serialización y carga
        temp_path = "nutrition-analytics/artifacts/temp_test_tree.joblib"
        joblib.dump(model, temp_path)
        loaded_model = joblib.load(temp_path)

        preds = loaded_model.predict(df_test[self.trainer.FEATURES].values)
        self.assertEqual(len(preds), len(df_test))

        # Verificar que el modelo pueda predecir las 3 clases
        predicted_classes = set(preds)
        self.assertTrue({"bajo", "moderado", "alto"}.issubset(predicted_classes))

        if os.path.exists(temp_path):
            os.remove(temp_path)


if __name__ == "__main__":
    unittest.main()
