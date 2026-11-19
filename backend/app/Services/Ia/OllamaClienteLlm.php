<?php

namespace App\Services\Ia;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Implementação do ClienteLlm para o Ollama (Llama local) — um Adapter: traduz a API HTTP do
 * Ollama para o contrato da aplicação e converte falhas de rede em IaIndisponivelException.
 *
 * As instruções e exemplos ficam no modelo (ia/Modelfile); aqui só mandamos o texto do
 * documento e pedimos a resposta em JSON.
 */
class OllamaClienteLlm implements ClienteLlm
{
    public function modelo(): string
    {
        return config('ia.modelo');
    }

    /**
     * @return array{achados: array<int, mixed>, bruto: string, tokens_entrada: ?int, tokens_saida: ?int}
     */
    public function analisar(string $texto): array
    {
        $texto = mb_substr($texto, 0, config('ia.max_caracteres'));

        try {
            $resposta = Http::timeout(config('ia.timeout'))
                ->acceptJson()
                ->post(rtrim(config('ia.url'), '/').'/api/generate', [
                    'model' => $this->modelo(),
                    'prompt' => "DOCUMENTO:\n".$texto,
                    'stream' => false,
                    'format' => 'json',
                    'options' => ['temperature' => 0, 'num_ctx' => 8192],
                ]);
        } catch (ConnectionException $e) {
            throw new IaIndisponivelException('A IA (Ollama) não respondeu em '.config('ia.url').'.', previous: $e);
        }

        if ($resposta->failed()) {
            throw new IaIndisponivelException('A IA respondeu com erro HTTP '.$resposta->status().': '.($resposta->json('error') ?? 'sem detalhes'));
        }

        $bruto = (string) $resposta->json('response', '');
        $json = json_decode($bruto, true);

        return [
            'achados' => is_array($json['achados'] ?? null) ? $json['achados'] : [],
            'bruto' => $bruto,
            'tokens_entrada' => $resposta->json('prompt_eval_count'),
            'tokens_saida' => $resposta->json('eval_count'),
        ];
    }
}
