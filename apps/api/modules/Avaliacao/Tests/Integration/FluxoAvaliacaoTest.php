<?php

namespace Modules\Avaliacao\Tests\Integration;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Queue;
use Modules\Avaliacao\Jobs\ProcessarAvaliacaoJob;
use Modules\Avaliacao\Models\Avaliacao;
use Modules\Fornecedores\Models\Fornecedor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\IntegrationTestCase;

/**
 * Fluxo completo: API → fila → pipeline (driver heurístico) → MySQL + auditoria no MongoDB.
 */
class FluxoAvaliacaoTest extends IntegrationTestCase
{
    public static function cenarios(): array
    {
        return [
            'risco baixo' => ['11222333000181', 'baixo', false],
            'risco médio' => ['22333444000105', 'medio', true],
            'risco elevado (lista suja)' => ['33444555000177', 'elevado', true],
        ];
    }

    #[Test]
    #[DataProvider('cenarios')]
    public function classifica_as_construtoras_da_demonstracao(string $cnpj, string $nivel, bool $revisao): void
    {
        $this->seed(DatabaseSeeder::class);
        $fornecedor = Fornecedor::where('cnpj', $cnpj)->firstOrFail();

        // QUEUE_CONNECTION=sync nos testes: o job roda na hora.
        $id = $this->postJson("/api/fornecedores/{$fornecedor->id}/avaliacoes")
            ->assertAccepted()
            ->json('data.id');

        $this->getJson("/api/avaliacoes/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'concluida')
            ->assertJsonPath('data.nivel', $nivel)
            ->assertJsonPath('data.requer_revisao', $revisao)
            ->assertJsonStructure(['data' => [
                'resultado' => ['dimensoes', 'motivos', 'analises_documentos', 'recomendacao'],
                'documentos_usados' => [['id', 'tipo', 'sha256']],
            ]]);
    }

    #[Test]
    public function grava_auditoria_de_cada_chamada_ao_llm_no_mongodb(): void
    {
        $this->seed(DatabaseSeeder::class);
        $fornecedor = Fornecedor::where('cnpj', '11222333000181')->firstOrFail();

        $id = $this->postJson("/api/fornecedores/{$fornecedor->id}/avaliacoes")->json('data.id');

        $auditoria = $this->getJson("/api/avaliacoes/{$id}/auditoria")->assertOk()->json('data');

        // 8 documentos (etapa map) + 1 consolidação (etapa reduce)
        $this->assertCount(9, $auditoria);
        $this->assertSame('consolidacao', end($auditoria)['etapa']);
        $this->assertSame('heuristico', $auditoria[0]['driver']);
    }

    #[Test]
    public function enfileira_na_fila_de_avaliacoes_e_impede_duplicidade(): void
    {
        Queue::fake();
        $this->seed(DatabaseSeeder::class);
        $fornecedor = Fornecedor::firstOrFail();

        $this->postJson("/api/fornecedores/{$fornecedor->id}/avaliacoes")
            ->assertAccepted()
            ->assertJsonPath('data.status', 'pendente');

        Queue::assertPushedOn('avaliacoes', ProcessarAvaliacaoJob::class);

        $this->postJson("/api/fornecedores/{$fornecedor->id}/avaliacoes")->assertStatus(409);
    }

    #[Test]
    public function exige_documentos_para_avaliar(): void
    {
        $fornecedor = Fornecedor::factory()->create();

        $this->postJson("/api/fornecedores/{$fornecedor->id}/avaliacoes")->assertUnprocessable();
    }

    #[Test]
    public function comite_registra_a_decisao_final(): void
    {
        $this->seed(DatabaseSeeder::class);
        $fornecedor = Fornecedor::where('cnpj', '22333444000105')->firstOrFail();
        $id = $this->postJson("/api/fornecedores/{$fornecedor->id}/avaliacoes")->json('data.id');

        $this->postJson("/api/avaliacoes/{$id}/decisao", [
            'decisao' => 'aprovado_com_ressalvas',
            'decidido_por' => 'Comitê de Compliance',
            'justificativa' => 'Aprovado mediante seguro-garantia de 10% e retenção contratual.',
        ])->assertOk()->assertJsonPath('data.decisao', 'aprovado_com_ressalvas');

        $this->assertNotNull(Avaliacao::find($id)->decidido_em);
    }
}
