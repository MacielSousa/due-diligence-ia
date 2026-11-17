<?php

return [
    /*
     * Driver do LLM:
     *  - "heuristico": regras por palavra-chave, roda offline (padrão da demo e dos testes de integração)
     *  - "anthropic": Claude via Anthropic PHP SDK (exige ANTHROPIC_API_KEY)
     */
    'driver' => env('IA_DRIVER', 'heuristico'),

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('IA_MODELO', 'claude-opus-5-5'),
        // Esforço de raciocínio: low | medium | high | xhigh | max
        'effort' => env('IA_ESFORCO', 'high'),
        'max_tokens' => (int) env('IA_MAX_TOKENS', 16000),
        'timeout' => (float) env('IA_TIMEOUT', 300),
    ],
];
