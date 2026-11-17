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

    #[Test]
    public function rejeita_evidencia_curta_demais(): void
    {
        $this->assertFalse((new EvidenceVerifier())->exists('em', self::CONTENT));
    }
}
