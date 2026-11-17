<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Fornecedores\Http\Requests\StoreFornecedorRequest;
use Modules\Fornecedores\Http\Resources\FornecedorResource;
use Modules\Fornecedores\Models\Fornecedor;

class FornecedorController
{
    public function index(): AnonymousResourceCollection
    {
        return FornecedorResource::collection(Fornecedor::query()->latest('id')->paginate(20));
    }

    public function store(StoreFornecedorRequest $request): JsonResponse
    {
        $fornecedor = Fornecedor::create($request->validated());

        return FornecedorResource::make($fornecedor)->response()->setStatusCode(201);
    }

    public function show(Fornecedor $fornecedor): FornecedorResource
    {
        return FornecedorResource::make($fornecedor);
    }
}
