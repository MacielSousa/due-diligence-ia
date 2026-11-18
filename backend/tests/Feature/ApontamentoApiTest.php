<?php

namespace Tests\Feature;

use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApontamentoApiTest extends TestCase
{
    use RefreshDatabase;

    private Terceiro $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = Terceiro::create(['tipo' => 'pf', 'nome' => 'Ana Souza', 'documento' => '52998224725']);
    }

    public function test_adiciona_apontamento(): void
    {
        $this->postJson("/api/terceiros/{$this->ana->id}/apontamentos", ['tipo' => 'midia_negativa', 'ano' => 2024])
            ->assertCreated()
            ->assertJsonPath('data.tipo', 'midia_negativa')
            ->assertJsonPath('data.ano', 2024);

        $this->assertDatabaseHas('apontamentos', ['terceiro_id' => $this->ana->id, 'tipo' => 'midia_negativa']);
    }

    public function test_valida_tipo_e_ano(): void
    {
        $this->postJson("/api/terceiros/{$this->ana->id}/apontamentos", ['tipo' => 'xpto', 'ano' => (int) date('Y') + 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo', 'ano']);
    }

    public function test_remove_apontamento(): void
    {
        $apontamento = $this->ana->apontamentos()->create(['tipo' => 'sancao', 'ano' => 2020]);

        $this->deleteJson("/api/terceiros/{$this->ana->id}/apontamentos/{$apontamento->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Apontamento removido.');

        $this->assertDatabaseCount('apontamentos', 0);
    }

    public function test_nao_remove_apontamento_de_outro_terceiro(): void
    {
        $outro = Terceiro::create(['tipo' => 'pf', 'nome' => 'Outro', 'documento' => '11144477735']);
        $apontamento = $outro->apontamentos()->create(['tipo' => 'sancao', 'ano' => 2020]);

        // scopeBindings: o apontamento precisa pertencer ao terceiro da URL
        $this->deleteJson("/api/terceiros/{$this->ana->id}/apontamentos/{$apontamento->id}")->assertNotFound();

        $this->assertDatabaseCount('apontamentos', 1);
    }
}
