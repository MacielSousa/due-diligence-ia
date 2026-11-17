<?php

declare(strict_types=1);

namespace RiskEngine\Data;

use RiskEngine\Enums\RiskLevel;

/** Parecer final de risco de contratação. */
final readonly class Assessment
{
    /**
     * @param array<string, DimensionScore> $dimensionScores indexado pelo valor da dimensão
     * @param list<DocumentAnalysis> $analyses
     * @param list<KnockOutSignal> $knockOuts
     * @param list<string> $missingDocuments
     * @param list<string> $expiredDocuments
     * @param list<string> $reasons motivos que levaram à classificação
     */
    public function __construct(
        public RiskLevel $level,
        public float $score,
        public float $confidence,
        public bool $requiresHumanReview,
        public string $executiveSummary,
        public string $recommendation,
        public array $dimensionScores,
        public array $analyses,
        public array $knockOuts,
        public array $missingDocuments,
        public array $expiredDocuments,
        public array $reasons,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'classificacao' => $this->level->value,
            'classificacao_rotulo' => $this->level->label(),
            'score' => round($this->score, 1),
            'confianca' => round($this->confidence, 2),
            'requer_revisao_humana' => $this->requiresHumanReview,
            'resumo_executivo' => $this->executiveSummary,
            'recomendacao' => $this->recommendation,
            'motivos' => $this->reasons,
            'dimensoes' => array_values(array_map(fn (DimensionScore $d) => $d->toArray(), $this->dimensionScores)),
            'sinais_eliminatorios' => array_map(fn (KnockOutSignal $k) => $k->toArray(), $this->knockOuts),
            'documentos_faltantes' => $this->missingDocuments,
            'documentos_vencidos' => $this->expiredDocuments,
            'analises_documentos' => array_map(fn (DocumentAnalysis $a) => $a->toArray(), $this->analyses),
        ];
    }
}
