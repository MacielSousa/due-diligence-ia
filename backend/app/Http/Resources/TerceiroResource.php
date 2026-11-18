<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerceiroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'nome' => $this->nome,
            'documento' => $this->documento,
            'pep' => $this->pep,
            'apontamentos_count' => $this->whenCounted('apontamentos'),
            'socios_count' => $this->whenCounted('socios'),
            'apontamentos' => ApontamentoResource::collection($this->whenLoaded('apontamentos')),
            'socios' => SocioResource::collection($this->whenLoaded('socios')),
            'anexos' => AnexoResource::collection($this->whenLoaded('anexos')),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
