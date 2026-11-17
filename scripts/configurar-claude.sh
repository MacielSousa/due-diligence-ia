#!/usr/bin/env bash
# Configura o driver do Claude no apps/api/.env sem exibir a chave na tela.
# Uso: bash scripts/configurar-claude.sh
set -euo pipefail

ENV_FILE="$(cd "$(dirname "$0")/.." && pwd)/apps/api/.env"

read -rsp "Cole sua ANTHROPIC_API_KEY (não aparece na tela) e tecle Enter: " CHAVE
echo

if [[ ! "$CHAVE" =~ ^sk-ant-[A-Za-z0-9_-]+$ ]]; then
  echo "Formato inesperado (deveria começar com sk-ant-). Nada foi alterado." >&2
  exit 1
fi

sed -i \
  -e "s|^ANTHROPIC_API_KEY=.*|ANTHROPIC_API_KEY=${CHAVE}|" \
  -e "s|^IA_DRIVER=.*|IA_DRIVER=anthropic|" \
  "$ENV_FILE"
unset CHAVE

cd "$(dirname "$ENV_FILE")"
./vendor/bin/sail artisan config:clear >/dev/null
./vendor/bin/sail restart queue >/dev/null 2>&1

echo "PRONTO: IA_DRIVER=anthropic configurado e worker reiniciado."
