<?php

declare(strict_types=1);

namespace RiskEngine;

use DateTimeImmutable;
use RiskEngine\Contracts\LlmClient;
use RiskEngine\Data\Assessment;
use RiskEngine\Data\Document;
use RiskEngine\Data\DocumentAnalysis;
use RiskEngine\Enums\DocumentType;
use RiskEngine\Enums\RiskLevel;
use RiskEngine\Pipeline\DimensionAssessor;
use RiskEngine\Pipeline\DocumentAnalyzer;
use RiskEngine\Scoring\RiskScorer;

/**
 * Orquestra o pipeline completo:
 * documentos → resumo/achados (LLM) → eliminatórios → notas por dimensão (LLM) → score (código) → parecer.
 */
final class RiskPipeline
{
    private readonly DocumentAnalyzer $analyzer;
    private readonly DimensionAssessor $assessor;

    public function __construct(
        LlmClient $llm,
        private readonly RiskScorer $scorer = new RiskScorer(),
    ) {
        $this->analyzer = new DocumentAnalyzer($llm);
        $this->assessor = new DimensionAssessor($llm);
    }

    /**
     * @param list<Document> $documents
     * @param (callable(Document, DocumentAnalysis): void)|null $onAnalyzed gancho para auditoria/progresso
     */
    public function run(
        string $supplierName,
        array $documents,
        ?DateTimeImmutable $now = null,
        ?callable $onAnalyzed = null,
    ): Assessment {
        $now ??= new DateTimeImmutable();

        $analyses = [];
        foreach ($documents as $document) {
            $analysis = $this->analyzer->analyze($document);
            $analyses[] = $analysis;
            if ($onAnalyzed) {
                $onAnalyzed($document, $analysis);
            }
        }

        $knockOuts = [];
        foreach ($analyses as $analysis) {
            array_push($knockOuts, ...$analysis->knockOuts);
        }

        $assessed = $this->assessor->assess($supplierName, $analyses);
        $classification = $this->scorer->classify($assessed['scores'], $knockOuts);

        [$missing, $expired] = $this->documentGaps($documents, $now);
        $confidence = $this->confidence($missing, $expired, $assessed['missing'], $analyses);

        $level = $classification['level'];
        $reasons = $classification['reasons'];

        if ($missing) {
            $reasons[] = 'Documentos obrigatórios ausentes: ' . implode(', ', $missing) . '.';
        }
        if ($expired) {
            $reasons[] = 'Documentos vencidos: ' . implode(', ', $expired) . '.';
        }

        $requiresReview = $level !== RiskLevel::Low || $confidence < $this->scorer->policy()->minConfidence;
        if ($requiresReview) {
            $reasons[] = $level === RiskLevel::Low
                ? sprintf('Confiança %.2f abaixo do mínimo: revisão humana obrigatória.', $confidence)
                : 'Risco ' . $level->label() . ': aprovação do comitê de compliance obrigatória.';
        }

        return new Assessment(
            level: $level,
            score: $classification['score'],
            confidence: $confidence,
            requiresHumanReview: $requiresReview,
            executiveSummary: $assessed['summary'],
            recommendation: $assessed['recommendation'],
            dimensionScores: $assessed['scores'],
            analyses: $analyses,
            knockOuts: $knockOuts,
            missingDocuments: $missing,
            expiredDocuments: $expired,
            reasons: $reasons,
        );
    }

    /**
     * @param list<Document> $documents
     * @return array{0: list<string>, 1: list<string>}
     */
    private function documentGaps(array $documents, DateTimeImmutable $now): array
    {
        $present = [];
        $expired = [];

        foreach ($documents as $document) {
            $present[$document->type->value] = true;
            if ($document->isExpired($now)) {
                $expired[] = $document->type->value;
            }
        }

        $missing = array_values(array_map(
            fn (DocumentType $t) => $t->value,
            array_filter(DocumentType::required(), fn (DocumentType $t) => ! isset($present[$t->value])),
        ));

        return [$missing, array_values(array_unique($expired))];
    }

    /**
     * @param list<string> $missingDocs
     * @param list<string> $expiredDocs
     * @param list<string> $unscoredDimensions
     * @param list<DocumentAnalysis> $analyses
     */
    private function confidence(array $missingDocs, array $expiredDocs, array $unscoredDimensions, array $analyses): float
    {
        $confidence = 1.0;
        $confidence -= 0.08 * count($missingDocs);
        $confidence -= 0.05 * count($expiredDocs);
        $confidence -= 0.10 * count($unscoredDimensions);

        // Muitos itens descartados por falta de evidência indicam resposta pouco confiável do modelo.
        $discarded = array_sum(array_map(fn (DocumentAnalysis $a) => $a->discardedItems, $analyses));
        $confidence -= min(0.2, 0.02 * $discarded);

        return max(0.0, round($confidence, 2));
    }
}
