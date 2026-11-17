<?php

namespace Modules\IA\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\IA\Clients\OllamaLlmClient;
use PHPUnit\Framework\Attributes\Test;
use RiskEngine\Exceptions\LlmException;
use RiskEngine\Prompts;
use Tests\TestCase;

class OllamaLlmClientTest extends TestCase
{
    private function client(): OllamaLlmClient
    {
        return new OllamaLlmClient(app(HttpFactory::class), 'http://ollama:11434', 'llama3.2:3b', 30);
    }

    #[Test]
    public function envia_o_schema_em_format_e_decodifica_a_resposta(): void
    {
        Http::fake(['ollama:11434/api/chat' => Http::response([
            'message' => ['role' => 'assistant', 'content' => '{"resumo":"ok","achados":[],"sinais_eliminatorios":[]}'],
            'prompt_eval_count' => 120,
            'eval_count' => 30,
        ])]);

        $client = $this->client();
        $resposta = $client->json('sistema', 'prompt', Prompts::analyzeSchema());

        $this->assertSame('ok', $resposta['resumo']);
        $this->assertSame(['modelo' => 'llama3.2:3b', 'tokens_entrada' => 120, 'tokens_saida' => 30], $client->ultimoUso());

        Http::assertSent(fn (Request $r) => $r['model'] === 'llama3.2:3b'
            && $r['stream'] === false
            && $r['format'] === Prompts::analyzeSchema()
            && $r['options']['temperature'] === 0
            && $r['messages'][0] === ['role' => 'system', 'content' => 'sistema']);
    }

    #[Test]
    public function erro_http_vira_llm_exception(): void
    {
        Http::fake(['*' => Http::response(['error' => 'model "llama3.2:3b" not found'], 404)]);

        $this->expectException(LlmException::class);
        $this->expectExceptionMessage('not found');

        $this->client()->json('s', 'p', Prompts::assessSchema());
    }

    #[Test]
    public function conteudo_que_nao_e_json_vira_llm_exception(): void
    {
        Http::fake(['*' => Http::response(['message' => ['content' => 'desculpe, não sei']])]);

        $this->expectException(LlmException::class);

        $this->client()->json('s', 'p', Prompts::assessSchema());
    }
}
