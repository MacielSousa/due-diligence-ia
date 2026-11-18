<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

/** Calcula o score de vários terceiros e devolve um ranking. */
class AnalisadorDeRisco
{
    /** @var Terceiro[] */
    private array $terceiros = [];

    public function __construct(private int $anoReferencia) {}

    public function adicionar(Terceiro $terceiro): void
    {
        $this->terceiros[] = $terceiro;
    }

    /**
     * @return array<int, array{nome: string, score: float, nivel: string}> do maior para o menor score
     */
    public function relatorio(): array
    {
        $linhas = [];
        foreach ($this->terceiros as $terceiro) {
            $score = $terceiro->calcularRisco($this->anoReferencia);
            $linhas[] = [
                'nome' => $terceiro->getNome(),
                'score' => $score,
                'nivel' => self::classificar($score),
            ];
        }

        usort($linhas, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $linhas;
    }

    public static function classificar(float $score): string
    {
        return match (true) {
            $score >= 75 => 'CRITICO',
            $score >= 50 => 'ALTO',
            $score >= 25 => 'MEDIO',
            default => 'BAIXO',
        };
    }
}
