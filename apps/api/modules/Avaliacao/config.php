<?php

/*
 * Política de score — parâmetros de negócio definidos pelo time de compliance.
 * O LLM nunca decide o nível sozinho: ele dá notas por dimensão e o código classifica.
 */
return [
    'fila' => env('AVALIACAO_FILA', 'avaliacoes'),

    'politica' => [
        'weights' => [
            'financeira' => 0.30,
            'tecnica' => 0.25,
            'trabalhista' => 0.20,
            'juridica' => 0.15,
            'reputacional' => 0.10,
        ],
        'medium_threshold' => 35,
        'high_threshold' => 65,
        'critical_dimension' => 80,
        'min_confidence' => 0.7,
    ],
];
