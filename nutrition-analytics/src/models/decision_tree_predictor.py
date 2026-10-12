"""
Predictor de Inferencia para Árbol de Decisión de Riesgo Nutricional (Fase 2F).
Carga el modelo serializado .joblib y metadata, ejecuta predict_proba y extrae
el camino de decisión explicable nodo por nodo.
"""

import os
import json
import numpy as np
import joblib
from sklearn.tree import DecisionTreeClassifier


class DecisionTreeRiskPredictor:
    """Componente de inferencia para Decision Tree con explicabilidad por reglas del camino de decisión."""

    FEATURES = [
        "promedio_calorias_fnu",
        "pct_proteina_fnu",
        "pct_carbohidratos_fnu",
        "pct_grasa_fnu",
        "fibra_por_1000kcal",
        "azucar_por_1000kcal",
        "sodio_por_1000kcal",
        "frecuencia_desayuno_fnu",
        "frecuencia_cena_fnu",
        "promedio_comidas_dia_fnu",
        "variabilidad_horaria_min_fnu",
        "imc_fnu",
    ]

    CLASSES = ["bajo", "moderado", "alto"]
    ADVERTENCIA = (
        "Modelo experimental entrenado con datos sintéticos controlados. "
        "La estimación es orientativa y no constituye un diagnóstico ni sustituye la valoración profesional."
    )

    def __init__(self, model_path: str, metadata_path: str = None):
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"No se encontró el artefacto del modelo en: {model_path}")
        self.model: DecisionTreeClassifier = joblib.load(model_path)

        self.metadata = {}
        if metadata_path and os.path.exists(metadata_path):
            with open(metadata_path, "r", encoding="utf-8") as f:
                self.metadata = json.load(f)

    @property
    def class_names(self):
        return self.CLASSES

    @property
    def feature_names(self):
        return self.FEATURES

    def prepare_feature_vector(self, raw_vector: dict) -> np.ndarray:
        """Calcula densidades derivadas y formatea las 12 features en el orden exacto de entrenamiento."""
        cal = float(raw_vector.get("promedio_calorias_fnu", 2000.0))
        fibra_g = float(raw_vector.get("promedio_fibra_g_fnu", 15.0))
        azucar_g = float(raw_vector.get("promedio_azucar_g_fnu", 30.0))
        sodio_mg = float(raw_vector.get("promedio_sodio_mg_fnu", 1200.0))

        fibra_dens = raw_vector.get("fibra_por_1000kcal")
        if fibra_dens is None:
            fibra_dens = (fibra_g / (cal / 1000.0)) if cal > 0 else 0.0

        azucar_dens = raw_vector.get("azucar_por_1000kcal")
        if azucar_dens is None:
            azucar_dens = (azucar_g / (cal / 1000.0)) if cal > 0 else 0.0

        sodio_dens = raw_vector.get("sodio_por_1000kcal")
        if sodio_dens is None:
            sodio_dens = (sodio_mg / (cal / 1000.0)) if cal > 0 else 0.0

        feature_map = {
            "promedio_calorias_fnu": cal,
            "pct_proteina_fnu": float(raw_vector.get("pct_proteina_fnu", 15.0)),
            "pct_carbohidratos_fnu": float(raw_vector.get("pct_carbohidratos_fnu", 55.0)),
            "pct_grasa_fnu": float(raw_vector.get("pct_grasa_fnu", 30.0)),
            "fibra_por_1000kcal": round(float(fibra_dens), 2),
            "azucar_por_1000kcal": round(float(azucar_dens), 2),
            "sodio_por_1000kcal": round(float(sodio_dens), 2),
            "frecuencia_desayuno_fnu": float(raw_vector.get("frecuencia_desayuno_fnu", 0.8)),
            "frecuencia_cena_fnu": float(raw_vector.get("frecuencia_cena_fnu", 0.9)),
            "promedio_comidas_dia_fnu": float(raw_vector.get("promedio_comidas_dia_fnu", 3.0)),
            "variabilidad_horaria_min_fnu": float(raw_vector.get("variabilidad_horaria_min_fnu") or 30.0),
            "imc_fnu": float(raw_vector.get("imc_fnu") or 24.0),
        }

        return np.array([[feature_map[feat] for feat in self.FEATURES]], dtype=float), feature_map

    def predict(self, raw_vector: dict) -> dict:
        """
        Ejecuta inferencia y extrae el camino de decisión explicable.
        """
        X, feature_map = self.prepare_feature_vector(raw_vector)

        pred_class_arr = self.model.predict(X)
        pred_class = str(pred_class_arr[0])

        proba_arr = self.model.predict_proba(X)[0]
        classes_in_model = list(self.model.classes_)

        probabilidades = {}
        for c in self.CLASSES:
            if c in classes_in_model:
                probabilidades[c] = round(float(proba_arr[classes_in_model.index(c)]), 4)
            else:
                probabilidades[c] = 0.0

        confidence = probabilidades.get(pred_class, 0.0)

        # Extraer el camino de decisión (Decision Path)
        decision_path_csr = self.model.decision_path(X)
        node_indices = decision_path_csr.indices

        camino_decision = []
        variables_involucradas = {}

        tree = self.model.tree_

        for node_id in node_indices:
            # Si no es hoja
            if tree.feature[node_id] != -2:  # _tree.TREE_UNDEFINED
                feature_idx = tree.feature[node_id]
                feature_name = self.FEATURES[feature_idx]
                threshold = round(float(tree.threshold[node_id]), 2)
                patient_val = round(float(X[0, feature_idx]), 2)

                variables_involucradas[feature_name] = patient_val

                if patient_val <= threshold:
                    cond_str = f"{feature_name} ({patient_val}) <= {threshold}"
                    decision_str = f"Paso por nodo #{node_id}: {feature_name} = {patient_val} es menor o igual al umbral {threshold}."
                else:
                    cond_str = f"{feature_name} ({patient_val}) > {threshold}"
                    decision_str = f"Paso por nodo #{node_id}: {feature_name} = {patient_val} supera el umbral {threshold}."

                camino_decision.append({
                    "nodo_id": int(node_id),
                    "feature": feature_name,
                    "valor_paciente": patient_val,
                    "umbral_modelo": threshold,
                    "condicion_cumplida": cond_str,
                    "explicacion": decision_str,
                })

        modelo_info = self.metadata.get("modelo", {})

        return {
            "clase_predicha": pred_class,
            "resultado_formateado": f"Riesgo {pred_class.capitalize()}",
            "probabilidades": probabilidades,
            "probabilidad_clase": confidence,
            "version_modelo": modelo_info.get("version_mml", "v1.0-dt-candidate"),
            "version_features": modelo_info.get("version_features_mml", "v1.0"),
            "origen_dataset": modelo_info.get("origen_dataset_mml", "sintetico_controlado"),
            "protocolo_sintetico": modelo_info.get("protocolo_sintetico", "protocolo_sintetico_riesgo_v1.0"),
            "camino_decision": camino_decision,
            "variables_involucradas": variables_involucradas,
            "advertencia": self.ADVERTENCIA,
        }
