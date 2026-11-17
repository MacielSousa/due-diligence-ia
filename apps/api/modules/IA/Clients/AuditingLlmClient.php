<?php

declare(strict_types=1);

namespace Modules\IA\Clients;

use Illuminate\Support\Facades\Context;
use Modules\Auditoria\Contracts\AuditoriaLlm;
use Modules\IA\Contracts\ReportaUso;
use RiskEngine\Contracts\LlmClient;
use Throwable;

/**
 * Decorator: registra toda chamada ao LLM (prompt, resposta, tempo, tokens, erro) na auditoria,
 * associada à avaliação em andamento (via Context do Laravel).
 */
final class AuditingLlmClient implements LlmClient
{
    public function __construct(
        private readonly LlmClient $inner,
        private readonly AuditoriaLlm $auditoria,
        private readonly string $driver,
    ) {
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        $inicio = hrtime(true);
        $resposta = null;
        $erro = null;

        try {
            return $resposta = $this->inner->json($system, $prompt, $schema);
        } catch (Throwable $e) {
            $erro = $e->getMessage();
            throw $e;
        } finally {
            $uso = $this->inner instanceof ReportaUso ? $this->inner->ultimoUso() : null;

            $this->auditoria->registrar([
                'avaliacao_id' => Context::get('avaliacao_id'),
                'etapa' => isset($schema['properties']['dimensoes']) ? 'consolidacao' : 'analise_documento',
                'driver' => $this->driver,
                'modelo' => $uso['modelo'] ?? $this->driver,
                'system_sha256' => hash('sha256', $system),
                'prompt' => $prompt,
                'resposta' => $resposta,
                'erro' => $erro,
                'duracao_ms' => (int) ((hrtime(true) - $inicio) / 1e6),
                'tokens_entrada' => $uso['tokens_entrada'] ?? null,
                'tokens_saida' => $uso['tokens_saida'] ?? null,
            ]);
        }
    }
}
