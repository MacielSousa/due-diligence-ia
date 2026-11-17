<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Fornecedores\Models\Fornecedor;

/** @mixin Fornecedor */
class FornecedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'razao_social' => $this->razao_social,
            'cnpj' => $this->cnpj,
            'cnpj_formatado' => $this->cnpjFormatado(),
            'segmento' => $this->segmento,
            'valor_contrato_estimado' => $this->valor_contrato_estimado,
            'observacoes' => $this->observacoes,
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
