<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnaliseIaResource;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorCode;
use App\Models\AnaliseIa;
use App\Models\Terceiro;
use App\Services\Ia\IniciadorDeAnalise;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnaliseIaController extends Controller
{
    /** GET /api/terceiros/{terceiro}/analises-ia — histórico (mais recente primeiro) */
    public function index(Terceiro $terceiro): JsonResponse
    {
        $analises = $terceiro->analises()->with('documentos')->latest('id')->limit(10)->get();

        return ApiResponse::success(AnaliseIaResource::collection($analises));
    }

    /**
     * POST /api/terceiros/{terceiro}/analises-ia
     * Cria a análise e dispara o job: responde 202 na hora, o resultado sai depois (acompanhe pelo GET).
     */
    public function store(Request $request, Terceiro $terceiro, IniciadorDeAnalise $iniciador): JsonResponse
    {
        $request->validate(['ano' => ['sometimes', 'integer', 'between:1990,2100']]);

        try {
            $analise = $iniciador->iniciar($terceiro, (int) $request->input('ano', date('Y')));
        } catch (DomainException $e) {
            return $e->getCode() === 409
                ? ApiResponse::error($e->getMessage(), ErrorCode::Conflict, 409)
                : ApiResponse::error($e->getMessage(), ErrorCode::BusinessRule, 422);
        }

        return ApiResponse::success(
            new AnaliseIaResource($analise->fresh('documentos')),
            __('api.analise.iniciada', ['total' => $analise->documentos_total]),
            202,
        );
    }

    /** GET /api/terceiros/{terceiro}/analises-ia/{analise} — progresso, métricas e resultado */
    public function show(Terceiro $terceiro, AnaliseIa $analise): JsonResponse
    {
        return ApiResponse::success(new AnaliseIaResource($analise->load('documentos')));
    }
}
