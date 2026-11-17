<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Modules\Avaliacao\Enums\Decisao;
use Modules\Avaliacao\Http\Resources\AvaliacaoResource;
use Modules\Avaliacao\Models\Avaliacao;
use Modules\Avaliacao\Services\AvaliacaoService;
use Modules\Fornecedores\Models\Fornecedor;

class AvaliacaoController
{
    public function __construct(private readonly AvaliacaoService $service)
    {
    }

    public function index(Fornecedor $fornecedor): AnonymousResourceCollection
    {
        return AvaliacaoResource::collection(
            Avaliacao::query()->where('fornecedor_id', $fornecedor->id)->latest('id')->get(),
        );
    }

    public function store(Fornecedor $fornecedor): JsonResponse
    {
        return AvaliacaoResource::make($this->service->solicitar($fornecedor))
            ->response()
            ->setStatusCode(202);
    }

    public function show(Avaliacao $avaliacao): AvaliacaoResource
    {
        return AvaliacaoResource::make($avaliacao->load('fornecedor'));
    }

    public function decidir(Request $request, Avaliacao $avaliacao): AvaliacaoResource
    {
        $dados = $request->validate([
            'decisao' => ['required', Rule::enum(Decisao::class)],
            'decidido_por' => ['required', 'string', 'max:255'],
            'justificativa' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        return AvaliacaoResource::make($this->service->decidir(
            $avaliacao,
            Decisao::from($dados['decisao']),
            $dados['decidido_por'],
            $dados['justificativa'],
        )->load('fornecedor'));
    }
}
