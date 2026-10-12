#!/usr/bin/env python
"""
Script CLI para inferencia de clustering nutricional K-Means.
Acepta cadena JSON vía argumento o stdin, o ruta de archivo, y retorna JSON con la predicción.
"""

import argparse
import json
from pathlib import Path
import sys

current_dir = Path(__file__).resolve().parent
project_root = current_dir.parent
sys.path.insert(0, str(project_root))

from src.models.kmeans_predictor import KMeansPredictor


def main():
    parser = argparse.ArgumentParser(description="Ejecutar inferencia K-Means sobre un vector de features nutricionales.")
    parser.add_argument("--input", type=str, default=None, help="JSON con los valores del vector o ruta a archivo JSON")
    parser.add_argument("--scaler", type=str, default=str(project_root / "artifacts" / "scaler_v1.joblib"), help="Ruta al scaler serializado")
    parser.add_argument("--model", type=str, default=str(project_root / "artifacts" / "kmeans_v1_k4_candidate.joblib"), help="Ruta al modelo K-Means serializado")
    parser.add_argument("--stdin", action="store_true", help="Leer JSON de entrada desde stdin")

    args = parser.parse_args()

    input_data = None
    if args.stdin:
        input_data = json.load(sys.stdin)
    elif args.input:
        in_path = Path(args.input)
        if in_path.exists():
            with open(in_path, "r", encoding="utf-8") as f:
                input_data = json.load(f)
        else:
            input_data = json.loads(args.input)
    else:
        # Vector de prueba por defecto para demostración
        input_data = {
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

    predictor = KMeansPredictor(scaler_path=args.scaler, model_path=args.model)
    result = predictor.predict(input_data)

    print(json.dumps(result, indent=2, ensure_ascii=True))


if __name__ == "__main__":
    main()
