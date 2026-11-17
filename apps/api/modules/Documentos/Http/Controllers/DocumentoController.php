<?php

declare(strict_types=1);

namespace Modules\Documentos\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Documentos\Http\Requests\StoreDocumentoRequest;
use Modules\Documentos\Http\Resources\DocumentoResource;
use Modules\Documentos\Models\Documento;
use Modules\Documentos\Services\DocumentoService;
use Modules\Fornecedores\Models\Fornecedor;
use RiskEngine\Enums\DocumentType;

class DocumentoController
{
    public function index(Fornecedor $fornecedor): AnonymousResourceCollection
    {
        return DocumentoResource::collection(
            Documento::query()->where('fornecedor_id', $fornecedor->id)->orderBy('id')->get(),
        );
    }

    public function store(StoreDocumentoRequest $request, Fornecedor $fornecedor, DocumentoService $service): JsonResponse
    {
        $documento = $service->registrar(
            $fornecedor,
            DocumentType::from($request->string('tipo')->toString()),
            $request->string('titulo')->toString(),
            $request->texto(),
            $request->input('emitido_em'),
        );

        return DocumentoResource::make($documento)->response()->setStatusCode(201);
    }

    /** Lista os tipos de documento aceitos, para o frontend montar o formulário. */
    public function tipos(): JsonResponse
    {
        return response()->json(['data' => array_map(fn (DocumentType $t) => [
            'valor' => $t->value,
            'rotulo' => $t->label(),
            'obrigatorio' => in_array($t, DocumentType::required(), true),
            'validade_dias' => $t->validityDays(),
        ], DocumentType::cases())]);
    }
}
