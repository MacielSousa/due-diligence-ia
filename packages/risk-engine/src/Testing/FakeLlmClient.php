<?php

declare(strict_types=1);

namespace RiskEngine\Testing;

use Closure;
use RiskEngine\Contracts\LlmClient;
use RiskEngine\Exceptions\LlmException;

/** Dublê de teste: devolve respostas pré-programadas e registra as chamadas. */
final class FakeLlmClient implements LlmClient
{
    /** @var list<array{system: string, prompt: string, schema: array<string, mixed>}> */
    public array $calls = [];

    /** @var list<array<string, mixed>|Closure> */
    private array $queue;

    /** @param list<array<string, mixed>|Closure(string, string, array<string, mixed>): array<string, mixed>> $responses */
    public function __construct(array $responses = [])
    {
        $this->queue = $responses;
    }

    public function push(array|Closure $response): self
    {
        $this->queue[] = $response;

        return $this;
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        $this->calls[] = compact('system', 'prompt', 'schema');

        if ($this->queue === []) {
            throw new LlmException('FakeLlmClient sem respostas programadas.');
        }

        $next = array_shift($this->queue);

        return $next instanceof Closure ? $next($system, $prompt, $schema) : $next;
    }
}
