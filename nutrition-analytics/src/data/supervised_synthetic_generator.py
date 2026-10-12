"""
Generador de Dataset Sintético Supervisado para Clasificación de Riesgo Nutricional (Fase 2E).
Protocolo: protocolo_sintetico_riesgo_v1.0
Origen: sintetico_controlado

Genera 200 pacientes sintéticos x 3 ventanas temporales = 600 observaciones.
Incluye variabilidad intra-paciente, correlación temporal, ruido y casos frontera.
"""

import os
import hashlib
import numpy as np
import pandas as pd


class SupervisedSyntheticGenerator:
    """Generador controlado de datasets supervisados con etiquetas de riesgo multinivel (bajo, moderado, alto)."""

    PROTOCOL_VERSION = "protocolo_sintetico_riesgo_v1.0"
    ORIGEN_DATASET = "sintetico_controlado"

    def __init__(self, seed: int = 42):
        self.seed = seed
        self.rng = np.random.default_rng(seed)

    def generate_supervised_dataset(
        self,
        n_patients: int = 200,
        windows_per_patient: int = 3,
        output_path: str = None
    ) -> pd.DataFrame:
        """
        Genera un dataset sintético supervisado con n_patients pacientes y windows_per_patient ventanas por paciente.
        Total de filas = n_patients * windows_per_patient.
        """
        rows = []
        patient_profiles = self._generate_base_patient_profiles(n_patients)

        for p_idx, profile in enumerate(patient_profiles):
            patient_group_id = f"SYNTH_PAC_{p_idx + 1:03d}"

            for w_idx in range(windows_per_patient):
                obs = self._generate_patient_window_observation(
                    profile, patient_group_id, w_idx, windows_per_patient
                )
                rows.append(obs)

        df = pd.DataFrame(rows)

        # Calcular densidades derivadas por 1000 kcal
        df["fibra_por_1000kcal"] = np.round(
            (df["promedio_fibra_g_fnu"] / (df["promedio_calorias_fnu"] / 1000.0)), 2
        )
        df["azucar_por_1000kcal"] = np.round(
            (df["promedio_azucar_g_fnu"] / (df["promedio_calorias_fnu"] / 1000.0)), 2
        )
        df["sodio_por_1000kcal"] = np.round(
            (df["promedio_sodio_mg_fnu"] / (df["promedio_calorias_fnu"] / 1000.0)), 2
        )

        # Asignar etiquetas mediante función probabilística multivariable con ruido
        df["nivel_riesgo_erp"] = self._assign_probabilistic_risk_labels(df)

        # Asignar subtipos descriptivos secundarios (no target principal)
        df["tipo_riesgo_erp"] = self._assign_descriptive_risk_subtypes(df)

        if output_path:
            os.makedirs(os.path.dirname(output_path), exist_ok=True)
            df.to_csv(output_path, index=False)
            print(f"[SupervisedGenerator] Dataset supervisado guardado en: {output_path} ({len(df)} filas)")

        return df

    def _generate_base_patient_profiles(self, n_patients: int) -> list:
        """Crea perfiles basales heterogéneos de pacientes sintéticos."""
        profiles = []
        for i in range(n_patients):
            # Arquetipo de paciente latente:
            # 0: Tendencia saludable/equilibrada (~35%)
            # 1: Desbalances moderados en fibra/horarios (~35%)
            # 2: Desbalances severos azúcar/sodio/calorías/IMC (~30%)
            archetype = self.rng.choice([0, 1, 2], p=[0.35, 0.35, 0.30])

            edad = int(self.rng.integers(18, 70))
            sexo = self.rng.choice(["masculino", "femenino"])

            if archetype == 0:
                base_cal = self.rng.normal(2000, 200)
                base_imc = self.rng.normal(23.5, 2.0)
                base_fibra_rate = self.rng.uniform(14.0, 22.0)  # g/1000kcal
                base_azucar_rate = self.rng.uniform(15.0, 28.0) # g/1000kcal
                base_sodio_rate = self.rng.uniform(800.0, 1200.0) # mg/1000kcal
                base_var_horaria = self.rng.uniform(20.0, 45.0)
                base_desayuno = self.rng.uniform(0.75, 1.0)
                pct_pro = self.rng.uniform(16.0, 22.0)
                pct_gra = self.rng.uniform(22.0, 30.0)
            elif archetype == 1:
                base_cal = self.rng.normal(2200, 300)
                base_imc = self.rng.normal(27.0, 3.0)
                base_fibra_rate = self.rng.uniform(9.0, 14.0)
                base_azucar_rate = self.rng.uniform(28.0, 48.0)
                base_sodio_rate = self.rng.uniform(1100.0, 1600.0)
                base_var_horaria = self.rng.uniform(45.0, 80.0)
                base_desayuno = self.rng.uniform(0.40, 0.75)
                pct_pro = self.rng.uniform(12.0, 17.0)
                pct_gra = self.rng.uniform(28.0, 36.0)
            else:
                base_cal = self.rng.choice([self.rng.normal(2700, 350), self.rng.normal(1350, 180)])
                base_imc = self.rng.choice([self.rng.normal(34.0, 4.0), self.rng.normal(17.5, 1.2)])
                base_fibra_rate = self.rng.uniform(5.0, 9.5)
                base_azucar_rate = self.rng.uniform(45.0, 75.0)
                base_sodio_rate = self.rng.uniform(1500.0, 2300.0)
                base_var_horaria = self.rng.uniform(75.0, 140.0)
                base_desayuno = self.rng.uniform(0.10, 0.50)
                pct_pro = self.rng.uniform(10.0, 15.0)
                pct_gra = self.rng.uniform(34.0, 45.0)

            pct_car = max(10.0, 100.0 - pct_pro - pct_gra)

            profiles.append({
                "archetype": archetype,
                "edad": edad,
                "sexo": sexo,
                "base_cal": max(1100, float(base_cal)),
                "base_imc": np.clip(float(base_imc), 16.0, 45.0),
                "base_fibra_rate": base_fibra_rate,
                "base_azucar_rate": base_azucar_rate,
                "base_sodio_rate": base_sodio_rate,
                "base_var_horaria": max(15.0, float(base_var_horaria)),
                "base_desayuno": float(base_desayuno),
                "pct_pro": pct_pro,
                "pct_car": pct_car,
                "pct_gra": pct_gra,
            })
        return profiles

    def _generate_patient_window_observation(
        self, profile: dict, group_id: str, w_idx: int, total_w: int
    ) -> dict:
        """Genera una ventana temporal específica de 7 días con ruido intra-sujeto."""
        # Variabilidad intra-paciente por ventana (correlacionada temporalmente)
        noise_cal = self.rng.normal(0, 100)
        cal = max(1000.0, profile["base_cal"] + noise_cal)

        # Variaciones en macros (+/- 2%)
        d_pro = self.rng.normal(0, 1.2)
        d_gra = self.rng.normal(0, 1.5)
        pct_pro = np.clip(profile["pct_pro"] + d_pro, 8.0, 35.0)
        pct_gra = np.clip(profile["pct_gra"] + d_gra, 15.0, 50.0)
        pct_car = max(10.0, 100.0 - pct_pro - pct_gra)

        # Gramos absolutos
        pro_g = (cal * (pct_pro / 100.0)) / 4.0
        car_g = (cal * (pct_car / 100.0)) / 4.0
        gra_g = (cal * (pct_gra / 100.0)) / 9.0

        # Fibra, azúcar, sodio derivados de las tasas basales con ruido
        fibra_g = max(4.0, (cal / 1000.0) * profile["base_fibra_rate"] + self.rng.normal(0, 1.5))
        azucar_g = max(10.0, (cal / 1000.0) * profile["base_azucar_rate"] + self.rng.normal(0, 4.0))
        sodio_mg = max(500.0, (cal / 1000.0) * profile["base_sodio_rate"] + self.rng.normal(0, 80.0))

        # Hábitos
        desayuno_freq = np.clip(profile["base_desayuno"] + self.rng.normal(0, 0.08), 0.0, 1.0)
        cena_freq = np.clip(self.rng.uniform(0.70, 1.0) + self.rng.normal(0, 0.05), 0.0, 1.0)
        snack_freq = np.clip(self.rng.uniform(0.20, 0.80), 0.0, 1.0)
        comidas_dia = np.clip(2.0 + desayuno_freq + (1.0 if cena_freq > 0.5 else 0.5) + snack_freq * 0.8, 1.5, 5.5)
        var_horaria = max(15.0, profile["base_var_horaria"] + self.rng.normal(0, 8.0))

        # Antropometría
        imc = np.clip(profile["base_imc"] + (w_idx * self.rng.normal(0, 0.15)), 15.5, 46.0)
        cambio_peso = round(float(self.rng.normal(0, 0.6)), 2)

        # Adherencia sintética (entre 4/7 y 7/7 días)
        dias_reg = int(self.rng.choice([4, 5, 6, 7], p=[0.15, 0.30, 0.35, 0.20]))
        adherencia = round(dias_reg / 7.0, 4)

        start_day = 1 + w_idx * 7
        end_day = start_day + 6

        return {
            "patient_group_id": group_id,
            "snapshot_synthetic_id": f"{group_id}_W{w_idx + 1}",
            "ventana_numero": w_idx + 1,
            "fec_inicio": f"2026-05-{start_day:02d}",
            "fec_fin": f"2026-05-{end_day:02d}",
            "ventana_dias_fnu": 7,
            "dias_registrados_fnu": dias_reg,
            "adherencia_registro_fnu": adherencia,
            "promedio_calorias_fnu": round(cal, 1),
            "std_calorias_fnu": round(float(self.rng.uniform(60.0, 220.0)), 1),
            "promedio_proteina_g_fnu": round(pro_g, 1),
            "pct_proteina_fnu": round(pct_pro, 1),
            "promedio_carbohidratos_g_fnu": round(car_g, 1),
            "pct_carbohidratos_fnu": round(pct_car, 1),
            "promedio_grasas_g_fnu": round(gra_g, 1),
            "pct_grasa_fnu": round(pct_gra, 1),
            "promedio_fibra_g_fnu": round(fibra_g, 1),
            "promedio_azucar_g_fnu": round(azucar_g, 1),
            "promedio_sodio_mg_fnu": round(sodio_mg, 1),
            "frecuencia_desayuno_fnu": round(desayuno_freq, 4),
            "frecuencia_cena_fnu": round(cena_freq, 4),
            "frecuencia_snack_fnu": round(snack_freq, 4),
            "promedio_comidas_dia_fnu": round(comidas_dia, 1),
            "variabilidad_horaria_min_fnu": round(var_horaria, 1),
            "cobertura_composicion_pct_fnu": round(float(self.rng.uniform(90.0, 100.0)), 1),
            "imc_fnu": round(imc, 1),
            "cambio_peso_fnu": cambio_peso,
            "edad_fnu": profile["edad"],
            "sexo_fnu": profile["sexo"],
            "origen_datos": self.ORIGEN_DATASET,
            "version_features": "v1.0",
            "version_protocolo": self.PROTOCOL_VERSION,
        }

    def _assign_probabilistic_risk_labels(self, df: pd.DataFrame) -> pd.Series:
        """
        Función probabilística multivariable con ruido para asignar bajo, moderado o alto.
        No usa heurísticas rígidas de regla única; combina penalizaciones continuas + ruido estocástico.
        """
        labels = []

        for _, row in df.iterrows():
            score = 0.0

            # 1. Densidad de Fibra (Bajo nivel aumenta riesgo)
            fibra = row["fibra_por_1000kcal"]
            if fibra < 8.0:
                score += 2.2
            elif fibra < 12.5:
                score += 1.0

            # 2. Densidad de Azúcar (Alto nivel aumenta riesgo)
            azucar = row["azucar_por_1000kcal"]
            if azucar > 48.0:
                score += 2.2
            elif azucar > 32.0:
                score += 1.1

            # 3. Densidad de Sodio (Alto nivel aumenta riesgo)
            sodio = row["sodio_por_1000kcal"]
            if sodio > 1600.0:
                score += 1.8
            elif sodio > 1200.0:
                score += 0.8

            # 4. Grasa (% VCT)
            grasa = row["pct_grasa_fnu"]
            if grasa > 38.0:
                score += 1.5
            elif grasa > 32.0:
                score += 0.7

            # 5. Desviación Calórica
            cal = row["promedio_calorias_fnu"]
            if cal > 2700 or cal < 1300:
                score += 1.6
            elif cal > 2400 or cal < 1500:
                score += 0.8

            # 6. Variabilidad Horaria y Desayuno
            var_min = row["variabilidad_horaria_min_fnu"]
            if var_min > 80.0:
                score += 1.2
            elif var_min > 50.0:
                score += 0.6

            desayuno = row["frecuencia_desayuno_fnu"]
            if desayuno < 0.35:
                score += 1.0

            # 7. IMC
            imc = row["imc_fnu"]
            if imc >= 33.0 or imc < 18.0:
                score += 1.8
            elif imc >= 28.0:
                score += 0.9

            # Ruido estocástico gaussiano para generar casos frontera (~10% solapamiento)
            noise = self.rng.normal(0, 0.75)
            final_score = score + noise

            # Mapeo a clases
            if final_score < 3.2:
                labels.append("bajo")
            elif final_score < 6.8:
                labels.append("moderado")
            else:
                labels.append("alto")

        return pd.Series(labels, index=df.index)

    def _assign_descriptive_risk_subtypes(self, df: pd.DataFrame) -> pd.Series:
        """Asigna subtipos descriptivos secundarios según características predominantes."""
        subtypes = []
        for _, row in df.iterrows():
            nivel = row["nivel_riesgo_erp"]
            if nivel == "bajo":
                subtypes.append("balanceado_bajo_riesgo")
            elif nivel == "alto":
                if row["azucar_por_1000kcal"] > 45.0 or row["promedio_calorias_fnu"] > 2500:
                    subtypes.append("exceso_calorico_azucar")
                elif row["fibra_por_1000kcal"] < 8.0:
                    subtypes.append("deficit_fibra_severo")
                elif row["variabilidad_horaria_min_fnu"] > 80.0:
                    subtypes.append("irregularidad_horaria_severa")
                else:
                    subtypes.append("perfil_cardiometabolico_riesgo")
            else:  # moderado
                if row["fibra_por_1000kcal"] < 12.0:
                    subtypes.append("deficit_fibra_moderado")
                elif row["variabilidad_horaria_min_fnu"] > 50.0:
                    subtypes.append("irregularidad_horaria")
                else:
                    subtypes.append("exceso_grasa_moderado")
        return pd.Series(subtypes, index=df.index)
