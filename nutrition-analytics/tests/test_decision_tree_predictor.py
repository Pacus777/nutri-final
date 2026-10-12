"""
Pruebas unitarias para el componente de inferencia DecisionTreeRiskPredictor (Fase 2F).
Prueban la carga de artefactos, el cálculo de probabilidades (suma ≈ 1), la coincidencia con la clase de mayor probabilidad,
la explicabilidad de la ruta de decisión y la presencia del descargo de responsabilidad sobre el carácter experimental.
"""

import unittest
from pathlib import Path
import sys
import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from src.models.decision_tree_predictor import DecisionTreeRiskPredictor


class TestDecisionTreePredictor(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        project_root = Path(__file__).resolve().parent.parent
        cls.model_path = project_root / "artifacts" / "decision_tree_v1_candidate.joblib"
        cls.metadata_path = project_root / "artifacts" / "decision_tree_v1_metadata.json"
        cls.predictor = DecisionTreeRiskPredictor(model_path=cls.model_path, metadata_path=cls.metadata_path)

        cls.sample_low_risk = {
            "promedio_calorias_fnu": 2000.0,
            "pct_proteina_fnu": 18.0,
            "pct_carbohidratos_fnu": 52.0,
            "pct_grasa_fnu": 30.0,
            "promedio_fibra_g_fnu": 28.0,
            "promedio_azucar_g_fnu": 30.0,    # 15 g/1000kcal <= 44.1
            "promedio_sodio_mg_fnu": 1400.0,
            "frecuencia_desayuno_fnu": 0.90,
            "frecuencia_cena_fnu": 0.90,
            "promedio_comidas_dia_fnu": 3.5,
            "variabilidad_horaria_min_fnu": 30.0,  # <= 52.1
            "imc_fnu": 23.5,
        }

        cls.sample_high_risk = {
            "promedio_calorias_fnu": 1800.0,
            "pct_proteina_fnu": 10.0,
            "pct_carbohidratos_fnu": 65.0,
            "pct_grasa_fnu": 25.0,
            "promedio_fibra_g_fnu": 10.0,
            "promedio_azucar_g_fnu": 100.0,   # 55.5 g/1000kcal > 44.1
            "promedio_sodio_mg_fnu": 3200.0,  # 1777 mg/1000kcal > 1465.71
            "frecuencia_desayuno_fnu": 0.30,
            "frecuencia_cena_fnu": 0.40,
            "promedio_comidas_dia_fnu": 2.0,
            "variabilidad_horaria_min_fnu": 75.0,
            "imc_fnu": 31.0,
        }

    def test_predictor_loads_model_and_metadata(self):
        self.assertIsNotNone(self.predictor.model)
        self.assertIn("bajo", self.predictor.class_names)
        self.assertIn("moderado", self.predictor.class_names)
        self.assertIn("alto", self.predictor.class_names)
        self.assertEqual(len(self.predictor.feature_names), 12)

    def test_prediction_reproducibility(self):
        res1 = self.predictor.predict(self.sample_low_risk)
        res2 = self.predictor.predict(self.sample_low_risk)

        self.assertEqual(res1["clase_predicha"], res2["clase_predicha"])
        self.assertEqual(res1["probabilidad_clase"], res2["probabilidad_clase"])
        self.assertEqual(res1["probabilidades"], res2["probabilidades"])
        self.assertEqual(res1["camino_decision"], res2["camino_decision"])

    def test_probabilities_sum_to_approximately_one(self):
        for sample in [self.sample_low_risk, self.sample_high_risk]:
            res = self.predictor.predict(sample)
            probs = res["probabilidades"]
            total_prob = sum(probs.values())
            self.assertAlmostEqual(total_prob, 1.0, places=4)
            for k in ["bajo", "moderado", "alto"]:
                self.assertGreaterEqual(probs[k], 0.0)
                self.assertLessEqual(probs[k], 1.0)

    def test_selected_class_corresponds_to_max_probability(self):
        for sample in [self.sample_low_risk, self.sample_high_risk]:
            res = self.predictor.predict(sample)
            clase = res["clase_predicha"]
            probs = res["probabilidades"]
            max_class = max(probs, key=probs.get)
            self.assertEqual(clase, max_class)
            self.assertEqual(res["probabilidad_clase"], probs[max_class])

    def test_decision_path_and_explainability_structure(self):
        res = self.predictor.predict(self.sample_high_risk)
        self.assertIn("camino_decision", res)
        self.assertIn("variables_involucradas", res)
        self.assertGreater(len(res["camino_decision"]), 0)

        # Alto riesgo sample has azucar > 44.1 and sodio > 1465.71
        vars_inv = res["variables_involucradas"]
        self.assertIn("azucar_por_1000kcal", vars_inv)
        self.assertEqual(res["clase_predicha"], "alto")

    def test_experimental_disclaimer_and_origin_present(self):
        res = self.predictor.predict(self.sample_low_risk)
        self.assertEqual(res["origen_dataset"], "sintetico_controlado")
        self.assertIn("orientativa y no constituye un diagnóstico", res["advertencia"])
        self.assertEqual(res["version_features"], "v1.0")


if __name__ == "__main__":
    unittest.main()
