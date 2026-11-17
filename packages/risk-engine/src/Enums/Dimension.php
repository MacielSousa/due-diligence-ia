<?php

declare(strict_types=1);

namespace RiskEngine\Enums;

/** Dimensões de risco avaliadas na contratação de um fornecedor. */
enum Dimension: string
{
    case Financial = 'financeira';
    case Technical = 'tecnica';
    case Labor = 'trabalhista';
    case Legal = 'juridica';
    case Reputational = 'reputacional';

    public function label(): string
    {
        return match ($this) {
            self::Financial => 'Financeira (risco de calote)',
            self::Technical => 'Técnica (capacidade de entrega)',
            self::Labor => 'Trabalhista / ESG',
            self::Legal => 'Jurídica / idoneidade',
            self::Reputational => 'Reputacional',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $d) => $d->value, self::cases());
    }
}
