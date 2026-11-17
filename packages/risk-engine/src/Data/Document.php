<?php

declare(strict_types=1);

namespace RiskEngine\Data;

use DateTimeImmutable;
use RiskEngine\Enums\DocumentType;

final readonly class Document
{
    public function __construct(
        public string $id,
        public DocumentType $type,
        public string $title,
        public string $content,
        public ?DateTimeImmutable $issuedAt = null,
    ) {
    }

    /** Conteúdo delimitado para o prompt; remove delimitadores injetados no próprio texto. */
    public function contentWrapped(): string
    {
        $clean = str_ireplace(['<documento>', '</documento>'], '', $this->content);

        return "<documento>\n{$clean}\n</documento>";
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        $days = $this->type->validityDays();

        if ($days === null || $this->issuedAt === null) {
            return false;
        }

        return $this->issuedAt->modify("+{$days} days") < $now;
    }
}
