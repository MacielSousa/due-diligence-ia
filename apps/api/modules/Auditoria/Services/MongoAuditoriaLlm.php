<?php

declare(strict_types=1);

namespace Modules\Auditoria\Services;

use Illuminate\Support\Facades\Log;
use Modules\Auditoria\Contracts\AuditoriaLlm;
use Modules\Auditoria\Models\ChamadaLlm;
use Throwable;

final class MongoAuditoriaLlm implements AuditoriaLlm
{
    public function registrar(array $registro): void
    {
        try {
            ChamadaLlm::create($registro);
        } catch (Throwable $e) {
            // Falha de auditoria não derruba a avaliação, mas fica no log.
            Log::error('Falha ao gravar auditoria do LLM', ['erro' => $e->getMessage()]);
        }
    }

    public function daAvaliacao(int $avaliacaoId): array
    {
        return ChamadaLlm::query()
            ->where('avaliacao_id', $avaliacaoId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ChamadaLlm $c) => [
                'id' => (string) $c->getKey(),
                'etapa' => $c->etapa,
                'driver' => $c->driver,
                'modelo' => $c->modelo,
                'duracao_ms' => $c->duracao_ms,
                'tokens_entrada' => $c->tokens_entrada,
                'tokens_saida' => $c->tokens_saida,
                'erro' => $c->erro,
                'prompt' => $c->prompt,
                'resposta' => $c->resposta,
                'criado_em' => $c->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
