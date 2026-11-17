#!/usr/bin/env bash
# Avalia as construtoras da demo com o Llama local (Ollama), sem alterar o IA_DRIVER do .env.
# Uso: bash scripts/avaliar-com-llama.sh [ids...]   (padrão: 1 2 3)
set -uo pipefail
cd "$(dirname "$0")/../apps/api"

for id in "${@:-1 2 3}"; do
  for f in $id; do
    echo "=================== Fornecedor $f ==================="
    inicio=$(date +%s)
    docker compose exec -T -u sail -e IA_DRIVER=ollama laravel.test php artisan risco:avaliar "$f"
    echo "Tempo: $(( $(date +%s) - inicio ))s"
  done
done
