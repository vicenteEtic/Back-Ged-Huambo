from conf.config import ISOLATION_FOREST_PARAMS
from sklearn.ensemble import IsolationForest

model = IsolationForest(**ISOLATION_FOREST_PARAMS)
