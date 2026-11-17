<?php

declare(strict_types=1);

namespace RiskEngine\Tests;

use DateTimeImmutable;
use RiskEngine\Data\DimensionScore;
use RiskEngine\Data\Document;
use RiskEngine\Enums\Dimension;
use RiskEngine\Enums\DocumentType;

trait Factories
{
    protected function doc(DocumentType $type, string $content, string $id = 'd1', ?string $issuedAt = '2026-11-01'): Document
    {
        return new Document($id, $type, $type->label(), $content, $issuedAt ? new DateTimeImmutable($issuedAt) : null);
    }

    /** @return list<Document> todos os documentos obrigatórios, sem pontos de atenção */
    protected function cleanDossier(): array
    {
        $docs = [];
        foreach (DocumentType::required() as $i => $type) {
            $docs[] = $this->doc($type, "{$type->label()}\nNada consta em nome da empresa.", 'd' . ($i + 1));
        }

        return $docs;
    }

    /** @param array<string, int> $notes */
    protected function scores(array $notes): array
    {
        $scores = [];
        foreach (Dimension::cases() as $dimension) {
            $scores[$dimension->value] = new DimensionScore($dimension, $notes[$dimension->value] ?? 10, 'teste');
        }

        return $scores;
    }

    /** Resposta do LLM para a etapa de consolidação com a mesma nota em todas as dimensões. */
    protected function assessResponse(int $note = 10): array
    {
        return [
            'dimensoes' => array_map(fn (Dimension $d) => [
                'dimensao' => $d->value,
                'nota' => $note,
                'justificativa' => 'ok',
                'achados' => [],
            ], Dimension::cases()),
            'resumo_executivo' => 'Resumo.',
            'recomendacao' => 'Recomendação.',
        ];
    }

    protected function emptyAnalysis(): array
    {
        return ['resumo' => 'Documento sem pontos de atenção.', 'achados' => [], 'sinais_eliminatorios' => []];
    }

    protected function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-17');
    }
}
