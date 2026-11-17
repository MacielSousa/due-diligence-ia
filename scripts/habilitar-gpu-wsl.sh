#!/usr/bin/env bash
# Habilita a GPU NVIDIA para o container do Ollama (Docker Engine nativo no WSL2).
# Pré-requisito: driver NVIDIA atualizado no Windows (com suporte a WSL).
# Uso: bash scripts/habilitar-gpu-wsl.sh
set -euo pipefail

API_DIR="$(cd "$(dirname "$0")/.." && pwd)/apps/api"

echo "1/4 Verificando a GPU dentro do WSL..."
if ! /usr/lib/wsl/lib/nvidia-smi -L 2>/dev/null; then
  echo "A GPU não aparece no WSL. Atualize o driver NVIDIA no Windows, rode 'wsl --shutdown' no PowerShell e tente de novo." >&2
  exit 1
fi

echo "2/4 Instalando o NVIDIA Container Toolkit (repositório oficial da NVIDIA)..."
if ! command -v nvidia-ctk >/dev/null; then
  curl -fsSL https://nvidia.github.io/libnvidia-container/gpgkey \
    | sudo gpg --dearmor -o /usr/share/keyrings/nvidia-container-toolkit-keyring.gpg
  curl -fsSL https://nvidia.github.io/libnvidia-container/stable/deb/nvidia-container-toolkit.list \
    | sed 's#deb https://#deb [signed-by=/usr/share/keyrings/nvidia-container-toolkit-keyring.gpg] https://#g' \
    | sudo tee /etc/apt/sources.list.d/nvidia-container-toolkit.list >/dev/null
  sudo apt-get update
  sudo apt-get install -y nvidia-container-toolkit
fi

echo "3/4 Configurando o Docker para usar o runtime NVIDIA..."
sudo nvidia-ctk runtime configure --runtime=docker
if pidof systemd >/dev/null 2>&1; then sudo systemctl restart docker; else sudo service docker restart; fi

echo "4/4 Ativando o override de GPU e recriando o container do Ollama..."
cd "$API_DIR"
grep -q '^COMPOSE_FILE=' .env \
  && sed -i 's|^COMPOSE_FILE=.*|COMPOSE_FILE=compose.yaml:compose.gpu.yaml|' .env \
  || printf '\n# GPU NVIDIA para o Ollama\nCOMPOSE_FILE=compose.yaml:compose.gpu.yaml\n' >> .env
./vendor/bin/sail up -d --force-recreate ollama
./vendor/bin/sail exec ollama nvidia-smi -L
./vendor/bin/sail up -d

echo "PRONTO: Ollama com GPU."
