<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnexoRequest;
use App\Http\Resources\AnexoResource;
use App\Http\Responses\ApiResponse;
use App\Models\Anexo;
use App\Models\Terceiro;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    /** GET /api/terceiros/{terceiro}/anexos */
    public function index(Terceiro $terceiro): JsonResponse
    {
        return ApiResponse::success(AnexoResource::collection($terceiro->anexos()->latest()->get()));
    }

    /** POST /api/terceiros/{terceiro}/anexos (multipart: categoria + arquivo) */
    public function store(AnexoRequest $request, Terceiro $terceiro): JsonResponse
    {
        $arquivo = $request->file('arquivo');

        // nome aleatório no disco (evita colisão e nomes maliciosos); o nome original fica no banco
        $caminho = $arquivo->store("terceiros/{$terceiro->id}/anexos", Anexo::DISCO);

        $anexo = $terceiro->anexos()->create([
            'categoria' => $request->validated('categoria'),
            'nome_original' => $arquivo->getClientOriginalName(),
            'caminho' => $caminho,
            'mime' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);

        return ApiResponse::created(new AnexoResource($anexo), __('api.anexo.criado'));
    }

    /** GET /api/terceiros/{terceiro}/anexos/{anexo}/download — devolve o arquivo (não é JSON) */
    public function download(Terceiro $terceiro, Anexo $anexo): StreamedResponse
    {
        abort_unless(Storage::disk(Anexo::DISCO)->exists($anexo->caminho), 404);

        return Storage::disk(Anexo::DISCO)->download($anexo->caminho, $anexo->nome_original);
    }

    /** DELETE /api/terceiros/{terceiro}/anexos/{anexo} */
    public function destroy(Terceiro $terceiro, Anexo $anexo): JsonResponse
    {
        $anexo->delete(); // o model apaga o arquivo do disco

        return ApiResponse::success(message: __('api.anexo.excluido'));
    }
}
