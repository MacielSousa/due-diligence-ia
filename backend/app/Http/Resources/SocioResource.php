<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participacao' => $this->participacao,
            'socio' => $this->whenLoaded('socio', fn () => [
                'id' => $this->socio->id,
                'nome' => $this->socio->nome,
                'tipo' => $this->socio->tipo,
            ]),
        ];
    }
}
