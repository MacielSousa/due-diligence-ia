<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Avaliacao\Models\Avaliacao;
use Modules\Avaliacao\Services\AvaliacaoService;

/** Avaliação roda em background: dezenas de documentos x chamadas ao LLM levam minutos. */
class ProcessarAvaliacaoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public readonly int $avaliacaoId)
    {
    }

    public function handle(AvaliacaoService $service): void
    {
        $avaliacao = Avaliacao::query()->find($this->avaliacaoId);

        if ($avaliacao) {
            $service->executar($avaliacao);
        }
    }
}
