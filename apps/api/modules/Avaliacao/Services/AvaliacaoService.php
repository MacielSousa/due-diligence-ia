<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Context;
use Modules\Avaliacao\Enums\Decisao;
use Modules\Avaliacao\Enums\StatusAvaliacao;
use Modules\Avaliacao\Exceptions\AvaliacaoException;
use Modules\Avaliacao\Jobs\ProcessarAvaliacaoJob;
use Modules\Avaliacao\Models\Avaliacao;
use Modules\Documentos\Services\DocumentoService;
use Modules\Fornecedores\Models\Fornecedor;
use RiskEngine\Contracts\LlmClient;
use RiskEngine\Data\Document;
use RiskEngine\RiskPipeline;
use RiskEngine\Scoring\RiskScorer;
use RiskEngine\Scoring\ScoringPolicy;
use Throwable;

final class AvaliacaoService
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly LlmClient $llm,
    ) {
    }

    /** Cria a avaliação e a envia para a fila (Redis). */
    public function solicitar(Fornecedor $fornecedor): Avaliacao
    {
        $emAndamento = Avaliacao::query()
            ->where('fornecedor_id', $fornecedor->id)
            ->whereIn('status', [StatusAvaliacao::Pendente, StatusAvaliacao::Processando])
            ->exists();

        if ($emAndamento) {
            throw new AvaliacaoException('Já existe uma avaliação em andamento para este fornecedor.', 409);
        }

        if ($this->documentos->comConteudo($fornecedor->id)->isEmpty()) {
            throw new AvaliacaoException('Envie ao menos um documento antes de avaliar o fornecedor.', 422);
        }

        $avaliacao = Avaliacao::create(['fornecedor_id' => $fornecedor->id]);

        ProcessarAvaliacaoJob::dispatch($avaliacao->id)->onQueue(config('avaliacao.fila'));

        return $avaliacao->refresh();
    }

    /** Executa o pipeline (chamado pelo job). */
    public function executar(Avaliacao $avaliacao): Avaliacao
    {
        Context::add('avaliacao_id', $avaliacao->id);

        $avaliacao->update(['status' => StatusAvaliacao::Processando, 'iniciada_em' => now(), 'erro' => null]);

        try {
            $itens = $this->documentos->comConteudo($avaliacao->fornecedor_id);

            $documentos = $itens->map(fn (array $item) => new Document(
                id: 'doc-' . $item['documento']->id,
                type: $item['documento']->tipo,
                title: $item['documento']->titulo,
                content: $item['texto'],
                issuedAt: $item['documento']->emitido_em
                    ? DateTimeImmutable::createFromInterface($item['documento']->emitido_em)
                    : null,
            ))->all();

            $pipeline = new RiskPipeline(
                $this->llm,
                new RiskScorer(ScoringPolicy::fromArray(config('avaliacao.politica'))),
            );

            $resultado = $pipeline->run(
                $avaliacao->fornecedor->razao_social,
                $documentos,
                DateTimeImmutable::createFromInterface(now()),
            );

            $avaliacao->update([
                'status' => StatusAvaliacao::Concluida,
                'nivel' => $resultado->level,
                'score' => round($resultado->score, 1),
                'confianca' => $resultado->confidence,
                'requer_revisao' => $resultado->requiresHumanReview,
                'resultado' => $resultado->toArray(),
                'documentos_usados' => $itens->map(fn (array $i) => [
                    'id' => $i['documento']->id,
                    'tipo' => $i['documento']->tipo->value,
                    'sha256' => $i['documento']->sha256,
                ])->all(),
                'concluida_em' => now(),
            ]);
        } catch (Throwable $e) {
            $avaliacao->update([
                'status' => StatusAvaliacao::Falhou,
                'erro' => $e->getMessage(),
                'concluida_em' => now(),
            ]);

            throw $e;
        } finally {
            Context::forget('avaliacao_id');
        }

        return $avaliacao;
    }

    /** Registra a decisão do comitê (humano no loop). */
    public function decidir(Avaliacao $avaliacao, Decisao $decisao, string $por, string $justificativa): Avaliacao
    {
        if ($avaliacao->status !== StatusAvaliacao::Concluida) {
            throw new AvaliacaoException('Só é possível decidir sobre avaliações concluídas.', 422);
        }

        $avaliacao->update([
            'decisao' => $decisao,
            'decidido_por' => $por,
            'justificativa_decisao' => $justificativa,
            'decidido_em' => now(),
        ]);

        return $avaliacao;
    }
}
