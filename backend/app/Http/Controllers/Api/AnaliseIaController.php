<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnaliseIaResource;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorCode;
use App\Jobs\AnalisarDocumentosJob;
use App\Models\AnaliseIa;
use App\Models\Terceiro;
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
    public function store(Request $request, Terceiro $terceiro): JsonResponse
    {
        $request->validate(['ano' => ['sometimes', 'integer', 'between:1990,2100']]);

        if ($terceiro->analises()->whereIn('status', [AnaliseIa::PENDENTE, AnaliseIa::PROCESSANDO])->exists()) {
            return ApiResponse::error(__('api.analise.em_andamento'), ErrorCode::Conflict, 409);
        }

        $anexos = $terceiro->anexos()->oldest('id')->get();
        if ($anexos->isEmpty()) {
            return ApiResponse::error(__('api.analise.sem_documentos'), ErrorCode::BusinessRule, 422);
        }

        $analise = $terceiro->analises()->create([
            'status' => AnaliseIa::PENDENTE,
            'modelo' => config('ia.modelo'),
            'ano_referencia' => (int) $request->input('ano', date('Y')),
            'documentos_total' => $anexos->count(),
        ]);
        foreach ($anexos as $anexo) {
            $analise->documentos()->create([
                'anexo_id' => $anexo->id,
                'nome' => $anexo->nome_original,
                'categoria' => $anexo->categoria,
            ]);
        }

        AnalisarDocumentosJob::dispatch($analise->id);

        return ApiResponse::success(
            new AnaliseIaResource($analise->fresh('documentos')),
            __('api.analise.iniciada', ['total' => $anexos->count()]),
            202,
        );
    }

    /** GET /api/terceiros/{terceiro}/analises-ia/{analise} — progresso, métricas e resultado */
    public function show(Terceiro $terceiro, AnaliseIa $analise): JsonResponse
    {
        return ApiResponse::success(new AnaliseIaResource($analise->load('documentos')));
    }
}
