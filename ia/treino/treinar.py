"""
Fine-tuning QLoRA do Llama 3.2 3B Instruct para o modelo due-diligence.

QLoRA = o modelo base fica congelado em 4 bits e treinamos só um ADAPTADOR pequeno (LoRA).
Pensado para GPU de 4 GB (GTX 1650): fp16 (sem bf16), batch 1 com acumulação de gradiente,
sequência de até 1536 tokens e gradient checkpointing.

Antes de rodar: descarregue a Llama do Ollama para liberar a GPU (curl ... keep_alive 0).

Uso:  python treinar.py [epocas]
Saída: saida/adapter/ (adaptador LoRA em safetensors) e saida/metricas-treino.json
"""

import json
import sys
import time
from pathlib import Path

import torch
from datasets import load_dataset
from trl import SFTConfig, SFTTrainer
from unsloth import FastLanguageModel
from unsloth.chat_templates import get_chat_template, train_on_responses_only

AQUI = Path(__file__).resolve().parent
BASE = "unsloth/Llama-3.2-3B-Instruct-bnb-4bit"  # mesmos pesos do llama3.2:3b do Ollama, já em 4 bits
MAX_SEQ = 1536
EPOCAS = float(sys.argv[1]) if len(sys.argv) > 1 else 1

if not torch.cuda.is_available():
    sys.exit("CUDA indisponível: o treino precisa da GPU.")
print(f"GPU: {torch.cuda.get_device_name(0)} | memória: {torch.cuda.get_device_properties(0).total_memory / 2**30:.1f} GB")

# ---------------------------------------------------------------- modelo + adaptador LoRA
model, tokenizer = FastLanguageModel.from_pretrained(
    model_name=BASE, max_seq_length=MAX_SEQ, dtype=torch.float16, load_in_4bit=True,
)
model = FastLanguageModel.get_peft_model(
    model,
    r=16, lora_alpha=16, lora_dropout=0, bias="none",
    target_modules=["q_proj", "k_proj", "v_proj", "o_proj", "gate_proj", "up_proj", "down_proj"],
    use_gradient_checkpointing="unsloth",  # troca memória da GPU por tempo
    random_state=42,
)
tokenizer = get_chat_template(tokenizer, chat_template="llama-3.1")

# ---------------------------------------------------------------- dados
dados = load_dataset("json", data_files={
    "train": str(AQUI / "dados" / "treino.jsonl"),
    "validation": str(AQUI / "dados" / "validacao.jsonl"),
})
dados = dados.map(
    lambda lote: {"text": [tokenizer.apply_chat_template(m, tokenize=False) for m in lote["messages"]]},
    batched=True,
)

# ---------------------------------------------------------------- treino
trainer = SFTTrainer(
    model=model,
    tokenizer=tokenizer,
    train_dataset=dados["train"],
    eval_dataset=dados["validation"],
    args=SFTConfig(
        dataset_text_field="text",
        max_seq_length=MAX_SEQ,
        per_device_train_batch_size=1,
        gradient_accumulation_steps=8,  # lote efetivo de 8 exemplos
        num_train_epochs=EPOCAS,
        learning_rate=2e-4,
        warmup_steps=5,
        lr_scheduler_type="linear",
        weight_decay=0.01,
        optim="adamw_8bit",
        fp16=True, bf16=False,  # GTX 1650 não tem bf16
        logging_steps=10,
        eval_strategy="steps", eval_steps=40,
        save_strategy="no",
        output_dir=str(AQUI / "saida" / "checkpoints"),
        report_to="none",
        seed=42,
    ),
)
# a perda só conta na resposta (JSON de achados), não no documento de entrada
trainer = train_on_responses_only(
    trainer,
    instruction_part="<|start_header_id|>user<|end_header_id|>\n\n",
    response_part="<|start_header_id|>assistant<|end_header_id|>\n\n",
)

inicio = time.time()
resultado = trainer.train()
duracao = time.time() - inicio
avaliacao = trainer.evaluate()

saida = AQUI / "saida" / "adapter"
model.save_pretrained(saida)
tokenizer.save_pretrained(saida)

metricas = {
    "modelo_base": BASE,
    "epocas": EPOCAS,
    "exemplos_treino": len(dados["train"]),
    "exemplos_validacao": len(dados["validation"]),
    "perda_treino": round(resultado.training_loss, 4),
    "perda_validacao": round(avaliacao.get("eval_loss", float("nan")), 4),
    "duracao_segundos": round(duracao),
    "pico_memoria_gpu_gb": round(torch.cuda.max_memory_reserved() / 2**30, 2),
}
(AQUI / "saida" / "metricas-treino.json").write_text(json.dumps(metricas, indent=2, ensure_ascii=False))
print(json.dumps(metricas, indent=2, ensure_ascii=False))
print(f"Adaptador salvo em {saida}")
