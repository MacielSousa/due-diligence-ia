<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TerceiroRequest;
use App\Http\Resources\TerceiroResource;
use App\Http\Responses\ApiResponse;
use App\Models\Terceiro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerceiroController extends Controller
{
    /** GET /api/terceiros?tipo=pf|pj&busca=texto */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => ['sometimes', 'in:pf,pj'],
            'busca' => ['sometimes', 'nullable', 'string', 'max:150'],
        ]);

        $query = Terceiro::query()->withCount(['apontamentos', 'socios'])->orderBy('nome');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($busca = trim((string) $request->query('busca'))) {
            $digitos = preg_replace('/\D/', '', $busca);
            $query->where(function ($w) use ($busca, $digitos) {
                $w->where('nome', 'like', "%{$busca}%");
                if ($digitos !== '') {
                    $w->orWhere('documento', 'like', "%{$digitos}%");
                }
            });
        }

        return ApiResponse::success(TerceiroResource::collection($query->get()));
    }

    /** POST /api/terceiros */
    public function store(TerceiroRequest $request): JsonResponse
    {
        $dados = $request->validated();
        // PEP só existe para pessoa física
        $dados['pep'] = $dados['tipo'] === 'pf' && ($dados['pep'] ?? false);

        $terceiro = Terceiro::create($dados);

        return ApiResponse::created(new TerceiroResource($terceiro), __('api.terceiro.criado'));
    }

    /** GET /api/terceiros/{id} */
    public function show(Terceiro $terceiro): JsonResponse
    {
        $terceiro->load([
            'apontamentos' => fn ($q) => $q->orderByDesc('ano'),
            'socios.socio',
            'anexos' => fn ($q) => $q->latest(),
        ]);

        return ApiResponse::success(new TerceiroResource($terceiro));
    }

    /** PUT /api/terceiros/{id} */
    public function update(TerceiroRequest $request, Terceiro $terceiro): JsonResponse
    {
        $dados = $request->validated();
        if ($terceiro->isPj()) {
            $dados['pep'] = false;
        }

        $terceiro->update($dados);

        return ApiResponse::success(new TerceiroResource($terceiro), __('api.terceiro.atualizado'));
    }

    /** DELETE /api/terceiros/{id} */
    public function destroy(Terceiro $terceiro): JsonResponse
    {
        $terceiro->delete(); // apontamentos e vínculos societários caem em cascata

        return ApiResponse::success(message: __('api.terceiro.excluido'));
    }
}
