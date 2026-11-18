#!/usr/bin/env bash
# Cria/atualiza o modelo "due-diligence" no Ollama a partir do ia/Modelfile.
# Uso: ./ia/criar-modelo.sh [container-ollama]
set -euo pipefail

CONTAINER="${1:-api-ollama-1}"
DIR="$(cd "$(dirname "$0")" && pwd)"

if command -v ollama >/dev/null 2>&1; then
  ollama create due-diligence -f "$DIR/Modelfile"
else
  # Ollama rodando em container Docker
  docker cp "$DIR/Modelfile" "$CONTAINER:/tmp/Modelfile.due-diligence"
  docker exec "$CONTAINER" ollama create due-diligence -f /tmp/Modelfile.due-diligence
fi

echo "Modelo pronto. Teste: curl -s http://127.0.0.1:11434/api/tags | grep due-diligence"
