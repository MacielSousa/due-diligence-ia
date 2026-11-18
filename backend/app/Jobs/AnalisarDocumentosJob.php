<?php

namespace App\Jobs;

use App\Models\AnaliseIa;
use App\Services\Ia\ClienteLlm;
use App\Services\Ia\DocumentoIlegivelException;
use App\Services\Ia\ExtratorDeTexto;
use App\Services\Ia\IaIndisponivelException;
use App\Services\Ia\ValidadorDeAchados;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * JOB 1 — a IA lê e estuda cada documento.
 *
 * Para cada anexo: extrai o texto, limpa, manda para a Llama, valida os achados
 * (travas do código) e guarda tudo com o tempo gasto. No fim, dispara o
 * RegistrarResultadoAnaliseJob, que atualiza a base e calcula o score.
 */
class AnalisarDocumentosJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800; // 30 min: vários documentos num 3B local

    public int $tries = 1;

    public function __construct(public int $analiseId) {}

    public function handle(ExtratorDeTexto $extrator, ClienteLlm $llm, ValidadorDeAchados $validador): void
    {
        $analise = AnaliseIa::find($this->analiseId);
        if (! $analise || ! $analise->emAndamento()) {
            return;
        }

        $analise->update(['status' => AnaliseIa::PROCESSANDO, 'iniciada_em' => now(), 'modelo' => $llm->modelo()]);

        try {
            foreach ($analise->documentos()->with('anexo')->get() as $documento) {
                $documento->update(['status' => 'processando']);
                $inicio = hrtime(true);

                try {
                    if (! $documento->anexo) {
                        throw new DocumentoIlegivelException('O arquivo foi removido antes da análise.');
                    }

                    $texto = $extrator->extrair($documento->anexo);
                    $resposta = $llm->analisar($texto);

                    $documento->update([
                        'status' => 'concluido',
                        'caracteres' => mb_strlen($texto),
                        'tokens_entrada' => $resposta['tokens_entrada'],
                        'tokens_saida' => $resposta['tokens_saida'],
                        'achados' => $validador->validar($resposta['achados'], $texto, $analise->ano_referencia),
                        'resposta_bruta' => $resposta['bruto'],
                        'duracao_ms' => self::ms($inicio),
                    ]);
                } catch (DocumentoIlegivelException $e) {
                    // um documento ruim não derruba a análise inteira
                    $documento->update(['status' => 'ignorado', 'erro' => $e->getMessage(), 'duracao_ms' => self::ms($inicio)]);
                }

                $analise->increment('documentos_processados');
            }
        } catch (IaIndisponivelException $e) {
            $this->falhar($analise, $e->getMessage());

            return;
        }

        // JOB 2: com o resultado da IA em mãos, atualiza a base de dados
        RegistrarResultadoAnaliseJob::dispatch($analise->id);
    }

    /** Erro inesperado (fora os tratados acima). */
    public function failed(Throwable $e): void
    {
        if ($analise = AnaliseIa::find($this->analiseId)) {
            $this->falhar($analise, 'Erro inesperado na análise: '.$e->getMessage());
        }
    }

    private function falhar(AnaliseIa $analise, string $erro): void
    {
        $analise->update([
            'status' => AnaliseIa::FALHOU,
            'erro' => $erro,
            'concluida_em' => now(),
            'duracao_ms' => $analise->iniciada_em ? (int) $analise->iniciada_em->diffInMilliseconds(now()) : null,
        ]);
        $analise->documentos()->where('status', 'processando')->update(['status' => 'pendente']);
    }

    private static function ms(int $inicioNs): int
    {
        return (int) round((hrtime(true) - $inicioNs) / 1_000_000);
    }
}
