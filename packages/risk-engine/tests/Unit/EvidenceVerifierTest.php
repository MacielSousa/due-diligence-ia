<?php

declare(strict_types=1);

namespace RiskEngine\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RiskEngine\Pipeline\EvidenceVerifier;

final class EvidenceVerifierTest extends TestCase
{
    private const CONTENT = "A empresa encontra-se em RECUPERAÇÃO JUDICIAL\n  desde   março de 2025.";

    #[Test]
    public function aceita_trecho_literal_ignorando_caixa_e_espacos(): void
    {
        $this->assertTrue((new EvidenceVerifier())->exists('recuperação judicial desde março', self::CONTENT));
    }

    #[Test]
    public function rejeita_trecho_inventado(): void
    {
        $this->assertFalse((new EvidenceVerifier())->exists('falência decretada em 2025', self::CONTENT));
    }

    public static function negacoes(): array
    {
        return [
            'não consta (caso real do Llama)' => ['Resultado: não consta registro para o CNPJ consultado', true],
            'nada consta' => ['Nada consta em nome do contribuinte', true],
            'nenhum registro' => ['Nenhum registro encontrado para o CNPJ consultado', true],
            'certidão negativa' => ['Certidão negativa de débitos trabalhistas', true],
            'não foi encontrado' => ['O CNPJ não foi encontrado no cadastro', true],
            'afirmação: consta' => ['O empregador CONSTA no Cadastro de Empregadores', false],
            'afirmação: incluído' => ['empregador incluído após fiscalização que resgatou 23 trabalhadores', false],
            'afirmação: falência' => ['Falência decretada pela 2ª Vara Empresarial', false],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('negacoes')]
    public function detecta_evidencia_que_nega_o_fato(string $evidencia, bool $esperado): void
    {
        $this->assertSame($esperado, (new EvidenceVerifier())->isNegation($evidencia));
    }

    #[Test]
    public function rejeita_evidencia_curta_demais(): void
    {
        $this->assertFalse((new EvidenceVerifier())->exists('em', self::CONTENT));
    }
}
