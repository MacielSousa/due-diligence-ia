<?php

namespace App\Services\Ia;

/**
 * Contrato (porta) de qualquer modelo de linguagem que lê um documento e devolve achados.
 *
 * Quem usa (AnalisarDocumentosJob) depende só desta interface; qual LLM atende é decidido
 * no AppServiceProvider pela config ia.driver. Trocar o Ollama por outro provedor é criar
 * uma nova implementação e registrá-la lá, sem mexer no job (SOLID: D e O; padrão Strategy).
 */
interface ClienteLlm
{
    /** Nome do modelo, gravado na análise para auditoria. */
    public function modelo(): string;

    /**
     * Lê o texto de um documento e devolve os achados em estado bruto (o ValidadorDeAchados confere depois).
     *
     * @return array{achados: array<int, mixed>, bruto: string, tokens_entrada: ?int, tokens_saida: ?int}
     *
     * @throws IaIndisponivelException quando o provedor não responde ou responde com erro
     */
    public function analisar(string $texto): array;
}
