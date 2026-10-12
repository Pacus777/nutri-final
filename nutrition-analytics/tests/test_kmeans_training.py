"""
Tests for K-Means training across K=2..6, inertia decrease, silhouette calculation, cluster sizes.
"""

import unittest
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.data.synthetic_generator import generate_synthetic_nutritional_dataset
from src.data.loader import compute_nutrient_densities, CLUSTERING_FEATURES_V1
from src.features.preprocessor import FeaturePreprocessor
from src.models.kmeans_trainer import KMeansExperimenter


class TestKMeansTraining(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.df = generate_synthetic_nutritional_dataset(n_profiles=120, n_ineligible=0, random_state=42)
        cls.df = compute_nutrient_densities(cls.df)
        cls.preprocessor = FeaturePreprocessor(feature_names=CLUSTERING_FEATURES_V1)
        cls.X_scaled = cls.preprocessor.fit_transform(cls.df)
        cls.experimenter = KMeansExperimenter(k_range=range(2, 7), random_state=42)
        cls.results = cls.experimenter.fit_experiments(cls.X_scaled, cls.preprocessor, CLUSTERING_FEATURES_V1)

    def test_all_k_values_evaluated(self):
        self.assertEqual(list(self.results.keys()), [2, 3, 4, 5, 6])

    def test_inertia_strictly_decreases_with_k(self):
        inertias = [self.results[k].inertia for k in range(2, 7)]
        for i in range(len(inertias) - 1):
            self.assertLess(
                inertias[i + 1],
                inertias[i],
                f"Inercia para K={i+3} ({inertias[i+1]}) debería ser menor que para K={i+2} ({inertias[i]})",
            )

    def test_silhouette_score_in_valid_range(self):
        for k in range(2, 7):
            sil = self.results[k].silhouette
            self.assertGreaterEqual(sil, -1.0)
            self.assertLessEqual(sil, 1.0)
            # In synthetic structured data silhouette should be positive (> 0.25)
            self.assertGreater(sil, 0.25)

    def test_cluster_sizes_sum_to_total_samples(self):
        total_samples = len(self.df)
        for k in range(2, 7):
            sizes = self.results[k].cluster_sizes
            self.assertEqual(len(sizes), k)
            self.assertEqual(sum(sizes.values()), total_samples)

    def test_denormalized_centroids_shape_and_columns(self):
        for k in range(2, 7):
            centroids_df = self.results[k].centroids_denormalized
            self.assertEqual(centroids_df.shape, (k, len(CLUSTERING_FEATURES_V1)))
            self.assertEqual(list(centroids_df.columns), CLUSTERING_FEATURES_V1)


if __name__ == "__main__":
    unittest.main()
