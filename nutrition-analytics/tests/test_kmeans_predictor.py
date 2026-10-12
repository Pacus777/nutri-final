"""
Unit tests for KMeansPredictor inference component.
Tests transform-only behavior, Euclidean distances, null confidence, and reproducibility.
"""

import unittest
import numpy as np
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.models.kmeans_predictor import KMeansPredictor


class TestKMeansPredictor(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        project_root = Path(__file__).resolve().parent.parent
        cls.scaler_path = project_root / "artifacts" / "scaler_v1.joblib"
        cls.model_path = project_root / "artifacts" / "kmeans_v1_k4_candidate.joblib"
        cls.predictor = KMeansPredictor(scaler_path=cls.scaler_path, model_path=cls.model_path)

        cls.sample_vector = {
            "promedio_calorias_fnu": 2100.0,
            "std_calorias_fnu": 150.0,
            "pct_proteina_fnu": 20.0,
            "pct_carbohidratos_fnu": 50.0,
            "pct_grasa_fnu": 30.0,
            "promedio_fibra_g_fnu": 26.0,
            "promedio_azucar_g_fnu": 38.0,
            "promedio_sodio_mg_fnu": 2200.0,
            "frecuencia_desayuno_fnu": 0.90,
            "frecuencia_cena_fnu": 0.85,
            "frecuencia_snack_fnu": 0.45,
            "promedio_comidas_dia_fnu": 3.5,
            "variabilidad_horaria_min_fnu": 28.0,
        }

    def test_predictor_loads_fitted_scaler_and_model(self):
        self.assertTrue(self.predictor.preprocessor.is_fitted)
        self.assertEqual(self.predictor.k, 4)
        self.assertIsNotNone(self.predictor.model)

    def test_inference_does_not_alter_scaler_parameters(self):
        # Capture mean and scale before inference
        mean_before = np.copy(self.predictor.preprocessor.scaler.mean_)
        scale_before = np.copy(self.predictor.preprocessor.scaler.scale_)

        # Run 5 predictions
        for _ in range(5):
            res = self.predictor.predict(self.sample_vector)

        # Scaler parameters must remain strictly identical
        np.testing.assert_array_equal(mean_before, self.predictor.preprocessor.scaler.mean_)
        np.testing.assert_array_equal(scale_before, self.predictor.preprocessor.scaler.scale_)

    def test_prediction_reproducibility(self):
        res1 = self.predictor.predict(self.sample_vector)
        res2 = self.predictor.predict(self.sample_vector)

        self.assertEqual(res1["cluster_asignado"], res2["cluster_asignado"])
        self.assertEqual(res1["distancia_centroide_asignado"], res2["distancia_centroide_asignado"])
        self.assertEqual(res1["distancias_centroides"], res2["distancias_centroides"])

    def test_distance_calculation_properties(self):
        res = self.predictor.predict(self.sample_vector)

        # Distance to assigned cluster must be >= 0
        assigned_d = res["distancia_centroide_asignado"]
        self.assertGreaterEqual(assigned_d, 0.0)

        # Assigned cluster distance must be minimum among all distances
        all_d = res["distancias_centroides"]
        self.assertEqual(len(all_d), 4)
        min_d = min(all_d.values())
        self.assertAlmostEqual(assigned_d, min_d, places=4)

    def test_absence_of_fake_confidence_scores(self):
        res = self.predictor.predict(self.sample_vector)

        self.assertIsNone(res["score_confianza"])
        self.assertIsNone(res["probabilidades_clases"])
        self.assertIn("Modelo experimental", res["etiqueta_experimental"])


if __name__ == "__main__":
    unittest.main()
