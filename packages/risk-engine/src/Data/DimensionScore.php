<?php

declare(strict_types=1);

namespace RiskEngine\Data;

use RiskEngine\Enums\Dimension;

final readonly class DimensionScore
{
    /** @param list<string> $findingIds */
    public function __construct(
        public Dimension $dimension,
        public int $score,
        public string $justification,
        public array $findingIds = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'dimensao' => $this->dimension->value,
            'rotulo' => $this->dimension->label(),
            'nota' => $this->score,
            'justificativa' => $this->justification,
            'achados' => $this->findingIds,
        ];
    }
}
