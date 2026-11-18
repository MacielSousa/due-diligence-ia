<?php

// Configuração da análise de documentos por IA (Llama local via Ollama).
return [
    'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),

    // modelo especializado criado a partir de ia/Modelfile (ou due-diligence-ft, o treinado com LoRA)
    'modelo' => env('OLLAMA_MODEL', 'due-diligence'),

    // segundos por documento (um 3B na GTX 1650 leva ~5-30 s)
    'timeout' => (int) env('OLLAMA_TIMEOUT', 300),

    // texto maior que isso é cortado antes de ir ao modelo (contexto de 8k tokens)
    'max_caracteres' => (int) env('IA_MAX_CARACTERES', 12000),
];
