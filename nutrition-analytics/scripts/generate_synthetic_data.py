#!/usr/bin/env python
"""
Script CLI para generar el dataset sintético controlado para validación de K-Means.
"""

import argparse
from pathlib import Path
import sys

# Asegurar que el paquete src esté en el sys.path
current_dir = Path(__file__).resolve().parent
project_root = current_dir.parent
sys.path.insert(0, str(project_root))

from src.data.synthetic_generator import generate_synthetic_nutritional_dataset


def main():
    parser = argparse.ArgumentParser(description="Generar dataset sintético controlado de features nutricionales.")
    parser.add_argument("--profiles", type=int, default=300, help="Número de perfiles sintéticos elegibles (default: 300)")
    parser.add_argument("--ineligible", type=int, default=30, help="Número de snapshots no elegibles para pruebas de filtro (default: 30)")
    parser.add_argument("--seed", type=int, default=42, help="Random seed para reproducibilidad (default: 42)")
    parser.add_argument(
        "--output",
        type=str,
        default=str(project_root / "datasets" / "synthetic" / "synthetic_features_v1.0.csv"),
        help="Ruta de guardado del CSV sintético",
    )

    args = parser.parse_args()

    print(f"============================================================")
    print(f" GENERADOR DE DATASET SINTÉTICO CONTROLADO (v1.0)")
    print(f"============================================================")
    print(f" Perfiles elegibles : {args.profiles}")
    print(f" No elegibles       : {args.ineligible}")
    print(f" Random seed        : {args.seed}")
    print(f" Destino            : {args.output}")
    print(f"============================================================")

    df = generate_synthetic_nutritional_dataset(
        n_profiles=args.profiles,
        n_ineligible=args.ineligible,
        random_state=args.seed,
        output_path=args.output,
    )

    print(f"[OK] Generación exitosa. Total registros generados: {len(df)}")
    print(f"Primeras 3 filas:")
    print(df[["paciente_anonimo_id", "ventana_dias", "adherencia_registro_fnu", "promedio_calorias_fnu", "pct_proteina_fnu", "origen_dataset"]].head(3))


if __name__ == "__main__":
    main()
