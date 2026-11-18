<?php

// API messages in English (language comes from the Accept-Language header)
return [
    'validacao' => 'The given data is invalid.',
    'nao_encontrado' => 'Resource not found.',
    'metodo_nao_permitido' => 'HTTP method not allowed for this route.',
    'erro_http' => 'The request could not be processed.',
    'erro_interno' => 'Internal server error.',
    'dominio_incompleto' => 'The calculation uses the challenge classes (app/Domain/DueDiligence), which are not complete yet.',

    'terceiro' => [
        'criado' => 'Third party created successfully.',
        'atualizado' => 'Third party updated successfully.',
        'excluido' => 'Third party deleted successfully.',
    ],
    'apontamento' => [
        'criado' => 'Finding added.',
        'excluido' => 'Finding removed.',
    ],
    'socio' => [
        'criado' => 'Partner added to the ownership structure.',
        'excluido' => 'Partner removed from the ownership structure.',
        'apenas_pj' => 'Only companies can have partners.',
        'soma_excede' => 'The total share would be :total% (maximum 100%).',
    ],
    'anexo' => [
        'criado' => 'Document attached successfully.',
        'excluido' => 'Document removed.',
    ],
    'risco' => [
        'calculado' => 'Risk calculated.',
    ],
];
