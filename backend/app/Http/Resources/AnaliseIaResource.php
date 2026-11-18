<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnaliseIaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'modelo' => $this->modelo,
            'ano_referencia' => $this->ano_referencia,
            'progresso' => [
                'processados' => $this->documentos_processados,
                'total' => $this->documentos_total,
            ],
            'metricas' => [
                'criada_em' => $this->created_at?->toIso8601String(),
                'iniciada_em' => $this->iniciada_em?->toIso8601String(),
                'concluida_em' => $this->concluida_em?->toIso8601String(),
                'duracao_ms' => $this->duracao_ms,
                // tempo na fila antes do worker pegar o job
                'espera_ms' => $this->iniciada_em ? (int) $this->created_at->diffInMilliseconds($this->iniciada_em) : null,
                'achados_validos' => $this->achados_validos,
                'achados_descartados' => $this->achados_descartados,
            ],
            'resultado' => $this->status === 'concluida' ? [
                'score' => $this->score,
                'nivel' => $this->nivel,
                'eliminatorio' => $this->eliminatorio,
                'recomendacao' => $this->recomendacao,
                'explicacao' => $this->explicacao,
            ] : null,
            'erro' => $this->erro,
            'documentos' => ! $this->relationLoaded('documentos') ? [] : $this->documentos->map(fn ($d) => [
                'id' => $d->id,
                'anexo_id' => $d->anexo_id,
                'nome' => $d->nome,
                'categoria' => $d->categoria,
                'status' => $d->status,
                'duracao_ms' => $d->duracao_ms,
                'caracteres' => $d->caracteres,
                'tokens_entrada' => $d->tokens_entrada,
                'tokens_saida' => $d->tokens_saida,
                'achados' => $d->achados ?? [],
                'erro' => $d->erro,
            ])->values(),
        ];
    }
}
