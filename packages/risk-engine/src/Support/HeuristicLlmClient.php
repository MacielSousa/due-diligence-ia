<?php

declare(strict_types=1);

namespace RiskEngine\Support;

use RiskEngine\Contracts\LlmClient;
use RiskEngine\Enums\Dimension;
use RiskEngine\Enums\KnockOut;
use RiskEngine\Enums\Severity;
use RiskEngine\Exceptions\LlmException;
use RiskEngine\Prompts;

/**
 * "LLM" determinístico baseado em palavras-chave, para rodar a demonstração sem chave de API.
 * NÃO é um substituto do modelo real — serve para demo, desenvolvimento e testes de integração.
 */
final class HeuristicLlmClient implements LlmClient
{
    /** Linhas com negação são ignoradas (ex.: "nada consta", "não consta"). */
    private const NEGATION = '/\b(certid[ãa]o negativa|nada consta|n[ãa]o consta|n[ãa]o constam|inexist\w*|sem registros?|n[ãa]o h[áa]|nenhum\w*)\b/iu';

    /** @var list<array{0: string, 1: Dimension, 2: Severity, 3: string, 4?: KnockOut}> */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            ['/fal[êe]ncia decretada/iu', Dimension::Financial, Severity::Critical, 'Falência decretada', KnockOut::Bankruptcy],
            ['/recupera[çc][ãa]o judicial/iu', Dimension::Financial, Severity::High, 'Empresa em recuperação judicial'],
            ['/patrim[ôo]nio l[íi]quido negativo|preju[íi]zo l[íi]quido/iu', Dimension::Financial, Severity::High, 'Resultado financeiro negativo'],
            ['/protestos?/iu', Dimension::Financial, Severity::Medium, 'Títulos protestados'],
            ['/endividamento elevado|liquidez corrente (de )?0[,.]\d/iu', Dimension::Financial, Severity::Medium, 'Indicadores de liquidez/endividamento ruins'],
            ['/obra (abandonada|paralisada)|rescis[ãa]o contratual/iu', Dimension::Technical, Severity::High, 'Histórico de obra abandonada ou contrato rescindido'],
            ['/atrasos?/iu', Dimension::Technical, Severity::Medium, 'Histórico de atraso na entrega'],
            ['/(trabalho|condi[çc][õo]es) an[áa]log[oa]s? [àa] (de )?escrav/iu', Dimension::Labor, Severity::Critical, 'Trabalho análogo à escravidão', KnockOut::SlaveLabor],
            ['/acidente (fatal|de trabalho)/iu', Dimension::Labor, Severity::High, 'Acidente de trabalho grave'],
            ['/d[ée]bitos? trabalhistas?|reclama[çc][õo]es trabalhistas/iu', Dimension::Labor, Severity::Medium, 'Passivo trabalhista'],
            ['/(consta|registrad[ao]) no (ceis|cnep)|empresa inid[ôo]nea/iu', Dimension::Legal, Severity::Critical, 'Empresa inidônea/punida', KnockOut::Ineligible],
            ['/situa[çc][ãa]o cadastral:?\s*(inapta|baixada|suspensa)/iu', Dimension::Legal, Severity::Critical, 'CNPJ irregular', KnockOut::InactiveCnpj],
            ['/condenad[ao] por corrup[çc][ãa]o|lei anticorrup[çc][ãa]o/iu', Dimension::Legal, Severity::Critical, 'Condenação por corrupção', KnockOut::Corruption],
            ['/improbidade|execu[çc][ãa]o fiscal/iu', Dimension::Legal, Severity::High, 'Processo de improbidade ou execução fiscal'],
            ['/investiga[çc][ãa]o|opera[çc][ãa]o da pol[íi]cia|den[úu]ncia/iu', Dimension::Reputational, Severity::High, 'Envolvimento em investigação/denúncia'],
            ['/reclama[çc][õo]es de clientes|multa/iu', Dimension::Reputational, Severity::Medium, 'Reclamações ou multas'],
        ];
    }

    public function json(string $system, string $prompt, array $schema): array
    {
        $properties = $schema['properties'] ?? [];

        return match (true) {
            isset($properties['achados']) => $this->analyze($prompt),
            isset($properties['dimensoes']) => $this->assess($prompt),
            default => throw new LlmException('Schema desconhecido para o HeuristicLlmClient.'),
        };
    }

    /** @return array<string, mixed> */
    private function analyze(string $prompt): array
    {
        $content = $this->between($prompt, Prompts::DOCUMENT_OPEN, Prompts::DOCUMENT_CLOSE);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $content) ?: [])));

        $findings = [];
        $knockOuts = [];

        foreach ($lines as $line) {
            if (preg_match(self::NEGATION, $line)) {
                continue;
            }

            foreach ($this->rules as $rule) {
                if (! preg_match($rule[0], $line)) {
                    continue;
                }

                $evidence = mb_substr($line, 0, 240);
                $findings[] = [
                    'dimensao' => $rule[1]->value,
                    'severidade' => $rule[2]->value,
                    'descricao' => $rule[3],
                    'evidencia' => $evidence,
                ];

                if (isset($rule[4])) {
                    $knockOuts[] = ['tipo' => $rule[4]->value, 'evidencia' => $evidence];
                }

                break; // uma regra por linha
            }
        }

        $summary = implode(' ', array_slice($lines, 0, 2));
        $summary .= $findings === []
            ? ' Nenhum ponto de atenção identificado.'
            : sprintf(' %d ponto(s) de atenção identificado(s).', count($findings));

        return ['resumo' => trim($summary), 'achados' => $findings, 'sinais_eliminatorios' => $knockOuts];
    }

    /** @return array<string, mixed> */
    private function assess(string $prompt): array
    {
        $documents = json_decode(
            $this->between($prompt, Prompts::FINDINGS_OPEN, Prompts::FINDINGS_CLOSE),
            true,
        ) ?: [];

        $byDimension = [];
        foreach ($documents as $document) {
            foreach ($document['achados'] ?? [] as $finding) {
                $byDimension[$finding['dimensao']][] = $finding;
            }
        }

        $dimensions = [];
        $worst = 0;

        foreach (Dimension::cases() as $dimension) {
            $findings = $byDimension[$dimension->value] ?? [];

            if ($findings === []) {
                $dimensions[] = [
                    'dimensao' => $dimension->value,
                    'nota' => 15,
                    'justificativa' => 'Nenhum achado negativo nos documentos analisados.',
                    'achados' => [],
                ];
                continue;
            }

            $points = array_map(fn ($f) => Severity::from($f['severidade'])->points(), $findings);
            $score = min(100, max($points) + 5 * (count($findings) - 1));
            $worst = max($worst, $score);

            $dimensions[] = [
                'dimensao' => $dimension->value,
                'nota' => $score,
                'justificativa' => implode('; ', array_unique(array_column($findings, 'descricao'))) . '.',
                'achados' => array_column($findings, 'id'),
            ];
        }

        $recommendation = match (true) {
            $worst >= 80 => 'Não contratar sem parecer do comitê de compliance; buscar fornecedor alternativo.',
            $worst >= 50 => 'Contratar somente com seguro-garantia de execução, retenção contratual e auditoria periódica.',
            default => 'Contratação recomendada com cláusulas padrão e monitoramento trimestral das certidões.',
        };

        return [
            'dimensoes' => $dimensions,
            'resumo_executivo' => sprintf(
                'Avaliação heurística (modo demonstração) com %d achado(s) validado(s) em %d documento(s).',
                array_sum(array_map('count', $byDimension)),
                count($documents),
            ),
            'recomendacao' => $recommendation,
        ];
    }

    private function between(string $text, string $open, string $close): string
    {
        $start = mb_strpos($text, $open);
        $end = mb_strrpos($text, $close);

        if ($start === false || $end === false) {
            return '';
        }

        $start += mb_strlen($open);

        return trim(mb_substr($text, $start, $end - $start));
    }
}
