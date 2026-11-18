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

    /**
     * Score com a explicação peso a peso (apontamentos, PEP, sócios × participação, teto).
     *
     * @return array<string, mixed>
     */
    public function explicar(Terceiro $terceiro, int $ano): array
    {
        $explicacao = (new MontadorDeTerceiros())->montar($terceiro)->explicarRisco($ano);
        $explicacao['nivel'] = AnalisadorDeRisco::classificar($explicacao['score']);
        $explicacao['faixas'] = ['BAIXO' => '0 a 24', 'MEDIO' => '25 a 49', 'ALTO' => '50 a 74', 'CRITICO' => '75 a 100'];

        return $explicacao;
    }
}
