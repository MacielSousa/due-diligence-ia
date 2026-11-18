<?php

namespace App\Jobs;

use App\Models\AnaliseIa;
use App\Services\AvaliadorDeRisco;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

/**
 * JOB 2 — atualiza a base com o que a IA encontrou e registra a decisão.
 *
 *  1. troca os apontamentos de origem "ia" do terceiro pelos achados válidos desta análise
 *     (os apontamentos cadastrados à mão não são tocados)
 *  2. calcula o score com o domínio, com a explicação peso a peso
 *  3. grava nível, recomendação, achado eliminatório e as métricas da análise
 */
class RegistrarResultadoAnaliseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $analiseId) {}

    public function handle(AvaliadorDeRisco $avaliador): void
    {
        $analise = AnaliseIa::with('terceiro', 'documentos')->find($this->analiseId);
        if (! $analise || $analise->status !== AnaliseIa::PROCESSANDO) {
            return;
        }

        $achados = $analise->documentos->flatMap(
            fn ($doc) => collect($doc->achados ?? [])->map(fn ($a) => $a + ['anexo_id' => $doc->anexo_id, 'documento' => $doc->nome])
        );
        $validos = $achados->where('valido', true);

        DB::transaction(function () use ($analise, $validos) {
            $terceiro = $analise->terceiro;
            $terceiro->apontamentos()->where('origem', 'ia')->delete();

            foreach ($validos->where('entra_no_score', true) as $achado) {
                $terceiro->apontamentos()->create([
                    'tipo' => $achado['tipo'],
                    'ano' => $achado['ano'],
                    'origem' => 'ia',
                    'trecho' => $achado['trecho'],
                    'anexo_id' => $achado['anexo_id'],
                    'analise_ia_id' => $analise->id,
                ]);
            }
        });

        $eliminatorio = $validos->contains(fn ($a) => $a['eliminatorio']);

        try {
            $explicacao = $avaliador->explicar($analise->terceiro->fresh(), $analise->ano_referencia);
        } catch (LogicException $e) { // ex.: ciclo societário, CPF inválido de um sócio
            $analise->update($this->metricas($analise, $validos->count(), $achados->count()) + [
                'status' => AnaliseIa::FALHOU,
                'erro' => 'Os achados foram registrados, mas o score não pôde ser calculado: '.$e->getMessage(),
                'eliminatorio' => $eliminatorio,
            ]);

            return;
        }

        $explicacao['achados_informativos'] = $validos->where('entra_no_score', false)->values()->all();

        $analise->update($this->metricas($analise, $validos->count(), $achados->count()) + [
            'status' => AnaliseIa::CONCLUIDA,
            'score' => $explicacao['score'],
            'nivel' => $explicacao['nivel'],
            'eliminatorio' => $eliminatorio,
            'recomendacao' => self::recomendacao($explicacao['nivel'], $eliminatorio),
            'explicacao' => $explicacao,
        ]);
    }

    public function failed(Throwable $e): void
    {
        AnaliseIa::whereKey($this->analiseId)->update([
            'status' => AnaliseIa::FALHOU,
            'erro' => 'Erro ao registrar o resultado: '.$e->getMessage(),
            'concluida_em' => now(),
        ]);
    }

    /** Achado eliminatório sempre reprova, independente do score. */
    public static function recomendacao(string $nivel, bool $eliminatorio): string
    {
        if ($eliminatorio) {
            return 'reprovar';
        }

        return match ($nivel) {
            'CRITICO' => 'reprovar',
            'ALTO' => 'revisao_comite',
            'MEDIO' => 'aprovar_com_ressalvas',
            default => 'aprovar',
        };
    }

    private function metricas(AnaliseIa $analise, int $validos, int $total): array
    {
        return [
            'achados_validos' => $validos,
            'achados_descartados' => $total - $validos,
            'concluida_em' => now(),
            'duracao_ms' => (int) $analise->iniciada_em?->diffInMilliseconds(now()),
        ];
    }
}
