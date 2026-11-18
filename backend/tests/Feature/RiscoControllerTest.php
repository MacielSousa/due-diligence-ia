<?php

namespace Tests\Feature;

use App\Models\Terceiro;
use App\Services\AvaliadorDeRisco;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testa só o CONTROLLER de risco, trocando o AvaliadorDeRisco por um dublê (mock).
 * Assim estes testes passam com ou sem o domínio do desafio implementado.
 */
class RiscoControllerTest extends TestCase
{
    use RefreshDatabase;

    private Terceiro $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = Terceiro::create(['tipo' => 'pf', 'nome' => 'Ana Souza', 'documento' => '52998224725']);
    }

    public function test_devolve_score_e_nivel_do_avaliador(): void
    {
        $this->mock(AvaliadorDeRisco::class)
            ->shouldReceive('avaliar')->once()
            ->withArgs(fn ($terceiro, $ano) => $terceiro->is($this->ana) && $ano === 2030)
            ->andReturn(['score' => 42.5, 'nivel' => 'MEDIO']);

        $this->getJson("/api/terceiros/{$this->ana->id}/risco?ano=2030")
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Risco calculado.',
                'data' => ['terceiro_id' => $this->ana->id, 'nome' => 'Ana Souza', 'ano' => 2030, 'score' => 42.5, 'nivel' => 'MEDIO'],
            ]);
    }

    public function test_regra_de_negocio_violada_vira_422(): void
    {
        $this->mock(AvaliadorDeRisco::class)
            ->shouldReceive('avaliar')->andThrow(new \LogicException('Ciclo societário detectado em X'));

        $this->getJson("/api/terceiros/{$this->ana->id}/risco")
            ->assertUnprocessable()
            ->assertExactJson(['success' => false, 'message' => 'Ciclo societário detectado em X', 'code' => 'business_rule']);
    }

    public function test_dominio_incompleto_vira_501(): void
    {
        $this->mock(AvaliadorDeRisco::class)
            ->shouldReceive('avaliar')->andThrow(new \Error('Call to undefined method Foo::bar()'));

        $this->getJson("/api/terceiros/{$this->ana->id}/risco")
            ->assertStatus(501)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'domain_incomplete')
            ->assertJsonPath('detail', 'Call to undefined method Foo::bar()');
    }

    public function test_relatorio_ordena_por_score_e_isola_erro_por_linha(): void
    {
        $carlos = Terceiro::create(['tipo' => 'pf', 'nome' => 'Carlos Lima', 'documento' => '12345678909']);
        $bruno = Terceiro::create(['tipo' => 'pf', 'nome' => 'Bruno', 'documento' => '11144477735']);

        $this->mock(AvaliadorDeRisco::class)
            ->shouldReceive('avaliar')
            ->andReturnUsing(fn ($t) => match ($t->id) {
                $this->ana->id => ['score' => 15.0, 'nivel' => 'BAIXO'],
                $carlos->id => ['score' => 75.0, 'nivel' => 'CRITICO'],
                $bruno->id => throw new \InvalidArgumentException('Documento inválido: 11144477735'),
            });

        $this->getJson('/api/relatorio-risco?ano=2026')
            ->assertOk()
            ->assertJsonPath('meta.ano', 2026)
            ->assertJsonPath('data.0.nome', 'Carlos Lima')
            ->assertJsonPath('data.1.nome', 'Ana Souza')
            ->assertJsonPath('data.2.nome', 'Bruno')
            ->assertJsonPath('data.2.score', null)
            ->assertJsonPath('data.2.erro', 'Documento inválido: 11144477735');
    }

    public function test_valida_o_ano(): void
    {
        $this->getJson('/api/relatorio-risco?ano=abc')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonValidationErrors(['ano']);
    }
}
