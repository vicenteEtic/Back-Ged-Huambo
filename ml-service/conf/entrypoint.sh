#!/bin/bash
set -e

echo "🚀 Iniciando ML Service..."

# Carrega variáveis de ambiente
if [ -f /app/conf/config.env ]; then
  export $(grep -v '^#' /app/conf/config.env | xargs)
fi

# Inicia o Flask
exec python app.py
