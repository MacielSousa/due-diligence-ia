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
     * @return array{scores: array<string, DimensionScore>, summary: string, recommendation: string, missing: list<string>, adjustments: list<string>}
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

        [$scores, $adjustments] = $this->applySeverityFloor($scores, $analyses);

        return [
            'scores' => $scores,
            'summary' => trim((string) ($raw['resumo_executivo'] ?? '')),
            'recommendation' => trim((string) ($raw['recomendacao'] ?? '')),
            'missing' => $missing,
            'adjustments' => $adjustments,
        ];
    }

    /**
     * Piso de nota: a nota de uma dimensão não pode ficar abaixo do pior achado VALIDADO nela.
     * A consolidação pode dar notas maiores, mas não pode "esquecer" um achado comprovado.
     * Caso real: llama3.2:3b deu 10 para "financeira" logo após extrair "Prejuízo líquido de R$ 45 milhões" (alta).
     *
     * @param array<string, DimensionScore> $scores
     * @param list<DocumentAnalysis> $analyses
     * @return array{0: array<string, DimensionScore>, 1: list<string>}
     */
    private function applySeverityFloor(array $scores, array $analyses): array
    {
        /** @var array<string, array{floor: int, severity: string, ids: list<string>}> $worst */
        $worst = [];
        foreach ($analyses as $analysis) {
            foreach ($analysis->findings as $finding) {
                $key = $finding->dimension->value;
                $floor = $finding->severity->scoreFloor();

                if ($floor > ($worst[$key]['floor'] ?? 0)) {
                    $worst[$key] = ['floor' => $floor, 'severity' => $finding->severity->value, 'ids' => [$finding->id]];
                } elseif ($floor > 0 && $floor === ($worst[$key]['floor'] ?? 0)) {
                    $worst[$key]['ids'][] = $finding->id;
                }
            }
        }

        $adjustments = [];
        foreach ($worst as $key => $info) {
            $current = $scores[$key];

            if ($current->score >= $info['floor']) {
                continue;
            }

            $adjustments[] = sprintf(
                'Nota de %s elevada de %d para %d: há achado validado de severidade %s (%s).',
                $key,
                $current->score,
                $info['floor'],
                $info['severity'],
                implode(', ', $info['ids']),
            );

            $scores[$key] = new DimensionScore(
                $current->dimension,
                $info['floor'],
                trim($current->justification . ' [Piso por severidade: nota original do modelo ' . $current->score . '.]'),
                array_values(array_unique([...$current->findingIds, ...$info['ids']])),
            );
        }

        return [$scores, $adjustments];
    }
}
