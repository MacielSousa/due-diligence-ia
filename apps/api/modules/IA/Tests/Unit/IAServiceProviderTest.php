<?php

namespace Modules\IA\Tests\Unit;

use InvalidArgumentException;
use Modules\IA\Clients\AuditingLlmClient;
use PHPUnit\Framework\Attributes\Test;
use RiskEngine\Contracts\LlmClient;
use Tests\TestCase;

class IAServiceProviderTest extends TestCase
{
    #[Test]
    public function driver_heuristico_e_sempre_auditado(): void
    {
        config(['ia.driver' => 'heuristico']);

        $this->assertInstanceOf(AuditingLlmClient::class, app(LlmClient::class));
    }

    #[Test]
    public function driver_anthropic_exige_chave(): void
    {
        config(['ia.driver' => 'anthropic', 'ia.anthropic.api_key' => null]);

        $this->expectException(InvalidArgumentException::class);
        app(LlmClient::class);
    }

    #[Test]
    public function driver_anthropic_com_chave_monta_o_cliente(): void
    {
        config(['ia.driver' => 'anthropic', 'ia.anthropic.api_key' => 'sk-ant-teste']);

        $this->assertInstanceOf(AuditingLlmClient::class, app(LlmClient::class));
    }
}
