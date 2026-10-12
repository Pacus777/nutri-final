"""
Tests for feature scaling with StandardScaler and inverse transform.
"""

import unittest
import numpy as np
import pandas as pd
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.features.preprocessor import FeaturePreprocessor


class TestFeatureScaling(unittest.TestCase):
    def setUp(self):
        self.feature_names = ["feat_a", "feat_b", "feat_c"]
        np.random.seed(42)
        self.df = pd.DataFrame(
            np.random.normal(loc=[2000, 50, 10], scale=[200, 10, 2], size=(100, 3)),
            columns=self.feature_names,
        )

    def test_fit_transform_produces_zero_mean_unit_variance(self):
        preprocessor = FeaturePreprocessor(feature_names=self.feature_names)
        X_scaled = preprocessor.fit_transform(self.df)

        self.assertEqual(X_scaled.shape, (100, 3))
        # Mean should be approximately 0.0 and std approximately 1.0
        np.testing.assert_almost_equal(X_scaled.mean(axis=0), [0.0, 0.0, 0.0], decimal=5)
        np.testing.assert_almost_equal(X_scaled.std(axis=0), [1.0, 1.0, 1.0], decimal=5)

    def test_inverse_transform_recovers_original_scale(self):
        preprocessor = FeaturePreprocessor(feature_names=self.feature_names)
        X_scaled = preprocessor.fit_transform(self.df)
        X_recovered = preprocessor.inverse_transform(X_scaled)

        np.testing.assert_almost_equal(self.df.values, X_recovered, decimal=5)

    def test_transform_unfitted_raises_error(self):
        preprocessor = FeaturePreprocessor(feature_names=self.feature_names)
        with self.assertRaises(RuntimeError):
            preprocessor.transform(self.df)


if __name__ == "__main__":
    unittest.main()
