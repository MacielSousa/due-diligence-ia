<?php

declare(strict_types=1);

namespace RiskEngine\Pipeline;

/**
 * Defesa contra alucinação: confirma em código que a "evidência" citada pelo LLM
 * existe literalmente no documento (ignorando caixa, aspas, espaços repetidos e pontuação nas bordas).
 */
final class EvidenceVerifier
{
    public function __construct(private readonly int $minLength = 8)
    {
    }

    public function exists(string $evidence, string $content): bool
    {
        $needle = $this->normalize($evidence);

        if (mb_strlen($needle) < $this->minLength) {
            return false;
        }

        return str_contains($this->normalize($content), $needle);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['“', '”', '"', '‘', '’', "'", '…'], '', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text, " \t\n\r\0\x0B.;:,");
    }
}
