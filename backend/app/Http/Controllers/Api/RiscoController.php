<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorCode;
use App\Models\Terceiro;
use App\Services\AvaliadorDeRisco;
use App\Services\MontadorDeTerceiros;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints que dependem das classes do desafio (app/Domain/DueDiligence).
 *  - Regra de negócio violada (CPF inválido, ciclo societário...) -> 422 business_rule
 *  - Classe/método do domínio ainda não implementado              -> 501 domain_incomplete
 */
class RiscoController extends Controller
{
    /** GET /api/terceiros/{terceiro}/risco?ano=2026 */
    public function show(Request $request, Terceiro $terceiro, AvaliadorDeRisco $avaliador): JsonResponse
    {
        $ano = $this->ano($request);

        try {
            $resultado = $avaliador->avaliar($terceiro, $ano);
        } catch (\LogicException $e) { // InvalidArgumentException também é LogicException
            return ApiResponse::error($e->getMessage(), ErrorCode::BusinessRule, 422);
        } catch (\Error $e) {
            return $this->dominioIncompleto($e);
        }

        return ApiResponse::success([
            'terceiro_id' => $terceiro->id,
            'nome' => $terceiro->nome,
            'ano' => $ano,
            ...$resultado,
        ], __('api.risco.calculado'));
    }

    /** GET /api/relatorio-risco?ano=2026 */
    public function relatorio(Request $request, AvaliadorDeRisco $avaliador): JsonResponse
    {
        $ano = $this->ano($request);
        $linhas = [];
        $montador = MontadorDeTerceiros::comTodos(); // 3 consultas para o relatório inteiro (sem N+1)

        foreach ($montador->carregados() as $terceiro) {
            $linha = [
                'id' => $terceiro->id,
                'nome' => $terceiro->nome,
                'tipo' => $terceiro->tipo,
                'score' => null,
                'nivel' => null,
                'erro' => null,
            ];

            try {
                $linha = [...$linha, ...$avaliador->avaliar($terceiro, $ano, $montador)];
            } catch (\LogicException $e) {
                $linha['erro'] = $e->getMessage(); // um terceiro com problema não derruba o relatório
            } catch (\Error $e) {
                return $this->dominioIncompleto($e);
            }

            $linhas[] = $linha;
        }

        usort($linhas, fn ($a, $b) => ($b['score'] ?? -1) <=> ($a['score'] ?? -1));

        return ApiResponse::success($linhas, meta: ['ano' => $ano]);
    }

    private function ano(Request $request): int
    {
        $request->validate(['ano' => ['sometimes', 'integer', 'between:1900,2100']]);

        return (int) $request->query('ano', date('Y'));
    }

    private function dominioIncompleto(\Error $e): JsonResponse
    {
        // detail = mensagem técnica do PHP, ajuda a saber o que falta implementar
        return ApiResponse::error(__('api.dominio_incompleto'), ErrorCode::DomainIncomplete, 501, detail: $e->getMessage());
    }
}
