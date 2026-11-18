<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocioRequest;
use App\Http\Resources\SocioResource;
use App\Http\Responses\ApiResponse;
use App\Models\Socio;
use App\Models\Terceiro;
use Illuminate\Http\JsonResponse;

class SocioController extends Controller
{
    /** POST /api/terceiros/{terceiro}/socios */
    public function store(SocioRequest $request, Terceiro $terceiro): JsonResponse
    {
        $socio = $terceiro->socios()->create($request->validated());
        $socio->load('socio');

        return ApiResponse::created(new SocioResource($socio), __('api.socio.criado'));
    }

    /** DELETE /api/terceiros/{terceiro}/socios/{socio} */
    public function destroy(Terceiro $terceiro, Socio $socio): JsonResponse
    {
        $socio->delete();

        return ApiResponse::success(message: __('api.socio.excluido'));
    }
}
