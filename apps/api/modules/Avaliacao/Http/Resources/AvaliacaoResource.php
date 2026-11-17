<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Avaliacao\Models\Avaliacao;

/** @mixin Avaliacao */
class AvaliacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fornecedor_id' => $this->fornecedor_id,
            'fornecedor' => $this->whenLoaded('fornecedor', fn () => [
                'id' => $this->fornecedor->id,
                'razao_social' => $this->fornecedor->razao_social,
                'cnpj_formatado' => $this->fornecedor->cnpjFormatado(),
            ]),
            'status' => $this->status->value,
            'nivel' => $this->nivel?->value,
            'nivel_rotulo' => $this->nivel?->label(),
            'score' => $this->score,
            'confianca' => $this->confianca,
            'requer_revisao' => $this->requer_revisao,
            'resultado' => $this->when($request->routeIs('avaliacoes.show'), $this->resultado),
            'documentos_usados' => $this->when($request->routeIs('avaliacoes.show'), $this->documentos_usados),
            'erro' => $this->erro,
            'decisao' => $this->decisao?->value,
            'decidido_por' => $this->decidido_por,
            'justificativa_decisao' => $this->justificativa_decisao,
            'decidido_em' => $this->decidido_em?->toIso8601String(),
            'iniciada_em' => $this->iniciada_em?->toIso8601String(),
            'concluida_em' => $this->concluida_em?->toIso8601String(),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
