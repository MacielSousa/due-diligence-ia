<?php

declare(strict_types=1);

namespace RiskEngine\Pipeline;

use RiskEngine\Contracts\LlmClient;
use RiskEngine\Data\DimensionScore;
use RiskEngine\Data\DocumentAnalysis;
use RiskEngine\Enums\Dimension;
use RiskEngine\Prompts;

/** Etapa "reduce": consolida os achados e atribui uma nota de risco por dimensão. */
final class DimensionAssessor
{
    public function __construct(private readonly LlmClient $llm)
    {
    }

    /**
     * @param list<DocumentAnalysis> $analyses
     * @return array{scores: array<string, DimensionScore>, summary: string, recommendation: string, missing: list<string>}
     */
    public function assess(string $supplierName, array $analyses): array
    {
        $raw = $this->llm->json(
            Prompts::assessSystem(),
            Prompts::assessUser($supplierName, $analyses),
            Prompts::assessSchema(),
        );

        $knownIds = [];
        foreach ($analyses as $analysis) {
            foreach ($analysis->findings as $finding) {
                $knownIds[$finding->id] = true;
            }
        }

        $scores = [];
        foreach ((array) ($raw['dimensoes'] ?? []) as $item) {
            $dimension = Dimension::tryFrom((string) ($item['dimensao'] ?? ''));

            if (! $dimension || isset($scores[$dimension->value])) {
                continue;
            }

            $scores[$dimension->value] = new DimensionScore(
                dimension: $dimension,
                score: max(0, min(100, (int) ($item['nota'] ?? 50))),
                justification: trim((string) ($item['justificativa'] ?? '')),
                // Só aceita IDs de achados que realmente existem.
                findingIds: array_values(array_filter(
                    array_map('strval', (array) ($item['achados'] ?? [])),
                    fn (string $id) => isset($knownIds[$id]),
                )),
            );
        }

        // Dimensão que o LLM não avaliou recebe nota neutra e reduz a confiança.
        $missing = [];
        foreach (Dimension::cases() as $dimension) {
            if (! isset($scores[$dimension->value])) {
                $missing[] = $dimension->value;
                $scores[$dimension->value] = new DimensionScore(
                    $dimension,
                    50,
                    'Dimensão não avaliada pelo modelo; nota neutra atribuída por regra.',
                );
            }
        }

        return [
            'scores' => $scores,
            'summary' => trim((string) ($raw['resumo_executivo'] ?? '')),
            'recommendation' => trim((string) ($raw['recomendacao'] ?? '')),
            'missing' => $missing,
        ];
    }
}
