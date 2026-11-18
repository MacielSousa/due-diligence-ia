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
    public function dimensao_nao_avaliada_recebe_nota_neutra_e_exige_revisao(): void
    {
        $docs = $this->cleanDossier();
        $assess = $this->assessResponse(10);
        array_pop($assess['dimensoes']); // modelo "esqueceu" a dimensão reputacional
        $llm = new FakeLlmClient([...array_fill(0, count($docs), $this->emptyAnalysis()), $assess]);

        $assessment = (new RiskPipeline($llm))->run('Construtora Delta', $docs, $this->now());

        $this->assertSame(50, $assessment->dimensionScores['reputacional']->score);
        $this->assertSame(0.9, $assessment->confidence);
        $this->assertSame(RiskLevel::Low, $assessment->level);
        $this->assertTrue($assessment->requiresHumanReview, 'Lacuna do modelo exige revisão mesmo com risco baixo.');
    }

    /**
     * Regressão: respostas reais do llama3.2:3b para a "Engenharia Pedra Firme" (risco médio).
     * A leitura extraiu achados de severidade alta, mas a consolidação deu 10 para "financeira"
     * e não avaliou "tecnica". Antes do piso: score 33,5 → BAIXO, sem revisão humana.
     */
    #[Test]
    public function piso_por_severidade_impede_que_a_consolidacao_ignore_achados_altos(): void
    {
        $docs = $this->cleanDossier();
        $docs[2] = $this->doc(DocumentType::LaborCertificate, 'Existem 14 reclamações trabalhistas em fase de execução com garantia do juízo.', 'd3');
        $docs[5] = $this->doc(DocumentType::FinancialStatement, 'Receita líquida de R$ 900 milhões. Prejuízo líquido de R$ 45 milhões no exercício.', 'd6');
        $docs[6] = $this->doc(DocumentType::TechnicalCertificate, 'Clientes relatam infiltrações em empreendimento residencial.', 'd7');

        $map = array_fill(0, count($docs), $this->emptyAnalysis());
        $map[2] = $this->analysis('trabalhista', 'alta', 'Existem 14 reclamações trabalhistas em fase de execução');
        $map[5] = $this->analysis('financeira', 'alta', 'Prejuízo líquido de R$ 45 milhões no exercício');
        $map[6] = $this->analysis('tecnica', 'alta', 'infiltrações em empreendimento residencial');

        $reduce = [
            'dimensoes' => [
                ['dimensao' => 'financeira', 'nota' => 10, 'justificativa' => 'Protestos são um problema menor.', 'achados' => []],
                ['dimensao' => 'trabalhista', 'nota' => 60, 'justificativa' => 'Reclamações em execução.', 'achados' => []],
                ['dimensao' => 'juridica', 'nota' => 20, 'justificativa' => 'Sem problemas.', 'achados' => []],
                ['dimensao' => 'reputacional', 'nota' => 30, 'justificativa' => 'Infiltrações.', 'achados' => []],
                // "tecnica" ausente, como na resposta real
            ],
            'resumo_executivo' => 'Resumo.',
            'recomendacao' => 'Recomendação.',
        ];

        $assessment = (new RiskPipeline(new FakeLlmClient([...$map, $reduce])))->run('Pedra Firme', $docs, $this->now());

        $this->assertSame(50, $assessment->dimensionScores['financeira']->score, 'Achado alto → nota mínima 50.');
        $this->assertSame(60, $assessment->dimensionScores['trabalhista']->score, 'Piso não reduz nota maior.');
        $this->assertSame(50, $assessment->dimensionScores['tecnica']->score);
        // 50×0,30 + 50×0,25 + 60×0,20 + 20×0,15 + 30×0,10 = 45,5
        $this->assertEqualsWithDelta(45.5, $assessment->score, 0.001);
        $this->assertSame(RiskLevel::Medium, $assessment->level);
        $this->assertTrue($assessment->requiresHumanReview);
        $this->assertStringContainsString('financeira elevada de 10 para 50', implode(' ', $assessment->reasons));
    }

    #[Test]
    public function achado_critico_garante_nota_minima_80(): void
    {
        $docs = $this->cleanDossier();
        $docs[5] = $this->doc(DocumentType::FinancialStatement, 'Empresa em recuperação judicial desde março de 2025.', 'd6');
        $map = array_fill(0, count($docs), $this->emptyAnalysis());
        $map[5] = $this->analysis('financeira', 'critica', 'em recuperação judicial desde março de 2025');

        $assessment = (new RiskPipeline(new FakeLlmClient([...$map, $this->assessResponse(10)])))->run('Ômega', $docs, $this->now());

        $this->assertSame(80, $assessment->dimensionScores['financeira']->score);
        // dimensão ≥ 80 garante no mínimo MÉDIO
        $this->assertSame(RiskLevel::Medium, $assessment->level);
    }

    private function analysis(string $dimensao, string $severidade, string $evidencia): array
    {
        return [
            'resumo' => 'Documento com ponto de atenção.',
            'achados' => [['dimensao' => $dimensao, 'severidade' => $severidade, 'descricao' => 'Achado', 'evidencia' => $evidencia]],
            'sinais_eliminatorios' => [],
        ];
    }
}
