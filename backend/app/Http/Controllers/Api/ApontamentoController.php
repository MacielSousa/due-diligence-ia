<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApontamentoRequest;
use App\Http\Resources\ApontamentoResource;
use App\Http\Responses\ApiResponse;
use App\Models\Apontamento;
use App\Models\Terceiro;
use Illuminate\Http\JsonResponse;

class ApontamentoController extends Controller
{
    /** POST /api/terceiros/{terceiro}/apontamentos */
    public function store(ApontamentoRequest $request, Terceiro $terceiro): JsonResponse
    {
        $apontamento = $terceiro->apontamentos()->create($request->validated());

        return ApiResponse::created(new ApontamentoResource($apontamento), __('api.apontamento.criado'));
    }

    /** DELETE /api/terceiros/{terceiro}/apontamentos/{apontamento} */
    public function destroy(Terceiro $terceiro, Apontamento $apontamento): JsonResponse
    {
        $apontamento->delete();

        return ApiResponse::success(message: __('api.apontamento.excluido'));
    }
}
