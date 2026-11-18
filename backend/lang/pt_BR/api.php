<?php

// Mensagens da API em português (o idioma vem do header Accept-Language)
return [
    'validacao' => 'Os dados informados são inválidos.',
    'nao_encontrado' => 'Recurso não encontrado.',
    'metodo_nao_permitido' => 'Método HTTP não permitido para esta rota.',
    'erro_http' => 'Não foi possível processar a requisição.',
    'erro_interno' => 'Erro interno no servidor.',
    'dominio_incompleto' => 'O cálculo usa as classes do desafio (app/Domain/DueDiligence), que ainda não estão completas.',

    'terceiro' => [
        'criado' => 'Terceiro cadastrado com sucesso.',
        'atualizado' => 'Terceiro atualizado com sucesso.',
        'excluido' => 'Terceiro excluído com sucesso.',
    ],
    'apontamento' => [
        'criado' => 'Apontamento adicionado.',
        'excluido' => 'Apontamento removido.',
    ],
    'socio' => [
        'criado' => 'Sócio adicionado ao quadro societário.',
        'excluido' => 'Sócio removido do quadro societário.',
        'apenas_pj' => 'Apenas pessoa jurídica pode ter sócios.',
        'soma_excede' => 'A soma das participações ficaria em :total% (máximo 100%).',
    ],
    'anexo' => [
        'criado' => 'Documento anexado com sucesso.',
        'excluido' => 'Documento removido.',
    ],
    'analise' => [
        'iniciada' => 'Análise iniciada: a IA vai ler :total documento(s). Acompanhe o progresso.',
        'em_andamento' => 'Já existe uma análise em andamento para este terceiro.',
        'sem_documentos' => 'Anexe pelo menos um documento antes de pedir a análise.',
    ],
    'risco' => [
        'calculado' => 'Risco calculado.',
    ],
];
