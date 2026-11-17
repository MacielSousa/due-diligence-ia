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

    /**
     * A evidência é uma negação? (ex.: "não consta registro", "nada consta", "certidão negativa").
     * Usado para sinais eliminatórios: o trecho existir não basta, ele precisa AFIRMAR o fato.
     * Caso real: um Llama 3B marcou "Lista Suja" citando "não consta registro para o CNPJ".
     */
    public function isNegation(string $evidence): bool
    {
        return (bool) preg_match(
            '/\b(n[ãa]o\s+consta[m]?|nada\s+consta|nenhum[a]?\s+(registro|ocorr[êe]ncia|resultado)'
            . '|sem\s+(registro|ocorr[êe]ncia|pend[êe]ncia)s?|inexist\w*|certid[ãa]o\s+negativa'
            . '|n[ãa]o\s+(h[áa]|possui|foi\s+encontrad\w*|foram\s+encontrad\w*|figura))/iu',
            $this->normalize($evidence),
        );
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['“', '”', '"', '‘', '’', "'", '…'], '', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text, " \t\n\r\0\x0B.;:,");
    }
}
