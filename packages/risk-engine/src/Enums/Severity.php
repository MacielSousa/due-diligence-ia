<?php

declare(strict_types=1);

namespace RiskEngine\Enums;

enum Severity: string
{
    case Low = 'baixa';
    case Medium = 'media';
    case High = 'alta';
    case Critical = 'critica';

    /** Peso usado pelo avaliador heurístico e para ordenar achados. */
    public function points(): int
    {
        return match ($this) {
            self::Low => 10,
            self::Medium => 35,
            self::High => 65,
            self::Critical => 90,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
