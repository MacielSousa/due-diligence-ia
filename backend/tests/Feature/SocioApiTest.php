<?php

namespace Tests\Feature;

use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocioApiTest extends TestCase
{
    use RefreshDatabase;

    private Terceiro $ana;
    private Terceiro $carlos;
    private Terceiro $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = Terceiro::create(['tipo' => 'pf', 'nome' => 'Ana Souza', 'documento' => '52998224725']);
        $this->carlos = Terceiro::create(['tipo' => 'pf', 'nome' => 'Carlos Lima', 'documento' => '12345678909']);
        $this->empresa = Terceiro::create(['tipo' => 'pj', 'nome' => 'Empresa X', 'documento' => '11222333000181']);
    }

    private function adicionar(Terceiro $empresa, Terceiro $socio, float $participacao)
    {
        return $this->postJson("/api/terceiros/{$empresa->id}/socios", ['socio_id' => $socio->id, 'participacao' => $participacao]);
    }

    public function test_adiciona_socio(): void
    {
        $this->adicionar($this->empresa, $this->ana, 60)
            ->assertCreated()
            ->assertJsonPath('data.participacao', 60)
            ->assertJsonPath('data.socio.nome', 'Ana Souza');
    }

    public function test_aceita_exatamente_100_por_cento(): void
    {
        $this->adicionar($this->empresa, $this->ana, 60)->assertCreated();
        $this->adicionar($this->empresa, $this->carlos, 40)->assertCreated();
    }

    public function test_soma_das_participacoes_nao_pode_passar_de_100(): void
    {
        $this->adicionar($this->empresa, $this->ana, 60)->assertCreated();

        $this->adicionar($this->empresa, $this->carlos, 50)
            ->assertUnprocessable()
            ->assertJsonPath('errors.participacao.0', 'A soma das participações ficaria em 110% (máximo 100%).');
    }

    public function test_participacao_precisa_ser_positiva(): void
    {
        $this->adicionar($this->empresa, $this->ana, 0)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participacao']);
    }

    public function test_pessoa_fisica_nao_pode_ter_socios(): void
    {
        $this->adicionar($this->ana, $this->carlos, 10)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['socio_id']);
    }

    public function test_empresa_nao_pode_ser_socia_de_si_mesma(): void
    {
        $this->adicionar($this->empresa, $this->empresa, 10)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['socio_id']);
    }

    public function test_nao_duplica_o_mesmo_socio(): void
    {
        $this->adicionar($this->empresa, $this->ana, 10)->assertCreated();

        $this->adicionar($this->empresa, $this->ana, 10)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['socio_id']);
    }

    public function test_remove_socio(): void
    {
        $vinculo = $this->empresa->socios()->create(['socio_id' => $this->ana->id, 'participacao' => 10]);

        $this->deleteJson("/api/terceiros/{$this->empresa->id}/socios/{$vinculo->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Sócio removido do quadro societário.');

        $this->assertDatabaseCount('socios', 0);
        $this->assertDatabaseHas('terceiros', ['id' => $this->ana->id]); // só o vínculo some
    }
}
