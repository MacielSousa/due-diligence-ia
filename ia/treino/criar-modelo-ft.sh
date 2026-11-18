#!/usr/bin/env bash
# Cria o modelo "due-diligence-ft" no Ollama: llama3.2:3b + adaptador LoRA treinado + mesmo SYSTEM do Modelfile.
# Uso: ./ia/treino/criar-modelo-ft.sh [container-ollama]
set -euo pipefail

CONTAINER="${1:-api-ollama-1}"
AQUI="$(cd "$(dirname "$0")" && pwd)"
ADAPTER="$AQUI/saida/adapter"
[ -f "$ADAPTER/adapter_model.safetensors" ] || { echo "Adaptador não encontrado em $ADAPTER (rode treinar.py)"; exit 1; }

# Modelfile do modelo treinado: mesmo SYSTEM e parâmetros, sem os exemplos (o treino já ensinou)
MODELFILE="$AQUI/saida/Modelfile.ft"
{
  echo "FROM llama3.2:3b"
  echo "ADAPTER /tmp/due-diligence-adapter"
  grep '^PARAMETER' "$AQUI/../Modelfile"
  awk '/^SYSTEM """/{f=1} f{print} f&&/^"""$/{exit}' "$AQUI/../Modelfile"
} > "$MODELFILE"

if command -v ollama >/dev/null 2>&1; then
  sed -i "s#/tmp/due-diligence-adapter#$ADAPTER#" "$MODELFILE"
  ollama create due-diligence-ft -f "$MODELFILE"
else
  docker exec "$CONTAINER" rm -rf /tmp/due-diligence-adapter
  docker cp "$ADAPTER" "$CONTAINER:/tmp/due-diligence-adapter"
  docker cp "$MODELFILE" "$CONTAINER:/tmp/Modelfile.ft"
  docker exec "$CONTAINER" ollama create due-diligence-ft -f /tmp/Modelfile.ft
fi
echo "Modelo due-diligence-ft criado."
