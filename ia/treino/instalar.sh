#!/usr/bin/env bash
# Prepara o ambiente de treino (Python + PyTorch com CUDA + Unsloth) num venv isolado.
# Download aproximado: ~4-5 GB (PyTorch/CUDA) + ~2,2 GB do modelo base no 1º treino.
set -euo pipefail

VENV="${VENV:-$HOME/.venvs/ia-treino}"

python3 -m venv "$VENV"
# shellcheck disable=SC1091
source "$VENV/bin/activate"
pip install --upgrade pip wheel
pip install unsloth   # traz torch (CUDA 12), transformers, trl, peft, bitsandbytes, datasets

python - <<'PY'
import torch
print("torch", torch.__version__, "| CUDA disponível:", torch.cuda.is_available())
if torch.cuda.is_available():
    print("GPU:", torch.cuda.get_device_name(0))
PY
echo "Ambiente pronto. Ative com: source $VENV/bin/activate"
