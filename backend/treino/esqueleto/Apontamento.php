<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

class Apontamento
{
    // constante de classe com a tabela de pesos (array associativo)
    private const PESOS = [
        'sancao'               => 40,
        'processo_criminal'    => ???,
        'midia_negativa'       => ???,
        'processo_trabalhista' => ???,
    ];

    public function __construct(
        private string $tipo,
        private int $ano,
    ) {
        // se o tipo não existir na tabela -> lança exceção
        if (!array_key_exists(???, self::PESOS)) {
            throw new \InvalidArgumentException("Tipo de apontamento inválido: {$tipo}");
        }
    }

    public function peso(int $anoRef): int
    {
        $peso = self::PESOS[???];

        // mais de 5 anos -> vale metade (divisão inteira)
        if (???) {
            return intdiv($peso, 2);
        }

        return $peso;
    }
}