<?php

declare(strict_types=1);

namespace RiskEngine\Scoring;

use InvalidArgumentException;
use RiskEngine\Enums\Dimension;

/** Parâmetros de negócio do score — configuráveis pelo time de compliance. */
final readonly class ScoringPolicy
{
    /**
     * @param array<string, float> $weights pesos por dimensão (somam 1.0)
     */
    public function __construct(
        public array $weights = [
            'financeira' => 0.30,
            'tecnica' => 0.25,
            'trabalhista' => 0.20,
            'juridica' => 0.15,
            'reputacional' => 0.10,
        ],
        public float $mediumThreshold = 35.0,
        public float $highThreshold = 65.0,
        public int $criticalDimension = 80,
        public float $minConfidence = 0.7,
    ) {
        foreach (Dimension::cases() as $dimension) {
            if (! isset($weights[$dimension->value])) {
                throw new InvalidArgumentException("Peso ausente para a dimensão {$dimension->value}.");
            }
        }

        if (abs(array_sum($weights) - 1.0) > 0.001) {
            throw new InvalidArgumentException('A soma dos pesos deve ser 1.0.');
        }
    }

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        $default = new self();

        return new self(
            weights: array_map('floatval', $config['weights'] ?? $default->weights),
            mediumThreshold: (float) ($config['medium_threshold'] ?? $default->mediumThreshold),
            highThreshold: (float) ($config['high_threshold'] ?? $default->highThreshold),
            criticalDimension: (int) ($config['critical_dimension'] ?? $default->criticalDimension),
            minConfidence: (float) ($config['min_confidence'] ?? $default->minConfidence),
        );
    }
}
