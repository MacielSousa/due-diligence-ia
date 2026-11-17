<?php

declare(strict_types=1);

namespace RiskEngine\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RiskEngine\Enums\DocumentType;
use RiskEngine\Enums\RiskLevel;
use RiskEngine\RiskPipeline;
use RiskEngine\Testing\FakeLlmClient;
use RiskEngine\Tests\Factories;

final class RiskPipelineTest extends TestCase
{
    use Factories;

    #[Test]
    public function dossie_completo_e_limpo_resulta_em_risco_baixo_sem_revisao(): void
    {
        $docs = $this->cleanDossier();
        $llm = new FakeLlmClient([...array_fill(0, count($docs), $this->emptyAnalysis()), $this->assessResponse(10)]);

        $assessment = (new RiskPipeline($llm))->run('Construtora Alfa', $docs, $this->now());

        $this->assertSame(RiskLevel::Low, $assessment->level);
        $this->assertSame(1.0, $assessment->confidence);
        $this->assertFalse($assessment->requiresHumanReview);
        $this->assertCount(count($docs) + 1, $llm->calls, 'Uma chamada por documento + uma de consolidação.');
    }

    #[Test]
    public function sinal_eliminatorio_em_um_documento_torna_o_risco_elevado(): void
    {
        $docs = $this->cleanDossier();
        $docs[3] = $this->doc(DocumentType::SlaveLaborList, 'O empregador CONSTA no Cadastro de Empregadores que submeteram trabalhadores a condições análogas à de escravo.', 'd4');

        $responses = array_fill(0, count($docs), $this->emptyAnalysis());
        $responses[3] = [
            'resumo' => 'Consta na lista suja.',
            'achados' => [],
            'sinais_eliminatorios' => [['tipo' => 'lista_suja_trabalho_escravo', 'evidencia' => 'CONSTA no Cadastro de Empregadores']],
        ];
        $llm = new FakeLlmClient([...$responses, $this->assessResponse(10)]);

        $assessment = (new RiskPipeline($llm))->run('Construtora Beta', $docs, $this->now());

        $this->assertSame(RiskLevel::High, $assessment->level);
        $this->assertTrue($assessment->requiresHumanReview);
        $this->assertCount(1, $assessment->knockOuts);
    }

    #[Test]
    public function documentos_faltantes_ou_vencidos_reduzem_confianca_e_exigem_revisao(): void
    {
        $docs = [
            $this->doc(DocumentType::FederalCertificate, 'Nada consta.', 'd1', '2025-01-01'), // vencida
            $this->doc(DocumentType::FinancialStatement, 'Balanço saudável.', 'd2'),
        ];
        $llm = new FakeLlmClient([$this->emptyAnalysis(), $this->emptyAnalysis(), $this->assessResponse(10)]);

        $assessment = (new RiskPipeline($llm))->run('Construtora Gama', $docs, $this->now());

        $this->assertSame(RiskLevel::Low, $assessment->level);
        $this->assertContains('cndt', $assessment->missingDocuments);
        $this->assertSame(['certidao_federal'], $assessment->expiredDocuments);
        $this->assertLessThan(0.7, $assessment->confidence);
        $this->assertTrue($assessment->requiresHumanReview);
    }

    #[Test]
    public function dimensao_nao_avaliada_pelo_modelo_recebe_nota_neutra(): void
    {
        $docs = $this->cleanDossier();
        $assess = $this->assessResponse(10);
        array_pop($assess['dimensoes']); // modelo "esqueceu" a dimensão reputacional
        $llm = new FakeLlmClient([...array_fill(0, count($docs), $this->emptyAnalysis()), $assess]);

        $assessment = (new RiskPipeline($llm))->run('Construtora Delta', $docs, $this->now());

        $this->assertSame(50, $assessment->dimensionScores['reputacional']->score);
        $this->assertSame(0.9, $assessment->confidence);
    }
}
