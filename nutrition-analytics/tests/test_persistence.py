"""
Tests for artifact persistence (.joblib), metadata extraction, and SHA256 dataset hashing.
"""

import json
import os
import tempfile
import unittest
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.data.synthetic_generator import generate_synthetic_nutritional_dataset
from src.data.loader import compute_nutrient_densities, CLUSTERING_FEATURES_V1
from src.features.preprocessor import FeaturePreprocessor
from src.models.kmeans_trainer import KMeansExperimenter


class TestPersistence(unittest.TestCase):
    def setUp(self):
        self.temp_dir = tempfile.TemporaryDirectory()
        self.tmp_path = Path(self.temp_dir.name)

        # Generate small dataset
        self.dataset_csv = self.tmp_path / "test_dataset.csv"
        self.df = generate_synthetic_nutritional_dataset(
            n_profiles=40, n_ineligible=0, random_state=42, output_path=str(self.dataset_csv)
        )
        self.df = compute_nutrient_densities(self.df)

        self.preprocessor = FeaturePreprocessor(feature_names=CLUSTERING_FEATURES_V1)
        self.X_scaled = self.preprocessor.fit_transform(self.df)

        self.experimenter = KMeansExperimenter(k_range=[3], random_state=42)
        self.experimenter.fit_experiments(self.X_scaled, self.preprocessor, CLUSTERING_FEATURES_V1)

    def tearDown(self):
        self.temp_dir.cleanup()

    def test_preprocessor_save_and_load(self):
        scaler_file = self.tmp_path / "scaler_test.joblib"
        self.preprocessor.save(scaler_file)
        self.assertTrue(scaler_file.exists())

        loaded_scaler = FeaturePreprocessor.load(scaler_file)
        self.assertTrue(loaded_scaler.is_fitted)
        self.assertEqual(loaded_scaler.feature_names, self.preprocessor.feature_names)

        # Transforms must be identical
        t1 = self.preprocessor.transform(self.df)
        t2 = loaded_scaler.transform(self.df)
        import numpy as np
        np.testing.assert_almost_equal(t1, t2)

    def test_kmeans_model_artifact_save(self):
        model_file = self.tmp_path / "kmeans_test.joblib"
        saved_path = self.experimenter.save_model_artifact(3, self.preprocessor, model_file)
        self.assertTrue(os.path.exists(saved_path))

    def test_generate_candidate_metadata_payload(self):
        artifact_fake_path = "nutrition-analytics/artifacts/kmeans_v1_k3.joblib"
        meta = self.experimenter.generate_candidate_metadata(
            k=3,
            dataset_path=self.dataset_csv,
            artifact_path=artifact_fake_path,
            n_samples=40,
            n_patients=40,
            feature_names=CLUSTERING_FEATURES_V1,
        )

        self.assertIn("modelo", meta)
        self.assertIn("clusters", meta)

        m = meta["modelo"]
        self.assertEqual(m["origen_dataset_mml"], "sintetico_controlado")
        self.assertEqual(m["version_features_mml"], "v1.0")
        self.assertEqual(m["numero_muestras_mml"], 40)
        self.assertEqual(m["numero_pacientes_mml"], 40)
        self.assertEqual(m["random_seed_mml"], 42)
        self.assertEqual(len(m["hash_dataset_mml"]), 64)  # SHA256 hex string length
        self.assertEqual(m["est_mml"], "validacion")

        clusters = meta["clusters"]
        self.assertEqual(len(clusters), 3)
        for cl in clusters:
            self.assertIn("numero_cluster", cl)
            self.assertIn("centroide", cl)
            self.assertIn("descripcion_automatica", cl)
            self.assertEqual(len(cl["centroide"]), len(CLUSTERING_FEATURES_V1))


if __name__ == "__main__":
    unittest.main()
