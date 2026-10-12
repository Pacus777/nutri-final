"""
Generador de Datos Sintéticos Controlados para Validación de Clustering Nutricional.
Genera perfiles de conducta alimentaria realistas con variabilidad clínica y solapamiento natural.
"""

from pathlib import Path
from typing import Optional
import numpy as np
import pandas as pd


def generate_synthetic_nutritional_dataset(
    n_profiles: int = 300,
    n_ineligible: int = 30,
    random_state: int = 42,
    output_path: Optional[str] = None,
) -> pd.DataFrame:
    """
    Genera un dataset sintético controlado de snapshots de variables de pacientes.
    Explícitamente etiquetado como 'sintetico_controlado'.
    
    Incluye 4 arquetipos clínicamente plausibles con perturbación gaussiana y solapamiento:
    1. Patron Balanceado / Regular (moderado en calorías, balanceado en macros, alta fibra, regular)
    2. Patron Denso en Carbohidratos y Azúcar / Snack nocturno (bajo en proteína, alto azúcar, alta variabilidad horaria)
    3. Patron Hiperproteico / Deportivo (alta proteína, moderados carbos, baja variabilidad, alta frecuencia)
    4. Patron Hipocalórico / Irregular (salta desayuno, alta variabilidad horaria, sodio relativo alto)
    """
    rng = np.random.RandomState(random_state)
    
    archetypes = [
        {
            "name": "Archetype_A",
            "ratio": 0.28,
            "cal_mean": 2050, "cal_std": 180, "std_cal_mean": 140, "std_cal_std": 35,
            "pro_pct": (19.0, 2.5), "car_pct": (51.0, 3.5), "gra_pct": (30.0, 2.5),
            "fib_g": (26.0, 4.0), "azu_g": (38.0, 6.0), "sod_mg": (2200, 250),
            "freq_des": (0.92, 0.08), "freq_cen": (0.88, 0.09), "freq_snk": (0.45, 0.12),
            "comidas_dia": (3.5, 0.35), "var_hor": (28.0, 7.0),
            "edad": (34, 10), "imc": (23.8, 2.5), "sexo_p_m": 0.48
        },
        {
            "name": "Archetype_B",
            "ratio": 0.27,
            "cal_mean": 2480, "cal_std": 240, "std_cal_mean": 290, "std_cal_std": 55,
            "pro_pct": (12.5, 2.0), "car_pct": (59.0, 3.5), "gra_pct": (28.5, 3.0),
            "fib_g": (12.0, 2.5), "azu_g": (115.0, 18.0), "sod_mg": (3900, 450),
            "freq_des": (0.42, 0.15), "freq_cen": (0.95, 0.06), "freq_snk": (0.88, 0.10),
            "comidas_dia": (4.4, 0.50), "var_hor": (85.0, 16.0),
            "edad": (29, 8), "imc": (28.6, 3.8), "sexo_p_m": 0.52
        },
        {
            "name": "Archetype_C",
            "ratio": 0.25,
            "cal_mean": 2350, "cal_std": 210, "std_cal_mean": 160, "std_cal_std": 40,
            "pro_pct": (31.0, 3.0), "car_pct": (43.0, 3.5), "gra_pct": (26.0, 2.5),
            "fib_g": (28.0, 4.5), "azu_g": (32.0, 7.0), "sod_mg": (2600, 300),
            "freq_des": (0.96, 0.05), "freq_cen": (0.94, 0.06), "freq_snk": (0.75, 0.12),
            "comidas_dia": (4.8, 0.40), "var_hor": (22.0, 6.0),
            "edad": (27, 6), "imc": (24.2, 2.0), "sexo_p_m": 0.65
        },
        {
            "name": "Archetype_D",
            "ratio": 0.20,
            "cal_mean": 1420, "cal_std": 190, "std_cal_mean": 320, "std_cal_std": 60,
            "pro_pct": (16.0, 3.0), "car_pct": (49.0, 4.5), "gra_pct": (35.0, 3.5),
            "fib_g": (11.0, 2.5), "azu_g": (36.0, 8.0), "sod_mg": (2400, 350),
            "freq_des": (0.22, 0.12), "freq_cen": (0.78, 0.14), "freq_snk": (0.50, 0.18),
            "comidas_dia": (2.1, 0.40), "var_hor": (102.0, 22.0),
            "edad": (38, 11), "imc": (26.4, 3.5), "sexo_p_m": 0.35
        },
    ]

    records = []
    current_patient_id = 1

    # Distribuir muestras entre los arquetipos
    counts = [int(n_profiles * arch["ratio"]) for arch in archetypes]
    counts[-1] = n_profiles - sum(counts[:-1])  # Asegurar suma exacta = n_profiles

    for arch, count in zip(archetypes, counts):
        for _ in range(count):
            # Ingesta calórica y variabilidad
            cal = float(np.clip(rng.normal(arch["cal_mean"], arch["cal_std"]), 800, 4200))
            std_cal = float(np.clip(rng.normal(arch["std_cal_mean"], arch["std_cal_std"]), 20, 700))

            # Macronutrientes (porcentajes normalizados a suma ~100)
            raw_pro = rng.normal(arch["pro_pct"][0], arch["pro_pct"][1])
            raw_car = rng.normal(arch["car_pct"][0], arch["car_pct"][1])
            raw_gra = rng.normal(arch["gra_pct"][0], arch["gra_pct"][1])
            sum_macros = raw_pro + raw_car + raw_gra
            pct_pro = float(np.round((raw_pro / sum_macros) * 100, 2))
            pct_car = float(np.round((raw_car / sum_macros) * 100, 2))
            pct_gra = float(np.round(100.0 - pct_pro - pct_car, 2))

            # Gramos
            pro_g = float(np.round((cal * (pct_pro / 100)) / 4.0, 2))
            car_g = float(np.round((cal * (pct_car / 100)) / 4.0, 2))
            gra_g = float(np.round((cal * (pct_gra / 100)) / 9.0, 2))

            # Micronutrientes
            fib_g = float(np.clip(np.round(rng.normal(arch["fib_g"][0], arch["fib_g"][1]), 2), 2.0, 60.0))
            azu_g = float(np.clip(np.round(rng.normal(arch["azu_g"][0], arch["azu_g"][1]), 2), 5.0, 200.0))
            sod_mg = float(np.clip(np.round(rng.normal(arch["sod_mg"][0], arch["sod_mg"][1]), 2), 500.0, 7000.0))

            # Frecuencias
            f_des = float(np.clip(np.round(rng.normal(arch["freq_des"][0], arch["freq_des"][1]), 4), 0.0, 1.0))
            f_cen = float(np.clip(np.round(rng.normal(arch["freq_cen"][0], arch["freq_cen"][1]), 4), 0.0, 1.0))
            f_snk = float(np.clip(np.round(rng.normal(arch["freq_snk"][0], arch["freq_snk"][1]), 4), 0.0, 1.0))
            com_dia = float(np.clip(np.round(rng.normal(arch["comidas_dia"][0], arch["comidas_dia"][1]), 2), 1.0, 8.0))
            var_hor = float(np.clip(np.round(rng.normal(arch["var_hor"][0], arch["var_hor"][1]), 2), 5.0, 180.0))

            # Metadatos y demografía
            edad = int(np.clip(np.round(rng.normal(arch["edad"][0], arch["edad"][1])), 18, 75))
            imc = float(np.clip(np.round(rng.normal(arch["imc"][0], arch["imc"][1]), 2), 16.5, 45.0))
            sexo = "masculino" if rng.rand() < arch["sexo_p_m"] else "femenino"
            cambio_peso = float(np.round(rng.normal(0.0, 0.8), 2))

            # Adherencia: los casos elegibles tienen 5, 6 o 7 días registrados de 7
            dias_reg = int(rng.choice([5, 6, 7], p=[0.20, 0.35, 0.45]))
            adherencia = float(np.round(dias_reg / 7.0, 4))
            ratio_val = float(np.round(rng.uniform(0.60, 1.00), 4))

            records.append({
                "paciente_anonimo_id": f"SYNTH_PAC_{current_patient_id:04d}",
                "snapshot_id": f"SNAP_{current_patient_id:04d}",
                "version_features": "v1.0",
                "fec_inicio": "2026-09-01",
                "fec_fin": "2026-09-07",
                "ventana_dias": 7,
                "dias_registrados": dias_reg,
                "adherencia_registro_fnu": adherencia,
                "ratio_validacion_fnu": ratio_val,
                "promedio_calorias_fnu": np.round(cal, 2),
                "std_calorias_fnu": np.round(std_cal, 2),
                "promedio_proteina_g_fnu": pro_g,
                "promedio_carbohidratos_g_fnu": car_g,
                "promedio_grasas_g_fnu": gra_g,
                "promedio_fibra_g_fnu": fib_g,
                "promedio_azucar_g_fnu": azu_g,
                "promedio_sodio_mg_fnu": sod_mg,
                "pct_proteina_fnu": pct_pro,
                "pct_carbohidratos_fnu": pct_car,
                "pct_grasa_fnu": pct_gra,
                "frecuencia_desayuno_fnu": f_des,
                "frecuencia_cena_fnu": f_cen,
                "frecuencia_snack_fnu": f_snk,
                "promedio_comidas_dia_fnu": com_dia,
                "variabilidad_horaria_min_fnu": var_hor,
                "cobertura_composicion_pct_fnu": float(np.round(rng.uniform(92.0, 100.0), 2)),
                "dias_calorias_altas_fnu": None,
                "dias_proteina_baja_fnu": None,
                "dias_fibra_baja_fnu": None,
                "dias_azucar_alta_fnu": None,
                "edad_fnu": edad,
                "sexo_fnu": sexo,
                "imc_fnu": imc,
                "cambio_peso_fnu": cambio_peso,
                "origen_dataset": "sintetico_controlado",
            })
            current_patient_id += 1

    # Añadir snapshots no elegibles (ej. baja adherencia o ventana != 7) para pruebas de filtro
    for i in range(n_ineligible):
        dias_reg = int(rng.choice([1, 2, 3, 4]))
        adh = float(np.round(dias_reg / 7.0, 4))
        records.append({
            "paciente_anonimo_id": f"SYNTH_INEL_{i+1:04d}",
            "snapshot_id": f"SNAP_INEL_{i+1:04d}",
            "version_features": "v1.0",
            "fec_inicio": "2026-09-01",
            "fec_fin": "2026-09-07",
            "ventana_dias": 7 if i % 2 == 0 else 14,
            "dias_registrados": dias_reg,
            "adherencia_registro_fnu": adh,
            "ratio_validacion_fnu": 0.50,
            "promedio_calorias_fnu": 1900.0,
            "std_calorias_fnu": 200.0,
            "promedio_proteina_g_fnu": 70.0,
            "promedio_carbohidratos_g_fnu": 230.0,
            "promedio_grasas_g_fnu": 60.0,
            "promedio_fibra_g_fnu": 18.0,
            "promedio_azucar_g_fnu": 50.0,
            "promedio_sodio_mg_fnu": 2500.0,
            "pct_proteina_fnu": 16.0,
            "pct_carbohidratos_fnu": 52.0,
            "pct_grasa_fnu": 32.0,
            "frecuencia_desayuno_fnu": 0.70,
            "frecuencia_cena_fnu": 0.80,
            "frecuencia_snack_fnu": 0.40,
            "promedio_comidas_dia_fnu": 3.0,
            "variabilidad_horaria_min_fnu": 45.0,
            "cobertura_composicion_pct_fnu": 95.0,
            "dias_calorias_altas_fnu": None,
            "dias_proteina_baja_fnu": None,
            "dias_fibra_baja_fnu": None,
            "dias_azucar_alta_fnu": None,
            "edad_fnu": 30,
            "sexo_fnu": "masculino",
            "imc_fnu": 25.0,
            "cambio_peso_fnu": 0.0,
            "origen_dataset": "sintetico_controlado",
        })

    df = pd.DataFrame(records)

    if output_path:
        out = Path(output_path)
        out.parent.mkdir(parents=True, exist_ok=True)
        df.to_csv(out, index=False)
        print(f"[SyntheticGenerator] Dataset sintético generado con éxito: {len(df)} filas ({n_profiles} elegibles + {n_ineligible} no elegibles) en {out}")

    return df
