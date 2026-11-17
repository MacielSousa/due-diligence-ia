<?php

namespace Modules\Fornecedores\Tests\Unit;

use Modules\Fornecedores\Support\Cnpj;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CnpjTest extends TestCase
{
    public static function casos(): array
    {
        return [
            'válido com máscara' => ['11.222.333/0001-81', true],
            'válido sem máscara' => ['11222333000181', true],
            'dígito errado' => ['11222333000182', false],
            'repetido' => ['11111111111111', false],
            'curto' => ['1122233300018', false],
        ];
    }

    #[Test]
    #[DataProvider('casos')]
    public function valida_digitos_verificadores(string $cnpj, bool $esperado): void
    {
        $this->assertSame($esperado, Cnpj::valido($cnpj));
    }

    #[Test]
    public function gera_cnpjs_validos(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertTrue(Cnpj::valido(Cnpj::gerar()));
        }
    }
}
