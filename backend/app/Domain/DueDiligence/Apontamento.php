<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

use InvalidArgumentException;

/** Um fato negativo encontrado na diligência (sanção, processo, mídia...). */
final class Apontamento
{
    /** Peso de cada tipo de apontamento no score. */
    private const PESOS = [
        'sancao' => 40,
        'processo_criminal' => 30,
        'midia_negativa' => 15,
        'processo_trabalhista' => 10,
    ];

    /** Apontamentos com mais de N anos valem metade. */
    private const ANOS_ATE_DEPRECIAR = 5;

    public function __construct(
        private string $tipo,
        private int $ano,
    ) {
        if (! array_key_exists($tipo, self::PESOS)) {
            throw new InvalidArgumentException("Tipo de apontamento inválido: {$tipo}");
        }
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function peso(int $anoReferencia): int
    {
        return $this->explicar($anoReferencia)['peso'];
    }

    /**
     * Como o peso foi calculado (para mostrar o "porquê" do score).
     *
     * @return array{tipo: string, ano: int, peso_base: int, idade: int, depreciado: bool, peso: int}
     */
    public function explicar(int $anoReferencia): array
    {
        $pesoBase = self::PESOS[$this->tipo];
        $idade = $anoReferencia - $this->ano;
        $depreciado = $idade > self::ANOS_ATE_DEPRECIAR;

        return [
            'tipo' => $this->tipo,
            'ano' => $this->ano,
            'peso_base' => $pesoBase,
            'idade' => $idade,
            'depreciado' => $depreciado,
            'peso' => $depreciado ? intdiv($pesoBase, 2) : $pesoBase,
        ];
    }
}
