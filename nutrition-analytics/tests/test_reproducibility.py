"""
Tests for reproducibility using random seeds in synthetic generation and K-Means.
"""

import unittest
import numpy as np
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.data.synthetic_generator import generate_synthetic_nutritional_dataset
from src.data.loader import compute_nutrient_densities, CLUSTERING_FEATURES_V1
from src.features.preprocessor import FeaturePreprocessor
from src.models.kmeans_trainer import KMeansExperimenter


class TestReproducibility(unittest.TestCase):
    def test_synthetic_data_generation_reproducibility(self):
        df1 = generate_synthetic_nutritional_dataset(n_profiles=50, n_ineligible=5, random_state=42)
        df2 = generate_synthetic_nutritional_dataset(n_profiles=50, n_ineligible=5, random_state=42)

        # Datasets with same seed must be strictly equal
        pd_testing = np.testing.assert_array_equal(
            df1["promedio_calorias_fnu"].values,
            df2["promedio_calorias_fnu"].values,
        )
        np.testing.assert_array_equal(
            df1["pct_proteina_fnu"].values,
            df2["pct_proteina_fnu"].values,
        )

    def test_kmeans_training_reproducibility(self):
        df = generate_synthetic_nutritional_dataset(n_profiles=60, n_ineligible=0, random_state=42)
        df = compute_nutrient_densities(df)

        preprocessor = FeaturePreprocessor(feature_names=CLUSTERING_FEATURES_V1)
        X_scaled = preprocessor.fit_transform(df)

        exp1 = KMeansExperimenter(k_range=[3], random_state=42)
        res1 = exp1.fit_experiments(X_scaled, preprocessor, CLUSTERING_FEATURES_V1)[3]

        exp2 = KMeansExperimenter(k_range=[3], random_state=42)
        res2 = exp2.fit_experiments(X_scaled, preprocessor, CLUSTERING_FEATURES_V1)[3]

        # Inertia, labels and centroids must match exactly
        self.assertAlmostEqual(res1.inertia, res2.inertia, places=5)
        self.assertAlmostEqual(res1.silhouette, res2.silhouette, places=5)
        np.testing.assert_array_equal(res1.labels, res2.labels)
        np.testing.assert_almost_equal(res1.centroids_scaled, res2.centroids_scaled, decimal=6)


if __name__ == "__main__":
    unittest.main()
