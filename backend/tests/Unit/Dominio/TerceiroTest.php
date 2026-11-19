<?php

namespace Tests\Unit\Dominio;

use App\Domain\DueDiligence\AnalisadorDeRisco;
use App\Domain\DueDiligence\Apontamento;
use App\Domain\DueDiligence\PessoaFisica;
use App\Domain\DueDiligence\PessoaJuridica;
use App\Domain\DueDiligence\Terceiro;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Regras de score de PF e PJ (Template Method + Composite), sem Laravel e sem banco. */
class TerceiroTest extends TestCase
{
    private const CPF = '52998224725';

    private const CPF_2 = '12345678909';

    private const CNPJ = '11222333000181';

    private const CNPJ_2 = '45278245000113';

    public function test_pessoa_fisica_soma_apontamentos_e_adicional_pep(): void
    {
        $pf = new PessoaFisica('Ana', self::CPF, pep: true);
        $pf->adicionarApontamento(new Apontamento('processo_criminal', 2026));

        $this->assertSame(50.0, $pf->calcularRisco(2026)); // 30 + 20 (PEP)
    }

    public function test_documento_invalido_e_recusado_no_construtor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PessoaFisica('Ana', '11111111111');
    }

    public function test_pf_exige_cpf_e_pj_exige_cnpj(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PessoaJuridica('Empresa', self::CPF);
    }

    public function test_empresa_herda_o_risco_do_socio_ponderado_pela_participacao(): void
    {
        $socio = new PessoaFisica('Sócio', self::CPF);
        $socio->adicionarApontamento(new Apontamento('sancao', 2026)); // 40

        $empresa = new PessoaJuridica('Empresa', self::CNPJ);
        $empresa->adicionarApontamento(new Apontamento('midia_negativa', 2026)); // 15
        $empresa->adicionarSocio($socio, 50);

        $this->assertSame(35.0, $empresa->calcularRisco(2026)); // 15 + 40 × 50%
    }

    public function test_score_tem_teto_de_100(): void
    {
        $pf = new PessoaFisica('Ana', self::CPF, pep: true);
        foreach (range(1, 3) as $_) {
            $pf->adicionarApontamento(new Apontamento('sancao', 2026));
        }

        $explicacao = $pf->explicarRisco(2026);

        $this->assertSame(100.0, $explicacao['score']);
        $this->assertTrue($explicacao['teto_aplicado']);
    }

    public function test_participacao_total_nao_pode_passar_de_100(): void
    {
        $empresa = new PessoaJuridica('Empresa', self::CNPJ);
        $empresa->adicionarSocio(new PessoaFisica('A', self::CPF), 60);

        $this->expectException(InvalidArgumentException::class);
        $empresa->adicionarSocio(new PessoaFisica('B', self::CPF_2), 50);
    }

    public function test_ciclo_societario_e_detectado(): void
    {
        $a = new PessoaJuridica('A', self::CNPJ);
        $b = new PessoaJuridica('B', self::CNPJ_2);
        $a->adicionarSocio($b, 50);
        $b->adicionarSocio($a, 50);

        $this->expectException(LogicException::class);
        $a->calcularRisco(2026);
    }

    public function test_mesmo_socio_por_dois_caminhos_nao_e_ciclo(): void
    {
        $pessoa = new PessoaFisica('Sócia', self::CPF);
        $pessoa->adicionarApontamento(new Apontamento('sancao', 2026)); // 40

        $holding = new PessoaJuridica('Holding', self::CNPJ_2);
        $holding->adicionarSocio($pessoa, 100); // 40

        $empresa = new PessoaJuridica('Empresa', self::CNPJ);
        $empresa->adicionarSocio($pessoa, 50); // 20
        $empresa->adicionarSocio($holding, 50); // 20

        $this->assertSame(40.0, $empresa->calcularRisco(2026));
    }

    public function test_polimorfismo_pf_e_pj_entram_no_mesmo_relatorio(): void
    {
        $analisador = new AnalisadorDeRisco(2026);
        $pf = new PessoaFisica('PF', self::CPF, pep: true);
        $pj = new PessoaJuridica('PJ', self::CNPJ);
        $pj->adicionarApontamento(new Apontamento('sancao', 2026));

        foreach ([$pf, $pj] as $terceiro) {
            $this->assertInstanceOf(Terceiro::class, $terceiro);
            $analisador->adicionar($terceiro);
        }

        $this->assertSame(['PJ', 'PF'], array_column($analisador->relatorio(), 'nome')); // maior score primeiro
    }

    #[DataProvider('faixas')]
    public function test_classificacao_por_faixa(float $score, string $nivel): void
    {
        $this->assertSame($nivel, AnalisadorDeRisco::classificar($score));
    }

    public static function faixas(): array
    {
        return [[0, 'BAIXO'], [24.9, 'BAIXO'], [25, 'MEDIO'], [50, 'ALTO'], [74.9, 'ALTO'], [75, 'CRITICO'], [100, 'CRITICO']];
    }
}
