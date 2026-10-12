"""
Tests for snapshot eligibility criteria (window=7, adherence>=0.7143, missing features).
"""

import unittest
import numpy as np
import pandas as pd
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.data.loader import CLUSTERING_FEATURES_V1
from src.data.filter import filter_eligible_snapshots


class TestEligibilityFilter(unittest.TestCase):
    def setUp(self):
        # Base template valid row
        self.base_row = {
            "paciente_anonimo_id": "PAC_001",
            "snapshot_id": "SNAP_001",
            "version_features": "v1.0",
            "ventana_dias": 7,
            "dias_registrados": 6,
            "adherencia_registro_fnu": 0.8571,
            "ratio_validacion_fnu": 0.80,
            "promedio_calorias_fnu": 2000.0,
            "std_calorias_fnu": 150.0,
            "pct_proteina_fnu": 20.0,
            "pct_carbohidratos_fnu": 50.0,
            "pct_grasa_fnu": 30.0,
            "fibra_por_1000kcal": 12.0,
            "azucar_por_1000kcal": 25.0,
            "sodio_por_1000kcal": 1200.0,
            "frecuencia_desayuno_fnu": 0.90,
            "frecuencia_cena_fnu": 0.85,
            "frecuencia_snack_fnu": 0.50,
            "promedio_comidas_dia_fnu": 3.5,
            "variabilidad_horaria_min_fnu": 30.0,
        }

    def test_filter_window_criteria(self):
        r1 = self.base_row.copy()
        r2 = self.base_row.copy()
        r2["ventana_dias"] = 14  # Ineligible window

        df = pd.DataFrame([r1, r2])
        filtered, stats = filter_eligible_snapshots(df, target_window=7, verbose=False)

        self.assertEqual(len(filtered), 1)
        self.assertEqual(stats["descartados_ventana"], 1)
        self.assertEqual(filtered.iloc[0]["ventana_dias"], 7)

    def test_filter_adherence_criteria(self):
        # Exactly 5/7 days is 0.7142857 -> 0.7143
        r_eligible_5_7 = self.base_row.copy()
        r_eligible_5_7["dias_registrados"] = 5
        r_eligible_5_7["adherencia_registro_fnu"] = 0.7143

        r_ineligible_4_7 = self.base_row.copy()
        r_ineligible_4_7["dias_registrados"] = 4
        r_ineligible_4_7["adherencia_registro_fnu"] = 0.5714

        df = pd.DataFrame([r_eligible_5_7, r_ineligible_4_7])
        filtered, stats = filter_eligible_snapshots(df, min_adherence=0.7143, verbose=False)

        self.assertEqual(len(filtered), 1)
        self.assertEqual(stats["descartados_adherencia"], 1)
        self.assertAlmostEqual(filtered.iloc[0]["adherencia_registro_fnu"], 0.7143)

    def test_filter_missing_feature_values(self):
        r_valid = self.base_row.copy()
        r_null = self.base_row.copy()
        r_null["variabilidad_horaria_min_fnu"] = np.nan

        df = pd.DataFrame([r_valid, r_null])
        filtered, stats = filter_eligible_snapshots(df, verbose=False)

        self.assertEqual(len(filtered), 1)
        self.assertEqual(stats["descartados_valores_nulos"], 1)


if __name__ == "__main__":
    unittest.main()
