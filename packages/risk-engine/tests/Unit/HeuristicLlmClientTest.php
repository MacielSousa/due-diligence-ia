<?php

declare(strict_types=1);

namespace RiskEngine\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RiskEngine\Enums\DocumentType;
use RiskEngine\Enums\RiskLevel;
use RiskEngine\RiskPipeline;
use RiskEngine\Support\HeuristicLlmClient;
use RiskEngine\Tests\Factories;

final class HeuristicLlmClientTest extends TestCase
{
    use Factories;

    #[Test]
    public function certidoes_negativas_nao_geram_achados(): void
    {
        $assessment = (new RiskPipeline(new HeuristicLlmClient()))->run('Alfa', $this->cleanDossier(), $this->now());

        $this->assertSame(RiskLevel::Low, $assessment->level);
        $this->assertSame([], array_merge(...array_map(fn ($a) => $a->findings, $assessment->analyses)));
    }

    #[Test]
    public function empresa_com_problemas_financeiros_e_atrasos_fica_em_risco_medio(): void
    {
        $docs = $this->cleanDossier();
        $docs[5] = $this->doc(DocumentType::FinancialStatement, "Balanço 2025\nPrejuízo líquido de R$ 40 milhões no exercício.\nProtestos em cartório somando R$ 2 milhões.", 'd6');
        $docs[6] = $this->doc(DocumentType::TechnicalCertificate, "Atestado de obra rodoviária\nEntregue com atraso de 8 meses.", 'd7');

        $assessment = (new RiskPipeline(new HeuristicLlmClient()))->run('Beta', $docs, $this->now());

        $this->assertSame(RiskLevel::Medium, $assessment->level);
        $this->assertSame([], $assessment->knockOuts);
    }

    #[Test]
    public function lista_suja_e_eliminatoria(): void
    {
        $docs = $this->cleanDossier();
        $docs[3] = $this->doc(DocumentType::SlaveLaborList, "Consulta ao Cadastro de Empregadores\nResultado: empregador flagrado com trabalhadores em condições análogas à de escravo.", 'd4');

        $assessment = (new RiskPipeline(new HeuristicLlmClient()))->run('Gama', $docs, $this->now());

        $this->assertSame(RiskLevel::High, $assessment->level);
        $this->assertCount(1, $assessment->knockOuts);
    }
}
