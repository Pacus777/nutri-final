"""
Script CLI para inferencia del Decision Tree de Riesgo Nutricional (Fase 2F).
Recibe vector JSON por STDIN o archivo y devuelve JSON con resultado y explicabilidad.
"""

import sys
import os
import json
import argparse

# Añadir raíz de nutrition-analytics al path
BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from src.models.decision_tree_predictor import DecisionTreeRiskPredictor


def main():
    parser = argparse.ArgumentParser(description="Inferencia Decision Tree de Riesgo Nutricional")
    parser.add_argument("--model", type=str, default=os.path.join(BASE_DIR, "artifacts", "decision_tree_v1_candidate.joblib"))
    parser.add_argument("--metadata", type=str, default=os.path.join(BASE_DIR, "artifacts", "decision_tree_v1_metadata.json"))
    parser.add_argument("--stdin", action="store_true", help="Leer vector JSON desde STDIN")
    parser.add_argument("--json", type=str, help="Vector JSON como string")
    parser.add_argument("--file", type=str, help="Ruta a archivo JSON con el vector")

    args = parser.parse_args()

    if args.file and os.path.exists(args.file):
        with open(args.file, "r", encoding="utf-8") as f:
            vector = json.load(f)
    elif args.stdin:
        input_data = sys.stdin.read().strip()
        vector = json.loads(input_data)
    elif args.json:
        vector = json.loads(args.json)
    else:
        # Vector demo por defecto
        vector = {
            "promedio_calorias_fnu": 2100.0,
            "pct_proteina_fnu": 16.0,
            "pct_carbohidratos_fnu": 54.0,
            "pct_grasa_fnu": 30.0,
            "promedio_fibra_g_fnu": 20.0,
            "promedio_azucar_g_fnu": 35.0,
            "promedio_sodio_mg_fnu": 1400.0,
            "frecuencia_desayuno_fnu": 0.85,
            "frecuencia_cena_fnu": 0.90,
            "promedio_comidas_dia_fnu": 3.2,
            "variabilidad_horaria_min_fnu": 40.0,
            "imc_fnu": 24.5,
        }

    predictor = DecisionTreeRiskPredictor(model_path=args.model, metadata_path=args.metadata)
    result = predictor.predict(vector)

    print(json.dumps(result, ensure_ascii=True, indent=2))


if __name__ == "__main__":
    main()
