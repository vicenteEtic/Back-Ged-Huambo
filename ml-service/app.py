from flask import Flask, request, jsonify
import pymysql
from datetime import datetime
from collections import defaultdict

app = Flask(__name__)

existing_alerts = set()
PRODUCT_LIMITS = {}          # {"PROD-001": 2, ...}
HIGH_RISK_COUNTRIES = set()  # {"Afeganistão", "África do Sul", ...}

# Configuração MySQL
DB_CONFIG = {
    "host": "setupNossaSeguros-mysql",
    "port": 3306,
    "user": "user",
    "password": "nf@204",
    "db": "nf",
    "cursorclass": pymysql.cursors.DictCursor
}

def load_dynamic_data():
    global PRODUCT_LIMITS, HIGH_RISK_COUNTRIES
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cursor:
            # Limites por produto
            cursor.execute("SELECT description, score FROM indicator_type")
            PRODUCT_LIMITS = {row['description']: row['score'] for row in cursor.fetchall()}

            # Países de risco alto (score >= 3)
            cursor.execute("SELECT description, score FROM indicator_type  AND score >= 3")
            HIGH_RISK_COUNTRIES.update(row['description'] for row in cursor.fetchall())
    finally:
        conn.close()

# Carrega dados ao iniciar o serviço
load_dynamic_data()

def evaluate_transaction(transactions):
    alerts = []
    daily_totals = defaultdict(float)
    tx_count_by_day = defaultdict(int)

    for tx in transactions:
        uid = tx.get("transaction_uid")
        client_id = tx.get("client_id") or "unknown"
        amount = tx.get("amount", 0)
        date_key = datetime.fromisoformat(tx.get("transaction_date")).date()
        product = tx.get("product_code")
        country = tx.get("country") or tx.get("high_risk_country", False)

        # 1️⃣ Limite por produto
        limit = PRODUCT_LIMITS.get(product, 2)
        if amount > limit:
            key = f"{uid}-product-limit"
            if key not in existing_alerts:
                existing_alerts.add(key)
                alerts.append({
                    "client_id": client_id,
                    "transaction_ref": uid,
                    "alert": True,
                    "reason": f"Transação acima do limite do produto {product}",
                    "severity": "high",
                    "risk_score": limit // 1000000 + 1
                })

        # 2️⃣ País de risco alto
        if country in HIGH_RISK_COUNTRIES or tx.get("high_risk_country"):
            key = f"{uid}-high-risk-country"
            if key not in existing_alerts:
                existing_alerts.add(key)
                alerts.append({
                    "client_id": client_id,
                    "transaction_ref": uid,
                    "alert": True,
                    "reason": f"Transação para país de risco elevado: {country}",
                    "severity": "high",
                    "risk_score": 10
                })

        # 3️⃣ Acúmulo diário e frequência
        daily_totals[(client_id, date_key)] += amount
        tx_count_by_day[(client_id, date_key)] += 1

    for (client_id, date_key), total in daily_totals.items():
        key = f"{client_id}-{date_key}-daily"
        if total > 100 and key not in existing_alerts:
            existing_alerts.add(key)
            alerts.append({
                "client_id": client_id,
                "transaction_ref": None,
                "transaction": {"date": str(date_key), "total_amount": total},
                "alert": True,
                "reason": "Acúmulo diário acima do limite",
                "severity": "medium",
                "risk_score": 8
            })

        if tx_count_by_day[(client_id, date_key)] > 5:
            key = f"{client_id}-{date_key}-freq"
            if key not in existing_alerts:
                existing_alerts.add(key)
                alerts.append({
                    "client_id": client_id,
                    "transaction_ref": None,
                    "transaction": {"date": str(date_key), "count": tx_count_by_day[(client_id, date_key)]},
                    "alert": True,
                    "reason": "Muitas transações no mesmo dia",
                    "severity": "low",
                    "risk_score": 5
                })

    return alerts

@app.route("/evaluate", methods=["POST"])
def evaluate():
    data = request.get_json(force=True) or {}
    transactions = data.get("transactions", [])
    if not isinstance(transactions, list):
        transactions = []

    alerts = evaluate_transaction(transactions)
    return jsonify({"alerts": alerts, "total_alerts": len(alerts)})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
