<?php

declare(strict_types=1);

namespace RiskEngine\Pipeline;

use RiskEngine\Contracts\LlmClient;
use RiskEngine\Data\Document;
use RiskEngine\Data\DocumentAnalysis;
use RiskEngine\Data\Finding;
use RiskEngine\Data\KnockOutSignal;
use RiskEngine\Enums\Dimension;
use RiskEngine\Enums\KnockOut;
use RiskEngine\Enums\Severity;
use RiskEngine\Prompts;

/** Etapa "map": resume um documento e extrai achados com evidência verificada. */
final class DocumentAnalyzer
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly EvidenceVerifier $verifier = new EvidenceVerifier(),
    ) {
    }

    public function analyze(Document $document): DocumentAnalysis
    {
        $raw = $this->llm->json(
            Prompts::analyzeSystem(),
            Prompts::analyzeUser($document),
            Prompts::analyzeSchema(),
        );

        $discarded = 0;
        $findings = [];

        foreach ((array) ($raw['achados'] ?? []) as $i => $item) {
            $dimension = Dimension::tryFrom((string) ($item['dimensao'] ?? ''));
            $severity = Severity::tryFrom((string) ($item['severidade'] ?? ''));
            $evidence = trim((string) ($item['evidencia'] ?? ''));

            if (! $dimension || ! $severity || ! $this->verifier->exists($evidence, $document->content)) {
                $discarded++;
                continue;
            }

            $findings[] = new Finding(
                id: sprintf('%s-a%d', $document->id, $i + 1),
                documentId: $document->id,
                dimension: $dimension,
                severity: $severity,
                description: trim((string) ($item['descricao'] ?? '')),
                evidence: $evidence,
            );
        }

        $knockOuts = [];

        foreach ((array) ($raw['sinais_eliminatorios'] ?? []) as $item) {
            $type = KnockOut::tryFrom((string) ($item['tipo'] ?? ''));
            $evidence = trim((string) ($item['evidencia'] ?? ''));

            if (! $type || ! $this->verifier->exists($evidence, $document->content)) {
                $discarded++;
                continue;
            }

            $knockOuts[] = new KnockOutSignal($type, $document->id, $evidence);
        }

        return new DocumentAnalysis(
            documentId: $document->id,
            summary: trim((string) ($raw['resumo'] ?? '')),
            findings: $findings,
            knockOuts: $knockOuts,
            discardedItems: $discarded,
        );
    }
}
