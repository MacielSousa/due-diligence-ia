<?php

declare(strict_types=1);

namespace RiskEngine\Data;

use RiskEngine\Enums\KnockOut;

final readonly class KnockOutSignal
{
    public function __construct(
        public KnockOut $type,
        public string $documentId,
        public string $evidence,
    ) {
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'tipo' => $this->type->value,
            'descricao' => $this->type->label(),
            'documento_id' => $this->documentId,
            'evidencia' => $this->evidence,
        ];
    }
}
