<?php

declare(strict_types=1);

namespace Modules\Documentos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Documentos\Models\Documento;

/** @mixin Documento */
class DocumentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fornecedor_id' => $this->fornecedor_id,
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->label(),
            'titulo' => $this->titulo,
            'emitido_em' => $this->emitido_em?->toDateString(),
            'sha256' => $this->sha256,
            'tamanho_bytes' => $this->tamanho_bytes,
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
