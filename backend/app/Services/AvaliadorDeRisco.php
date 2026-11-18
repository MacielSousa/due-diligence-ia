<?php

namespace App\Services;

use App\Domain\DueDiligence\AnalisadorDeRisco;
use App\Models\Terceiro;

/** Ponte entre a API e o AnalisadorDeRisco do desafio. */
class AvaliadorDeRisco
{
    /** @return array{score: float, nivel: string} */
    public function avaliar(Terceiro $terceiro, int $ano): array
    {
        $analisador = new AnalisadorDeRisco($ano);
        $analisador->adicionar((new MontadorDeTerceiros())->montar($terceiro));

        $linha = $analisador->relatorio()[0];

        return [
            'score' => (float) $linha['score'],
            'nivel' => $linha['nivel'],
        ];
    }
}
