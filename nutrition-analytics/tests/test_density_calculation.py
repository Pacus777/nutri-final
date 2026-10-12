"""
Tests for nutrient density calculations per 1000 kcal with zero division safety.
"""

import unittest
import numpy as np
import pandas as pd
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.data.loader import (
    calculate_density_per_1000kcal,
    compute_nutrient_densities,
)


class TestDensityCalculation(unittest.TestCase):
    def test_scalar_density_calculation_normal(self):
        # 25g fiber with 2000 kcal -> (25 / 2000) * 1000 = 12.5 g/1000kcal
        result = calculate_density_per_1000kcal(25.0, 2000.0)
        self.assertAlmostEqual(result, 12.5, places=4)

        # 2300mg sodium with 2000 kcal -> (2300 / 2000) * 1000 = 1150.0 mg/1000kcal
        result_sod = calculate_density_per_1000kcal(2300.0, 2000.0)
        self.assertAlmostEqual(result_sod, 1150.0, places=4)

    def test_zero_calories_protection(self):
        # Division by zero must safely return 0.0 without raising exception
        self.assertEqual(calculate_density_per_1000kcal(25.0, 0.0), 0.0)
        self.assertEqual(calculate_density_per_1000kcal(25.0, -100.0), 0.0)
        self.assertEqual(calculate_density_per_1000kcal(np.nan, 2000.0), 0.0)
        self.assertEqual(calculate_density_per_1000kcal(25.0, np.nan), 0.0)

    def test_series_vectorized_density_calculation(self):
        cal = pd.Series([2000.0, 0.0, 1500.0, np.nan])
        fib = pd.Series([30.0, 10.0, 15.0, 5.0])

        densities = calculate_density_per_1000kcal(fib, cal)

        self.assertAlmostEqual(densities.iloc[0], 15.0, places=4)
        self.assertEqual(densities.iloc[1], 0.0)
        self.assertAlmostEqual(densities.iloc[2], 10.0, places=4)
        self.assertEqual(densities.iloc[3], 0.0)

    def test_compute_nutrient_densities_dataframe(self):
        df = pd.DataFrame({
            "promedio_calorias_fnu": [2000.0, 1000.0],
            "promedio_fibra_g_fnu": [20.0, 15.0],
            "promedio_azucar_g_fnu": [40.0, 25.0],
            "promedio_sodio_mg_fnu": [2000.0, 1500.0],
        })

        transformed = compute_nutrient_densities(df)

        self.assertIn("fibra_por_1000kcal", transformed.columns)
        self.assertIn("azucar_por_1000kcal", transformed.columns)
        self.assertIn("sodio_por_1000kcal", transformed.columns)

        self.assertAlmostEqual(transformed["fibra_por_1000kcal"].iloc[0], 10.0)
        self.assertAlmostEqual(transformed["azucar_por_1000kcal"].iloc[0], 20.0)
        self.assertAlmostEqual(transformed["sodio_por_1000kcal"].iloc[0], 1000.0)

        self.assertAlmostEqual(transformed["fibra_por_1000kcal"].iloc[1], 15.0)
        self.assertAlmostEqual(transformed["azucar_por_1000kcal"].iloc[1], 25.0)
        self.assertAlmostEqual(transformed["sodio_por_1000kcal"].iloc[1], 1500.0)


if __name__ == "__main__":
    unittest.main()
