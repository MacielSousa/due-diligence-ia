<?php

declare(strict_types=1);

namespace RiskEngine\Enums;

enum RiskLevel: string
{
    case Low = 'baixo';
    case Medium = 'medio';
    case High = 'elevado';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'BAIXO',
            self::Medium => 'MÉDIO',
            self::High => 'ELEVADO',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Medium => 1,
            self::High => 2,
        };
    }

    public static function max(self $a, self $b): self
    {
        return $a->rank() >= $b->rank() ? $a : $b;
    }
}
