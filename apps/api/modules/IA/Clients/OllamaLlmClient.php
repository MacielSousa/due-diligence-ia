<?php

declare(strict_types=1);

namespace Modules\IA\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Modules\IA\Contracts\ReportaUso;
use RiskEngine\Contracts\LlmClient;
use RiskEngine\Exceptions\LlmException;

/**
 * LLM local e gratuito (ex.: Llama 3.2) servido pelo Ollama.
 * Usa "structured outputs" do Ollama: o JSON Schema vai em `format` e a resposta já vem no formato.
 */
final class OllamaLlmClient implements LlmClient, ReportaUso
{
    /** @var array{modelo: string, tokens_entrada: int, tokens_saida: int}|null */
    private ?array $ultimoUso = null;

    public function __construct(
        private readonly Http $http,
        private readonly string $url,
        private readonly string $model,
        private readonly int $timeout,
        private readonly int $contexto = 8192,
    ) {
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->post(rtrim($this->url, '/') . '/api/chat', [
                    'model' => $this->model,
                    'stream' => false,
                    'format' => $schema,
                    'options' => ['temperature' => 0, 'num_ctx' => $this->contexto],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new LlmException("Ollama indisponível em {$this->url}: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new LlmException("Ollama respondeu HTTP {$response->status()}: " . $response->json('error', $response->body()));
        }

        $this->ultimoUso = [
            'modelo' => $this->model,
            'tokens_entrada' => (int) $response->json('prompt_eval_count', 0),
            'tokens_saida' => (int) $response->json('eval_count', 0),
        ];

        $decoded = json_decode((string) $response->json('message.content'), true);

        if (! is_array($decoded)) {
            throw new LlmException('Resposta do Ollama não é um objeto JSON.');
        }

        return $decoded;
    }

    public function ultimoUso(): ?array
    {
        return $this->ultimoUso;
    }
}
