<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApontamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'ano' => $this->ano,
            'origem' => $this->origem ?? 'manual',
            'trecho' => $this->trecho,
            'anexo_id' => $this->anexo_id,
        ];
    }
}
