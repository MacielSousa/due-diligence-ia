<?php

declare(strict_types=1);

namespace RiskEngine\Data;

/** Resultado da etapa "map": resumo e achados de um único documento. */
final readonly class DocumentAnalysis
{
    /**
     * @param list<Finding> $findings
     * @param list<KnockOutSignal> $knockOuts
     */
    public function __construct(
        public string $documentId,
        public string $summary,
        public array $findings,
        public array $knockOuts,
        public int $discardedItems = 0,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'documento_id' => $this->documentId,
            'resumo' => $this->summary,
            'achados' => array_map(fn (Finding $f) => $f->toArray(), $this->findings),
            'sinais_eliminatorios' => array_map(fn (KnockOutSignal $k) => $k->toArray(), $this->knockOuts),
            'itens_descartados_sem_evidencia' => $this->discardedItems,
        ];
    }
}
