<?php

namespace Modules\IA\Tests\Unit;

use Illuminate\Support\Facades\Context;
use Modules\Auditoria\Contracts\AuditoriaLlm;
use Modules\IA\Clients\AuditingLlmClient;
use PHPUnit\Framework\Attributes\Test;
use RiskEngine\Exceptions\LlmException;
use RiskEngine\Prompts;
use RiskEngine\Testing\FakeLlmClient;
use Tests\TestCase;

class AuditingLlmClientTest extends TestCase
{
    private AuditoriaLlm $auditoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditoria = new class implements AuditoriaLlm
        {
            public array $registros = [];

            public function registrar(array $registro): void
            {
                $this->registros[] = $registro;
            }

            public function daAvaliacao(int $avaliacaoId): array
            {
                return [];
            }
        };
    }

    #[Test]
    public function registra_chamada_com_a_avaliacao_do_contexto(): void
    {
        Context::add('avaliacao_id', 42);
        $client = new AuditingLlmClient(new FakeLlmClient([['ok' => true]]), $this->auditoria, 'fake');

        $resposta = $client->json('sistema', 'prompt', Prompts::assessSchema());

        $this->assertSame(['ok' => true], $resposta);
        $registro = $this->auditoria->registros[0];
        $this->assertSame(42, $registro['avaliacao_id']);
        $this->assertSame('consolidacao', $registro['etapa']);
        $this->assertSame(hash('sha256', 'sistema'), $registro['system_sha256']);
        $this->assertNull($registro['erro']);
    }

    #[Test]
    public function registra_erro_e_repropaga_a_excecao(): void
    {
        $client = new AuditingLlmClient(new FakeLlmClient([]), $this->auditoria, 'fake');

        try {
            $client->json('sistema', 'prompt', Prompts::analyzeSchema());
            $this->fail('Deveria lançar LlmException.');
        } catch (LlmException) {
        }

        $this->assertSame('analise_documento', $this->auditoria->registros[0]['etapa']);
        $this->assertStringContainsString('sem respostas', $this->auditoria->registros[0]['erro']);
    }
}
