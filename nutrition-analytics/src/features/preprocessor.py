"""
Pipeline de preprocesamiento de variables utilizando StandardScaler para clustering K-Means.
"""

from pathlib import Path
from typing import List, Optional, Union
import joblib
import numpy as np
import pandas as pd
from sklearn.preprocessing import StandardScaler


class FeaturePreprocessor:
    """
    Gestiona la estandarización de variables numéricas para modelos de clustering.
    Persiste el estado del scaler para garantizar transformaciones consistentes entre entrenamiento e inferencia.
    """

    def __init__(self, feature_names: Optional[List[str]] = None):
        self.feature_names = feature_names or []
        self.scaler = StandardScaler()
        self.is_fitted = False

    def fit(self, X: Union[pd.DataFrame, np.ndarray]) -> "FeaturePreprocessor":
        """Ajusta el StandardScaler sobre las variables de entrenamiento."""
        if isinstance(X, pd.DataFrame):
            if self.feature_names:
                df_subset = X[self.feature_names]
            else:
                self.feature_names = list(X.columns)
                df_subset = X
            self.scaler.fit(df_subset.values)
        else:
            self.scaler.fit(X)

        self.is_fitted = True
        return self

    def transform(self, X: Union[pd.DataFrame, np.ndarray]) -> np.ndarray:
        """Transforma las variables utilizando el StandardScaler ajustado."""
        if not self.is_fitted:
            raise RuntimeError("FeaturePreprocessor debe ser entrenado con .fit() antes de llamar a .transform()")

        if isinstance(X, pd.DataFrame):
            if self.feature_names:
                data_matrix = X[self.feature_names].values
            else:
                data_matrix = X.values
        else:
            data_matrix = np.asarray(X)

        return self.scaler.transform(data_matrix)

    def fit_transform(self, X: Union[pd.DataFrame, np.ndarray]) -> np.ndarray:
        """Ajusta y transforma en una sola llamada."""
        return self.fit(X).transform(X)

    def inverse_transform(self, X_scaled: Union[pd.DataFrame, np.ndarray]) -> np.ndarray:
        """Invierte la transformación de escalado volviendo a la escala original de las variables."""
        if not self.is_fitted:
            raise RuntimeError("FeaturePreprocessor no ha sido entrenado.")
        matrix = X_scaled.values if isinstance(X_scaled, pd.DataFrame) else np.asarray(X_scaled)
        return self.scaler.inverse_transform(matrix)

    def save(self, filepath: Union[str, Path]) -> str:
        """Serializa el estado del preprocesador en disco con joblib."""
        path = Path(filepath)
        path.parent.mkdir(parents=True, exist_ok=True)
        payload = {
            "scaler": self.scaler,
            "feature_names": self.feature_names,
            "is_fitted": self.is_fitted,
            "mean_": self.scaler.mean_ if self.is_fitted else None,
            "scale_": self.scaler.scale_ if self.is_fitted else None,
        }
        joblib.dump(payload, path)
        return str(path)

    @classmethod
    def load(cls, filepath: Union[str, Path]) -> "FeaturePreprocessor":
        """Carga el preprocesador serializado desde el disco."""
        path = Path(filepath)
        if not path.exists():
            raise FileNotFoundError(f"Archivo de scaler no encontrado en: {path}")

        payload = joblib.load(path)
        instance = cls(feature_names=payload.get("feature_names", []))
        instance.scaler = payload["scaler"]
        instance.is_fitted = payload.get("is_fitted", True)
        return instance
