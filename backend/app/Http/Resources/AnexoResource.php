<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnexoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categoria' => $this->categoria,
            'nome' => $this->nome_original,
            'mime' => $this->mime,
            'tamanho' => $this->tamanho,
            'enviado_em' => $this->created_at?->toIso8601String(),
            // relativo: funciona tanto direto no Laravel quanto pelo proxy do Vite
            'download_url' => "/api/terceiros/{$this->terceiro_id}/anexos/{$this->id}/download",
        ];
    }
}
