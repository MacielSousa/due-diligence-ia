<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Documentos\Services\DocumentoService;
use Modules\Fornecedores\Models\Fornecedor;
use RiskEngine\Enums\DocumentType;

/**
 * Três construtoras FICTÍCIAS para a demonstração: uma de risco baixo, uma médio e uma elevado.
 * Os documentos são textos simulados, não dados reais.
 */
class DatabaseSeeder extends Seeder
{
    public function run(DocumentoService $documentos): void
    {
        $recente = now()->subDays(10)->toDateString();

        foreach ($this->cenarios() as $cenario) {
            $fornecedor = Fornecedor::query()->firstOrCreate(
                ['cnpj' => $cenario['cnpj']],
                ['razao_social' => $cenario['razao_social'], 'valor_contrato_estimado' => $cenario['valor']],
            );

            if (! $fornecedor->wasRecentlyCreated) {
                continue; // não duplica documentos ao rodar o seeder de novo
            }

            foreach ($cenario['documentos'] as [$tipo, $titulo, $texto]) {
                $documentos->registrar($fornecedor, $tipo, $titulo, $texto, $recente);
            }
        }
    }

    private function cenarios(): array
    {
        $negativas = fn (string $empresa) => [
            [DocumentType::CnpjCard, 'Comprovante de inscrição CNPJ', "{$empresa}\nSituação cadastral: ATIVA desde 2004."],
            [DocumentType::FederalCertificate, 'Certidão conjunta RFB/PGFN', "Certidão negativa de débitos relativos aos tributos federais.\nNada consta em nome do contribuinte."],
            [DocumentType::SlaveLaborList, 'Consulta Cadastro de Empregadores (MTE)', "Consulta ao Cadastro de Empregadores - Portaria Interministerial.\nResultado: não consta registro para o CNPJ consultado."],
            [DocumentType::CeisCnep, 'Consulta CEIS/CNEP (Portal da Transparência)', "Cadastro de Empresas Inidôneas e Suspensas.\nNenhum registro encontrado para o CNPJ consultado."],
        ];

        return [
            [
                'razao_social' => 'Construtora Horizonte Azul S.A. (fictícia)',
                'cnpj' => '11222333000181',
                'valor' => 1_200_000_000,
                'documentos' => [
                    ...$negativas('Construtora Horizonte Azul S.A.'),
                    [DocumentType::LaborCertificate, 'CNDT', "Certidão negativa de débitos trabalhistas.\nNão constam débitos inadimplidos perante a Justiça do Trabalho."],
                    [DocumentType::FinancialStatement, 'Balanço 2025', "Receita líquida de R$ 3,1 bilhões, crescimento de 12%.\nLucro líquido de R$ 280 milhões.\nLiquidez corrente de 1,8 e dívida sob controle."],
                    [DocumentType::TechnicalCertificate, 'Atestado - Hospital Regional', "Obra de 45 mil m² concluída dentro do prazo e do orçamento.\nCAT registrada no CREA."],
                    [DocumentType::News, 'Clipping de notícias', "Empresa recebe prêmio de segurança do trabalho do setor.\nInaugurada nova sede administrativa em Campinas."],
                ],
            ],
            [
                'razao_social' => 'Engenharia Pedra Firme Ltda. (fictícia)',
                'cnpj' => '22333444000105',
                'valor' => 2_500_000_000,
                'documentos' => [
                    ...$negativas('Engenharia Pedra Firme Ltda.'),
                    [DocumentType::LaborCertificate, 'CNDT', "Certidão positiva com efeito de negativa.\nExistem 14 reclamações trabalhistas em fase de execução com garantia do juízo."],
                    [DocumentType::FinancialStatement, 'Balanço 2025', "Receita líquida de R$ 900 milhões.\nPrejuízo líquido de R$ 45 milhões no exercício.\nProtestos em cartório somando R$ 3,2 milhões."],
                    [DocumentType::TechnicalCertificate, 'Atestado - Viaduto Norte', "Obra concluída com atraso de 7 meses em relação ao cronograma.\nQualidade técnica considerada satisfatória."],
                    [DocumentType::News, 'Clipping de notícias', "Consumidores relatam reclamações de clientes sobre infiltrações em empreendimento residencial."],
                ],
            ],
            [
                'razao_social' => 'Construtora Muralha Ltda. (fictícia)',
                'cnpj' => '33444555000177',
                'valor' => 4_000_000_000,
                'documentos' => [
                    [DocumentType::CnpjCard, 'Comprovante de inscrição CNPJ', "Construtora Muralha Ltda.\nSituação cadastral: ATIVA."],
                    [DocumentType::SlaveLaborList, 'Consulta Cadastro de Empregadores (MTE)', "Consulta ao Cadastro de Empregadores.\nResultado: empregador incluído após fiscalização que resgatou 23 trabalhadores em condições análogas à de escravo em canteiro de obras."],
                    [DocumentType::FinancialStatement, 'Balanço 2025', "Empresa em recuperação judicial desde março de 2025.\nPatrimônio líquido negativo de R$ 120 milhões."],
                    [DocumentType::Lawsuits, 'Pesquisa de processos', "Execução fiscal movida pela União no valor de R$ 18 milhões.\nAção civil pública por acidente fatal em obra de 2024."],
                    [DocumentType::News, 'Clipping de notícias', "Sócios são alvo de operação da Polícia Federal por fraude em licitações."],
                ],
            ],
        ];
    }
}
