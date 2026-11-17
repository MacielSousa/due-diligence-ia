<?php

declare(strict_types=1);

namespace RiskEngine\Data;

use RiskEngine\Enums\Dimension;
use RiskEngine\Enums\Severity;

/** Um achado extraído de um documento, sempre acompanhado de evidência literal verificada. */
final readonly class Finding
{
    public function __construct(
        public string $id,
        public string $documentId,
        public Dimension $dimension,
        public Severity $severity,
        public string $description,
        public string $evidence,
    ) {
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'documento_id' => $this->documentId,
            'dimensao' => $this->dimension->value,
            'severidade' => $this->severity->value,
            'descricao' => $this->description,
            'evidencia' => $this->evidence,
        ];
    }
}
