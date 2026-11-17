<?php

declare(strict_types=1);

namespace RiskEngine\Scoring;

use RiskEngine\Data\DimensionScore;
use RiskEngine\Data\KnockOutSignal;
use RiskEngine\Enums\RiskLevel;

/** Classificação final 100% determinística: o LLM nunca decide o nível sozinho. */
final class RiskScorer
{
    public function __construct(private readonly ScoringPolicy $policy = new ScoringPolicy())
    {
    }

    /**
     * @param array<string, DimensionScore> $scores
     * @param list<KnockOutSignal> $knockOuts
     * @return array{level: RiskLevel, score: float, reasons: list<string>}
     */
    public function classify(array $scores, array $knockOuts): array
    {
        $score = 0.0;
        foreach ($scores as $key => $dimensionScore) {
            $score += $dimensionScore->score * ($this->policy->weights[$key] ?? 0.0);
        }

        $reasons = [];

        $level = match (true) {
            $score >= $this->policy->highThreshold => RiskLevel::High,
            $score >= $this->policy->mediumThreshold => RiskLevel::Medium,
            default => RiskLevel::Low,
        };
        $reasons[] = sprintf('Score ponderado %.1f → %s.', $score, $level->label());

        // Uma dimensão crítica não pode ser "diluída" pelas outras.
        foreach ($scores as $dimensionScore) {
            if ($dimensionScore->score >= $this->policy->criticalDimension) {
                $before = $level;
                $level = RiskLevel::max($level, RiskLevel::Medium);
                if ($before !== $level) {
                    $reasons[] = sprintf(
                        'Dimensão %s com nota %d (≥ %d) eleva para no mínimo MÉDIO.',
                        $dimensionScore->dimension->value,
                        $dimensionScore->score,
                        $this->policy->criticalDimension,
                    );
                }
            }
        }

        // Regras eliminatórias: qualquer sinal confirmado → ELEVADO.
        foreach ($knockOuts as $knockOut) {
            $level = RiskLevel::High;
            $reasons[] = 'Eliminatório: ' . $knockOut->type->label() . '.';
        }

        return ['level' => $level, 'score' => $score, 'reasons' => $reasons];
    }

    public function policy(): ScoringPolicy
    {
        return $this->policy;
    }
}
