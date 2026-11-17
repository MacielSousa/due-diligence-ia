<?php

declare(strict_types=1);

namespace Modules\Documentos\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Documentos\Contracts\ConteudoDocumentoStore;
use Modules\Documentos\Models\Documento;
use Modules\Fornecedores\Models\Fornecedor;
use RiskEngine\Enums\DocumentType;

/** API pública do módulo Documentos — os outros módulos falam com ele por aqui. */
final class DocumentoService
{
    public function __construct(private readonly ConteudoDocumentoStore $conteudos)
    {
    }

    public function registrar(
        Fornecedor $fornecedor,
        DocumentType $tipo,
        string $titulo,
        string $texto,
        ?string $emitidoEm = null,
    ): Documento {
        $texto = trim($texto);
        $sha256 = hash('sha256', $texto);

        return DB::transaction(function () use ($fornecedor, $tipo, $titulo, $texto, $emitidoEm, $sha256) {
            $documento = Documento::create([
                'fornecedor_id' => $fornecedor->id,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'emitido_em' => $emitidoEm,
                'sha256' => $sha256,
                'tamanho_bytes' => strlen($texto),
            ]);

            // Se o Mongo falhar, a transação do MySQL é desfeita.
            $this->conteudos->salvar($documento->id, $fornecedor->id, $sha256, $texto);

            return $documento;
        });
    }

    /**
     * Documentos do fornecedor com o texto integral carregado.
     *
     * @return Collection<int, array{documento: Documento, texto: string}>
     */
    public function comConteudo(int $fornecedorId): Collection
    {
        $documentos = Documento::query()->where('fornecedor_id', $fornecedorId)->orderBy('id')->get();
        $textos = $this->conteudos->buscarVarios($documentos->modelKeys());

        return $documentos
            ->filter(fn (Documento $d) => isset($textos[$d->id]))
            ->map(fn (Documento $d) => ['documento' => $d, 'texto' => $textos[$d->id]])
            ->values();
    }
}
