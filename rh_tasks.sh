#!/bin/bash
#
# rh_tasks.sh — Executa todos os comandos RH de manutenção/agendados do projeto.
#
# Uso:
#   ./rh_tasks.sh                # roda todos os comandos
#   ./rh_tasks.sh --only-rh     # apenas comandos rh:*
#   ./rh_tasks.sh --date=2026-08-12   # define a data para rh:mark-absent
#   SKIP_SYNC_HOLIDAYS=1 ./rh_tasks.sh  # pula a sincronização de feriados
#

set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARTISAN="${ROOT}/artisan"

MARK_DATE=""
ONLY_RH=false

for arg in "$@"; do
    case "$arg" in
        --only-rh) ONLY_RH=true ;;
        --date=*) MARK_DATE="${arg#*=}" ;;
    esac
done

run() {
    local label="$1"
    shift
    echo ""
    echo "=================================================="
    echo ">>> ${label}"
    echo "=================================================="
    php "${ARTISAN}" "$@"
    local code=$?
    if [ $code -ne 0 ]; then
        echo "!!! Falha ao executar: ${label} (código ${code})"
    fi
    return $code
}

echo "Iniciando tarefas RH em $(date '+%Y-%m-%d %H:%M:%S')"

run "Sincronizar feriados nacionais" rh:sync-holidays

if [ -n "${MARK_DATE:-}" ]; then
    run "Marcar faltas automáticas (data: ${MARK_DATE})" rh:mark-absent --date="${MARK_DATE}"
else
    run "Marcar faltas automáticas (ontem)" rh:mark-absent
fi

run "Aniversários do dia" rh:check-birthdays
run "Documentos a expirar (30 dias)" rh:check-document-expiry --days=30
run "Pedidos de férias pendentes (> 3 dias)" rh:check-pending-leaves
run "Avaliações de desempenho pendentes" rh:check-pending-evaluations
run "Férias no próximo mês" rh:check-upcoming-leaves

echo ""
echo "Tarefas RH concluídas em $(date '+%Y-%m-%d %H:%M:%S')."
