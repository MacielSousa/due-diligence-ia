<?php

declare(strict_types=1);

namespace Modules\Documentos\Contracts;

/** Armazenamento do texto bruto dos documentos (implementação padrão: MongoDB). */
interface ConteudoDocumentoStore
{
    public function salvar(int $documentoId, int $fornecedorId, string $sha256, string $texto): void;

    public function buscar(int $documentoId): ?string;

    /**
     * @param list<int> $documentoIds
     * @return array<int, string> texto indexado pelo id do documento
     */
    public function buscarVarios(array $documentoIds): array;
}
