<?php

namespace Tests\Feature;

use App\Models\Anexo;
use App\Models\AnaliseIa;
use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fluxo completo da análise por IA, com o Ollama simulado (Http::fake).
 * Em teste a fila é "sync": o POST já roda o Job 1 e o Job 2 em sequência.
 */
class AnaliseIaTest extends TestCase
{
    use RefreshDatabase;

    private Terceiro $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Anexo::DISCO);
        $this->empresa = Terceiro::create(['tipo' => 'pj', 'nome' => 'FM Soluções Tecnologicas', 'documento' => '45278245000113']);
    }

    private function anexar(string $fixture, string $categoria = 'outro'): void
    {
        $this->post("/api/terceiros/{$this->empresa->id}/anexos", [
            'categoria' => $categoria,
            'arquivo' => UploadedFile::fake()->createWithContent($fixture, file_get_contents(base_path("tests/Fixtures/{$fixture}"))),
        ], ['Accept' => 'application/json'])->assertCreated();
    }

    /** Ollama simulado: responde conforme o conteúdo do documento recebido. */
    private function fakeOllama(): void
    {
        Http::fake(['*/api/generate' => function ($request) {
            $prompt = $request['prompt'];
            $achados = str_contains($prompt, 'sanção VIGENTE')
                ? [['tipo' => 'sancao', 'ano' => 2026, 'envolvido' => 'FM', 'eliminatorio' => true,
                    'trecho' => 'Tipo de sanção: Impedimento de licitar e contratar com a Administração Pública']]
                : [];

            return Http::response(['response' => json_encode(['achados' => $achados]), 'prompt_eval_count' => 300, 'eval_count' => 40]);
        }]);
    }

    public function test_fluxo_completo_job1_le_documentos_e_job2_registra_score_explicado(): void
    {
        $this->fakeOllama();
        $this->anexar('consulta-sancoes.pdf');
        $this->anexar('certidao-negativa.pdf', 'certidao_negativa');

        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia", ['ano' => 2026])
            ->assertStatus(202)
            ->assertJsonPath('message', 'Análise iniciada: a IA vai ler 2 documento(s). Acompanhe o progresso.');

        $analise = AnaliseIa::firstOrFail();
        $resposta = $this->getJson("/api/terceiros/{$this->empresa->id}/analises-ia/{$analise->id}")->assertOk();

        $resposta->assertJsonPath('data.status', 'concluida')
            ->assertJsonPath('data.progresso.processados', 2)
            ->assertJsonPath('data.metricas.achados_validos', 1)
            ->assertJsonPath('data.resultado.score', 40)
            ->assertJsonPath('data.resultado.nivel', 'MEDIO')
            ->assertJsonPath('data.resultado.eliminatorio', true)
            ->assertJsonPath('data.resultado.recomendacao', 'reprovar') // eliminatório reprova mesmo com score médio
            ->assertJsonPath('data.resultado.explicacao.apontamentos.0.peso_base', 40)
            ->assertJsonPath('data.documentos.0.status', 'concluido')
            ->assertJsonPath('data.documentos.0.achados.0.valido', true)
            ->assertJsonPath('data.documentos.1.achados', []); // certidão negativa: nada

        $this->assertIsInt($resposta->json('data.metricas.duracao_ms'));
        $this->assertIsInt($resposta->json('data.documentos.0.duracao_ms'));

        // o achado virou apontamento com evidência e origem
        $this->assertDatabaseHas('apontamentos', [
            'terceiro_id' => $this->empresa->id, 'tipo' => 'sancao', 'ano' => 2026, 'origem' => 'ia',
            'trecho' => 'Tipo de sanção: Impedimento de licitar e contratar com a Administração Pública',
        ]);

        // o texto mandado ao modelo já saiu limpo (sem a marca d'água "DOCUMENTO FICTÍCIO")
        Http::assertSent(fn ($request) => ! str_contains($request['prompt'], 'FICTÍCIO'));
    }

    public function test_travas_descartam_achado_inventado_negativo_ou_com_tipo_invalido(): void
    {
        Http::fake(['*/api/generate' => Http::response(['response' => json_encode(['achados' => [
            ['tipo' => 'sancao', 'ano' => 2026, 'trecho' => 'Frase que não existe no documento', 'eliminatorio' => true],
            ['tipo' => 'sancao|processo_criminal', 'ano' => 2026, 'trecho' => 'Resultado: 1 sanção VIGENTE encontrada'],
            ['tipo' => 'processo_criminal', 'ano' => 1800, 'trecho' => 'Resultado: 1 sanção VIGENTE encontrada'],
        ]])])]);
        $this->anexar('consulta-sancoes.pdf');

        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia", ['ano' => 2026])->assertStatus(202);

        $doc = AnaliseIa::firstOrFail()->documentos()->first();
        $motivos = array_column($doc->achados, 'motivo');
        $this->assertSame([false, false, false], array_column($doc->achados, 'valido'));
        $this->assertStringContainsString('não existe no documento', $motivos[0]);
        $this->assertStringContainsString('Tipo inválido', $motivos[1]);
        $this->assertStringContainsString('Ano', $motivos[2]);
        $this->assertDatabaseMissing('apontamentos', ['origem' => 'ia']);
        $this->assertSame('aprovar', AnaliseIa::first()->recomendacao);
    }

    public function test_reanalise_troca_apontamentos_da_ia_e_preserva_os_manuais(): void
    {
        $this->fakeOllama();
        $this->anexar('consulta-sancoes.pdf');
        $this->empresa->apontamentos()->create(['tipo' => 'midia_negativa', 'ano' => 2025]); // manual

        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia", ['ano' => 2026])->assertStatus(202);
        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia", ['ano' => 2026])->assertStatus(202);

        $this->assertSame(1, $this->empresa->apontamentos()->where('origem', 'ia')->count());
        $this->assertSame(1, $this->empresa->apontamentos()->where('origem', 'manual')->count());
        $this->assertSame(55.0, AnaliseIa::latest('id')->first()->score); // 40 (sanção) + 15 (mídia manual)
    }

    public function test_ia_fora_do_ar_marca_a_analise_como_falha(): void
    {
        Http::fake(['*/api/generate' => fn () => throw new ConnectionException('recusada')]);
        $this->anexar('consulta-sancoes.pdf');

        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia")->assertStatus(202);

        $analise = AnaliseIa::firstOrFail();
        $this->assertSame('falhou', $analise->status);
        $this->assertStringContainsString('não respondeu', $analise->erro);
    }

    public function test_imagem_sem_ocr_e_ignorada_sem_derrubar_a_analise(): void
    {
        $this->fakeOllama();
        $this->post("/api/terceiros/{$this->empresa->id}/anexos", [
            'categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('foto.png', 10, 'image/png'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $this->anexar('consulta-sancoes.pdf');

        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia", ['ano' => 2026])->assertStatus(202);

        $analise = AnaliseIa::with('documentos')->firstOrFail();
        $this->assertSame('concluida', $analise->status);
        $this->assertSame('ignorado', $analise->documentos[0]->status);
        $this->assertStringContainsString('OCR', $analise->documentos[0]->erro);
    }

    public function test_command_ia_analisar_executa_na_hora_e_mostra_o_resultado(): void
    {
        $this->fakeOllama();
        $this->anexar('consulta-sancoes.pdf');

        $this->artisan('ia:analisar', ['terceiro' => $this->empresa->id, '--ano' => 2026, '--agora' => true])
            ->expectsOutputToContain('Score 40.0 · MEDIO · recomendação: reprovar · ACHADO ELIMINATÓRIO')
            ->assertSuccessful();

        $this->artisan('ia:analisar', ['terceiro' => 999])->assertFailed();
    }

    public function test_sem_documentos_retorna_422(): void
    {
        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'business_rule');
    }

    public function test_nao_permite_duas_analises_ao_mesmo_tempo(): void
    {
        Queue::fake(); // o job fica na fila: a análise continua "pendente"
        $this->anexar('consulta-sancoes.pdf');

        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia")->assertStatus(202);
        $this->postJson("/api/terceiros/{$this->empresa->id}/analises-ia")
            ->assertStatus(409)
            ->assertJsonPath('code', 'conflict');

        Queue::assertPushed(\App\Jobs\AnalisarDocumentosJob::class, 1);
    }
}
