<?php

// Mensagens de validação em português (só as regras usadas no projeto;
// o que faltar cai no inglês, que é o idioma de fallback).
return [
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'exists' => 'O :attribute selecionado é inválido.',
    'file' => 'O :attribute deve ser um arquivo.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],
    'in' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'file' => 'O :attribute não pode ter mais de :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'mimes' => 'O :attribute deve ser do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'not_in' => 'O :attribute selecionado é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'prohibited' => 'O campo :attribute não pode ser alterado.',
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está cadastrado.',
    'uploaded' => 'Falha ao enviar o :attribute.',

    // regras próprias (app/Rules)
    'cpf' => 'O CPF informado é inválido.',
    'cnpj' => 'O CNPJ informado é inválido.',

    'attributes' => [
        'tipo' => 'tipo',
        'nome' => 'nome',
        'documento' => 'documento',
        'pep' => 'PEP',
        'ano' => 'ano',
        'socio_id' => 'sócio',
        'participacao' => 'participação',
        'busca' => 'busca',
        'categoria' => 'categoria',
        'arquivo' => 'arquivo',
    ],
];
