from flask import Flask, request, jsonify
from datetime import datetime
from collections import defaultdict

app = Flask(__name__)

# ---------------------------
# Função de avaliação de transações
# ---------------------------
def evaluate_transaction(user_id, transactions):
    """
    Avalia um conjunto de transações do usuário e retorna alertas.
    
    Critérios de exemplo:
    - Qualquer transação acima de 500.000 dispara alerta
    - Somatório diário de transações acima de 1.000.000 dispara alerta
    - Múltiplas transações repetidas no mesmo dia podem disparar alerta
    """
    alerts = []
    daily_totals = defaultdict(float)
    tx_count_by_day = defaultdict(int)

    for tx in transactions:
        amount = tx.get("amount", 0)
        date_str = tx.get("date", None)
        date_key = None
        if date_str:
            date_key = datetime.fromisoformat(date_str).date()

        # Alerta por valor alto
        if amount > 500000:
            alerts.append({
                "user_id": user_id,
                "transaction": tx,
               # "risk_score": 10,
                "alert": True,
                "reason": "Transação acima do limite"
            })

        # Acumula total diário
        if date_key:
            daily_totals[date_key] += amount
            tx_count_by_day[date_key] += 1

    # Avaliação diária
    for date_key, total in daily_totals.items():
        if total > 1000000:
            alerts.append({
                "user_id": user_id,
                "transaction": {"date": str(date_key), "total_amount": total},
                "risk_score": 8,
                "alert": True,
                "reason": "Acúmulo diário acima do limite"
            })
        if tx_count_by_day[date_key] > 5:  # mais de 5 transações no mesmo dia
            alerts.append({
                "user_id": user_id,
                "transaction": {"date": str(date_key), "count": tx_count_by_day[date_key]},
                "risk_score": 5,
                "alert": True,
                "reason": "Muitas transações no mesmo dia"
            })

    return alerts

# ---------------------------
# Endpoints
# ---------------------------
@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"}), 200

@app.route("/train/<int:user_id>", methods=["POST"])
def train(user_id):
    data = request.json
    return jsonify({
        "status": f"Perfil do utente {user_id} treinado",
        "dados_recebidos": data
    }), 200

@app.route("/analyze/<int:user_id>", methods=["POST"])
def analyze(user_id):
    data = request.json
    return jsonify({
        "user_id": user_id,
        "score_risco": 0,
        "dados": data
    }), 200

@app.route('/evaluate', methods=['POST'])
def evaluate():
    """
    Recebe:
    {
        "user_id": 123,
        "transactions": [ {transação1}, {transação2}, ... ]
    }
    """
    data = request.json
    user_id = data['user_id']
    transactions = data['transactions']

    alerts = evaluate_transaction(user_id, transactions)

    return jsonify({
        "alerts": alerts,
        "total_alerts": len(alerts)
    })


if __name__ == "__main__":
    print("🔥 Flask iniciado na porta 5000")
    app.run(host="0.0.0.0", port=5000)
