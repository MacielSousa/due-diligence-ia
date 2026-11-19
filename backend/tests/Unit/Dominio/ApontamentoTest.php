<?php

namespace Tests\Unit\Dominio;

use App\Domain\DueDiligence\Apontamento;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Domínio puro: sem Laravel, sem banco — roda em milissegundos. */
class ApontamentoTest extends TestCase
{
    #[DataProvider('pesos')]
    public function test_peso_de_cada_tipo(string $tipo, int $peso): void
    {
        $this->assertSame($peso, (new Apontamento($tipo, 2026))->peso(2026));
    }

    public static function pesos(): array
    {
        return [
            'sanção' => ['sancao', 40],
            'processo criminal' => ['processo_criminal', 30],
            'mídia negativa' => ['midia_negativa', 15],
            'processo trabalhista' => ['processo_trabalhista', 10],
        ];
    }

    public function test_apontamento_com_mais_de_cinco_anos_vale_metade(): void
    {
        $antigo = new Apontamento('sancao', 2020);

        $this->assertSame(40, $antigo->peso(2025)); // 5 anos: ainda vale inteiro
        $this->assertSame(20, $antigo->peso(2026)); // 6 anos: deprecia
        $this->assertTrue($antigo->explicar(2026)['depreciado']);
    }

    public function test_tipo_invalido_e_recusado(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Apontamento('fofoca', 2026);
    }
}
