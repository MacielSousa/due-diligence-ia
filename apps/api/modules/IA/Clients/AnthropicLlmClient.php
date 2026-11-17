<?php

declare(strict_types=1);

namespace Modules\IA\Clients;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use JsonException;
use Modules\IA\Contracts\ReportaUso;
use RiskEngine\Contracts\LlmClient;
use RiskEngine\Exceptions\LlmException;

/**
 * Adaptador do Claude (Anthropic PHP SDK) para a porta LlmClient do motor de risco.
 * Usa saída estruturada (JSON Schema) e cache do prompt de sistema, que se repete em todos os documentos.
 */
final class AnthropicLlmClient implements LlmClient, ReportaUso
{
    /** @var array{modelo: string, tokens_entrada: int, tokens_saida: int}|null */
    private ?array $ultimoUso = null;

    public function __construct(
        private readonly Client $client,
        private readonly string $model,
        private readonly string $effort,
        private readonly int $maxTokens,
    ) {
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        try {
            $message = $this->client->messages->create(
                model: $this->model,
                maxTokens: $this->maxTokens,
                system: [
                    ['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']],
                ],
                messages: [
                    ['role' => 'user', 'content' => $prompt],
                ],
                outputConfig: [
                    'effort' => $this->effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
            );
        } catch (APIException $e) {
            throw new LlmException('Falha ao chamar o Claude: ' . $e->getMessage(), previous: $e);
        }

        $this->ultimoUso = [
            'modelo' => $this->model,
            'tokens_entrada' => $message->usage->inputTokens,
            'tokens_saida' => $message->usage->outputTokens,
        ];

        // Recusa e truncamento não podem virar um parecer silenciosamente incompleto.
        if ($message->stopReason === 'refusal') {
            throw new LlmException('O modelo recusou a solicitação; encaminhar para análise manual.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new LlmException('Resposta do modelo truncada (max_tokens).');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        try {
            $decoded = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new LlmException('Resposta do modelo não é um JSON válido.', previous: $e);
        }

        if (! is_array($decoded)) {
            throw new LlmException('Resposta do modelo não é um objeto JSON.');
        }

        return $decoded;
    }

    public function ultimoUso(): ?array
    {
        return $this->ultimoUso;
    }
}
