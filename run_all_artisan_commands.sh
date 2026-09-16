#!/usr/bin/env bash

# Executa os comandos Artisan de manutenção dentro do container PHP do projecto.
#
# Uso:
#   ./run_all_artisan_commands.sh
#   ./run_all_artisan_commands.sh --date=2026-09-15
#   ./run_all_artisan_commands.sh --year=2026 --country=AO --days=15
#
# O script não executa comandos interactivos nem comandos que exigem um ID.

set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${ROOT}" || exit 1

COMPOSE_SERVICE="${PHP_COMPOSE_SERVICE:-php}"
MARK_DATE=""
HOLIDAY_YEAR=""
HOLIDAY_COUNTRY="AO"
EXPIRY_DAYS="30"
FAILURES=0

for arg in "$@"; do
    case "${arg}" in
        --date=*) MARK_DATE="${arg#*=}" ;;
        --year=*) HOLIDAY_YEAR="${arg#*=}" ;;
        --country=*) HOLIDAY_COUNTRY="${arg#*=}" ;;
        --days=*) EXPIRY_DAYS="${arg#*=}" ;;
        -h|--help)
            sed -n '3,10p' "${BASH_SOURCE[0]}"
            exit 0
            ;;
        *)
            printf 'Argumento desconhecido: %s\n' "${arg}" >&2
            exit 2
            ;;
    esac
done

if ! docker compose ps -q "${COMPOSE_SERVICE}" >/dev/null 2>&1; then
    printf 'Não foi possível encontrar o serviço Docker "%s".\n' "${COMPOSE_SERVICE}" >&2
    printf 'Inicie o projecto com: docker compose up -d %s\n' "${COMPOSE_SERVICE}" >&2
    exit 1
fi

run_artisan() {
    local description="$1"
    shift

    printf '\n==================================================\n'
    printf '>>> %s\n' "${description}"
    printf '==================================================\n'

    if docker compose exec -T "${COMPOSE_SERVICE}" php artisan "$@"; then
        return 0
    fi

    local status=$?
    printf '!!! Falha ao executar %s (código %s)\n' "${description}" "${status}" >&2
    FAILURES=$((FAILURES + 1))
}

printf 'A executar comandos Artisan no serviço Docker "%s"...\n' "${COMPOSE_SERVICE}"

run_artisan 'Sincronizar feriados' \
    rh:sync-holidays --country="${HOLIDAY_COUNTRY}" ${HOLIDAY_YEAR:+--year="${HOLIDAY_YEAR}"}
run_artisan 'Marcar faltas automáticas' \
    rh:mark-absent ${MARK_DATE:+--date="${MARK_DATE}"}
run_artisan 'Verificar aniversários' rh:check-birthdays
run_artisan 'Verificar documentos a expirar' rh:check-document-expiry --days="${EXPIRY_DAYS}"
run_artisan 'Verificar avaliações pendentes' rh:check-pending-evaluations
run_artisan 'Verificar pedidos de férias pendentes' rh:check-pending-leaves
run_artisan 'Verificar férias próximas' rh:check-upcoming-leaves
run_artisan 'Expirar dispensas de amamentação' rh:expire-breastfeeding-dispensas

printf '\nExecução concluída: %s falha(s).\n' "${FAILURES}"
exit "${FAILURES}"
