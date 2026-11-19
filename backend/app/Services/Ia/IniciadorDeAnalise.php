<?php

namespace App\Services\Ia;

use App\Jobs\AnalisarDocumentosJob;
use App\Models\AnaliseIa;
use App\Models\Terceiro;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Cria a análise (uma linha por documento) e dispara o Job 1.
 * Usado pela API (AnaliseIaController) e pelo terminal (php artisan ia:analisar).
 *
 * Idempotência: só pode existir UMA análise em andamento por terceiro. A verificação e a
 * criação acontecem numa transação com a linha do terceiro travada (SELECT ... FOR UPDATE):
 * se dois pedidos chegam juntos (duplo clique, retry do cliente), o segundo espera o primeiro
 * gravar, enxerga a análise pendente e recebe 409 — em vez de disparar a IA duas vezes.
 */
class IniciadorDeAnalise
{
    /** @throws DomainException quando já há análise em andamento ou não há documentos */
    public function iniciar(Terceiro $terceiro, int $ano): AnaliseIa
    {
        $analise = DB::transaction(function () use ($terceiro, $ano) {
            Terceiro::whereKey($terceiro->id)->lockForUpdate()->first();

            if ($terceiro->analises()->whereIn('status', [AnaliseIa::PENDENTE, AnaliseIa::PROCESSANDO])->exists()) {
                throw new DomainException(__('api.analise.em_andamento'), 409);
            }

            $anexos = $terceiro->anexos()->oldest('id')->get();
            if ($anexos->isEmpty()) {
                throw new DomainException(__('api.analise.sem_documentos'), 422);
            }

            $analise = $terceiro->analises()->create([
                'status' => AnaliseIa::PENDENTE,
                'modelo' => config('ia.modelo'),
                'ano_referencia' => $ano,
                'documentos_total' => $anexos->count(),
            ]);

            foreach ($anexos as $anexo) {
                $analise->documentos()->create([
                    'anexo_id' => $anexo->id,
                    'nome' => $anexo->nome_original,
                    'categoria' => $anexo->categoria,
                ]);
            }

            return $analise;
        });

        // Só depois do commit: o worker nunca pega um id que ainda não está gravado no banco.
        AnalisarDocumentosJob::dispatch($analise->id);

        return $analise;
    }
}
