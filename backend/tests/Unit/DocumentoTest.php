<?php

namespace Tests\Unit;

use App\Domain\DueDiligence\Documento;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DocumentoTest extends TestCase
{
    #[DataProvider('cpfs')]
    public function test_valida_cpf(string $cpf, bool $esperado): void
    {
        $this->assertSame($esperado, Documento::cpfValido($cpf));
    }

    public static function cpfs(): array
    {
        return [
            'válido sem máscara' => ['52998224725', true],
            'válido com máscara' => ['529.982.247-25', true],
            'DV errado' => ['529.982.247-24', false],
            'todos iguais' => ['111.111.111-11', false],
            'tamanho errado' => ['5299822472', false],
        ];
    }

    #[DataProvider('cnpjs')]
    public function test_valida_cnpj(string $cnpj, bool $esperado): void
    {
        $this->assertSame($esperado, Documento::cnpjValido($cnpj));
    }

    public static function cnpjs(): array
    {
        return [
            'válido sem máscara' => ['11222333000181', true],
            'válido com máscara' => ['11.222.333/0001-81', true],
            'DV errado' => ['11.222.333/0001-80', false],
            'todos iguais' => ['00.000.000/0000-00', false],
            'tamanho errado' => ['1122233300018', false],
        ];
    }
}
