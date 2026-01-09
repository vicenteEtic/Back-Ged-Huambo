# Parâmetros de configuração do ML Service

ML_MODEL_PATH = "/app/model.pkl"   # onde o modelo treinado será salvo
ISOLATION_FOREST_PARAMS = {
    "n_estimators": 100,
    "contamination": 0.05,
    "random_state": 42
}
ONE_CLASS_SVM_PARAMS = {
    "kernel": "rbf",
    "gamma": 0.1,
    "nu": 0.05
}
