<?php

declare(strict_types=1);

namespace Modules\Documentos\Storage;

use Modules\Documentos\Contracts\ConteudoDocumentoStore;

final class MongoConteudoDocumentoStore implements ConteudoDocumentoStore
{
    public function salvar(int $documentoId, int $fornecedorId, string $sha256, string $texto): void
    {
        ConteudoDocumento::query()->updateOrCreate(
            ['documento_id' => $documentoId],
            ['fornecedor_id' => $fornecedorId, 'sha256' => $sha256, 'texto' => $texto],
        );
    }

    public function buscar(int $documentoId): ?string
    {
        return ConteudoDocumento::query()->where('documento_id', $documentoId)->value('texto');
    }

    public function buscarVarios(array $documentoIds): array
    {
        return ConteudoDocumento::query()
            ->whereIn('documento_id', $documentoIds)
            ->get(['documento_id', 'texto'])
            ->mapWithKeys(fn (ConteudoDocumento $c) => [(int) $c->documento_id => (string) $c->texto])
            ->all();
    }
}
