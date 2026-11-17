<?php

declare(strict_types=1);

namespace RiskEngine\Contracts;

/**
 * Porta para o modelo de linguagem. O motor não conhece nenhum provedor:
 * a aplicação injeta um adaptador (Claude, fake de teste, heurístico de demo...).
 */
interface LlmClient
{
    /**
     * Envia um prompt e devolve a resposta já decodificada, obedecendo ao JSON Schema informado.
     *
     * @param array<string, mixed> $schema JSON Schema da resposta esperada
     * @return array<string, mixed>
     *
     * @throws \RiskEngine\Exceptions\LlmException
     */
    public function json(string $system, string $prompt, array $schema): array;
}
