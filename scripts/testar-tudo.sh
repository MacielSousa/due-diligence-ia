#!/usr/bin/env bash
# Roda todas as suítes de teste da PoC. Pré-requisito: containers do Sail no ar.
# Uso: bash scripts/testar-tudo.sh
set -uo pipefail
RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
falhas=0

secao() { printf '\n==================== %s ====================\n' "$1"; }

secao "1. Motor de risco (packages/risk-engine) — unitários"
docker compose -f "$RAIZ/apps/api/compose.yaml" --project-directory "$RAIZ/apps/api" \
  exec -T -u sail -w /var/packages/risk-engine laravel.test \
  sh -c 'composer install -q --no-interaction && vendor/bin/phpunit --colors=never' || falhas=$((falhas + 1))

cd "$RAIZ/apps/api"

secao "2. Módulos Laravel — unitários"
./vendor/bin/sail artisan test --testsuite=Unit --compact || falhas=$((falhas + 1))

secao "3. Integração — API + MySQL + Redis + MongoDB"
./vendor/bin/sail artisan test --testsuite=Integration --compact || falhas=$((falhas + 1))

secao "4. Frontend Vue — Vitest"
./vendor/bin/sail exec -T web npm test || falhas=$((falhas + 1))

secao "5. Frontend Vue — type-check + build"
./vendor/bin/sail exec -T web npm run build || falhas=$((falhas + 1))

secao "Resultado"
if [ "$falhas" -eq 0 ]; then echo "TODAS AS SUÍTES PASSARAM"; else echo "$falhas SUÍTE(S) FALHARAM"; fi
exit "$falhas"
