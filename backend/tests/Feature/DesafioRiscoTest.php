<?php

namespace Tests\Feature;

use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * TESTES DE ACEITAÇÃO DO DESAFIO.
 * Usam as SUAS classes em app/Domain/DueDiligence de verdade (sem mock),
 * então só passam quando os 4 níveis estiverem implementados.
 *
 *   composer test:desafio
 */
#[Group('desafio')]
class DesafioRiscoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Ana, Carlos, Holding XPTO, Fornecedor ABC
    }

    private function id(string $nome): int
    {
        return Terceiro::where('nome', $nome)->value('id');
    }

    public function test_nivel_2_risco_de_pessoa_fisica_com_pep_e_apontamento_antigo(): void
    {
        // criminal 2018 (>5 anos: 30 -> 15) + sanção 2025 (40) + PEP (20)
        $this->getJson('/api/terceiros/' . $this->id('Carlos Lima') . '/risco?ano=2026')
            ->assertOk()
            ->assertJsonPath('data.score', 75)
            ->assertJsonPath('data.nivel', 'CRITICO');
    }

    public function test_nivel_2_ano_de_referencia_muda_o_peso(): void
    {
        // em 2031 a sanção de 2025 também passa de 5 anos: 15 + 20 + 20
        $this->getJson('/api/terceiros/' . $this->id('Carlos Lima') . '/risco?ano=2031')
            ->assertOk()
            ->assertJsonPath('data.score', 55)
            ->assertJsonPath('data.nivel', 'ALTO');
    }

    public function test_nivel_3_risco_de_pessoa_juridica_ponderado_pelos_socios(): void
    {
        // 10 + 10 (trabalhistas) + Ana 15 × 50% + Holding 45 × 50%
        $this->getJson('/api/terceiros/' . $this->id('Fornecedor ABC') . '/risco?ano=2026')
            ->assertOk()
            ->assertJsonPath('data.score', 50)
            ->assertJsonPath('data.nivel', 'ALTO');
    }

    public function test_nivel_4_relatorio_ordenado_do_maior_para_o_menor(): void
    {
        $resposta = $this->getJson('/api/relatorio-risco?ano=2026')->assertOk();

        $this->assertSame(
            [['Carlos Lima', 75, 'CRITICO'], ['Fornecedor ABC', 50, 'ALTO'], ['Holding XPTO', 45, 'MEDIO'], ['Ana Souza', 15, 'BAIXO']],
            array_map(fn ($l) => [$l['nome'], $l['score'], $l['nivel']], $resposta->json('data')),
        );
    }

    public function test_nivel_4_ciclo_societario_retorna_422(): void
    {
        $alfa = Terceiro::create(['tipo' => 'pj', 'nome' => 'Alfa Ltda', 'documento' => '10000000000145']);
        $beta = Terceiro::create(['tipo' => 'pj', 'nome' => 'Beta Ltda', 'documento' => '20000000000107']);
        $alfa->socios()->create(['socio_id' => $beta->id, 'participacao' => 100]);
        $beta->socios()->create(['socio_id' => $alfa->id, 'participacao' => 100]);

        $this->getJson("/api/terceiros/{$alfa->id}/risco")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ciclo societário detectado em Alfa Ltda');
    }

    public function test_nivel_4_mesmo_socio_por_dois_caminhos_nao_e_ciclo(): void
    {
        // X tem a Holding direto (50%) e via Y (50%), e Y tem 100% da Holding
        $x = Terceiro::create(['tipo' => 'pj', 'nome' => 'Empresa X', 'documento' => '30000000000152']);
        $y = Terceiro::create(['tipo' => 'pj', 'nome' => 'Empresa Y', 'documento' => '40000000000106']);
        $y->socios()->create(['socio_id' => $this->id('Holding XPTO'), 'participacao' => 100]);
        $x->socios()->create(['socio_id' => $this->id('Holding XPTO'), 'participacao' => 50]);
        $x->socios()->create(['socio_id' => $y->id, 'participacao' => 50]);

        $this->getJson("/api/terceiros/{$x->id}/risco?ano=2026")
            ->assertOk()
            ->assertJsonPath('data.score', 45);
    }

    public function test_nivel_1_cpf_com_digito_verificador_errado_e_barrado_pelo_dominio(): void
    {
        // a API só confere os 11 dígitos; quem valida o DV é a sua PessoaFisica
        $falso = Terceiro::create(['tipo' => 'pf', 'nome' => 'CPF Errado', 'documento' => '12345678900']);

        $this->getJson("/api/terceiros/{$falso->id}/risco")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Documento inválido: 12345678900');
    }
}
