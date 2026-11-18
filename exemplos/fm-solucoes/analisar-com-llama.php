<?php
/**
 * Avalia um modelo do Ollama lendo os documentos fictícios da FM Soluções.
 *
 *  map:     cada documento .txt vai para o modelo, que devolve achados em JSON
 *  travas:  o CÓDIGO descarta achado com tipo inválido, sem trecho literal no documento,
 *           tirado de certidão negativa ou com ano que não aparece no documento
 *  reduce:  classificação final (regra eliminatória aplicada pelo código)
 *  métrica: compara com o gabarito (achados_esperados em dados-fm-solucoes.json)
 *
 * Uso:  php analisar-com-llama.php [modelo] [url-ollama]
 *       php analisar-com-llama.php llama3.2:3b      # modelo base (prompt com as regras)
 *       php analisar-com-llama.php due-diligence    # modelo especializado (Modelfile)
 */

$modelo = $argv[1] ?? 'due-diligence';
$ollama = rtrim($argv[2] ?? 'http://127.0.0.1:11434', '/');
$pasta = __DIR__;
const TIPOS = ['sancao', 'processo_criminal', 'processo_trabalhista', 'midia_negativa', 'pep', 'debito_fiscal'];

function ollama(string $url, string $modelo, string $prompt): string
{
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode([
            'model' => $modelo, 'prompt' => $prompt, 'stream' => false, 'format' => 'json',
            'options' => ['temperature' => 0, 'num_ctx' => 8192],
        ]),
        'timeout' => 300,
    ]]);
    $resposta = @file_get_contents("$url/api/generate", false, $ctx);
    if ($resposta === false) throw new RuntimeException("Ollama não respondeu em $url");
    return json_decode($resposta, true)['response'] ?? '';
}

/**
 * Limpeza antes de mandar para o modelo: tira marca d'água, cabeçalho e rodapé.
 * (o aviso "DOCUMENTO FICTÍCIO... SEM VALOR LEGAL" fazia o modelo achar que não havia nada a analisar)
 */
function limpar(string $texto): string
{
    $texto = preg_replace('/^.*(DOCUMENTO FICT[IÍ]CIO|SEM VALOR LEGAL).*$\n?/miu', '', $texto);
    $texto = preg_replace('/^\s*P[aá]gina \d+ de \d+\s*$\n?/miu', '', $texto);
    return trim(preg_replace("/\n{3,}/", "\n\n", $texto));
}

function normalizar(string $s): string
{
    $s = str_replace(['“', '”', '"', "\n", '\\(', '\\)'], ['', '', '', ' ', '(', ')'], $s);
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $s)));
}

/** Travas do código: devolve o motivo do descarte ou null se o achado é válido. */
function motivoDescarte(array $a, string $texto): ?string
{
    $tipo = $a['tipo'] ?? '';
    $trecho = (string) ($a['trecho'] ?? '');
    if (! in_array($tipo, TIPOS, true)) return "tipo inválido ($tipo)";
    if ($trecho === '' || ! str_contains(normalizar($texto), normalizar($trecho))) return 'trecho não existe no documento';
    if (preg_match('/n[aã]o\s+consta|nada\s+consta/iu', $trecho)) return 'trecho de certidão negativa';
    $ano = (int) ($a['ano'] ?? 0);
    if ($ano < 1990 || $ano > (int) date('Y') || ! str_contains($texto, (string) $ano)) return "ano sem evidência ($ano)";
    return null;
}

// o modelo base precisa das regras no prompt; o "due-diligence" já tem tudo no Modelfile
$ehBase = str_starts_with($modelo, 'llama');
$regras = <<<TXT
Você é analista de due diligence de fornecedores. Leia o DOCUMENTO e liste os achados de risco.
Responda SOMENTE JSON: {"achados": [{"tipo": "sancao|processo_criminal|processo_trabalhista|midia_negativa|pep|debito_fiscal",
"ano": 2026, "envolvido": "empresa ou sócio", "trecho": "cópia LITERAL de uma frase do documento", "eliminatorio": true}]}
Regras: não invente; certidão NEGATIVA ("NÃO CONSTA") não é achado; eliminatorio só para sanção vigente
que impede de licitar/contratar; ignore o aviso de documento fictício.

TXT;

$gabarito = json_decode(file_get_contents("$pasta/dados-fm-solucoes.json"), true)['achados_esperados'];
$esperados = array_map(fn ($e) => $e['documento'] . '|' . $e['tipo'], $gabarito);

$validos = [];
$descartados = 0;
$inicio = microtime(true);

foreach (glob("$pasta/0*.txt") as $arquivo) {
    $nome = basename($arquivo, '.txt');
    $texto = limpar(file_get_contents($arquivo));
    $t0 = microtime(true);
    $achados = json_decode(ollama($ollama, $modelo, ($ehBase ? $regras : '') . "DOCUMENTO:\n" . $texto), true)['achados'] ?? [];

    printf("\n[%s] %d achado(s) em %.1fs\n", $nome, count($achados), microtime(true) - $t0);
    foreach ($achados as $a) {
        $motivo = motivoDescarte($a, $texto);
        printf("  %s %-20s %-5s %s\n     \"%s\"\n",
            $motivo ? "✘ ($motivo)" : '✔', $a['tipo'] ?? '?', $a['ano'] ?? '?',
            !empty($a['eliminatorio']) ? '[ELIMINATÓRIO]' : '', mb_strimwidth((string) ($a['trecho'] ?? ''), 0, 100, '…'));
        if ($motivo) { $descartados++; continue; }
        $validos[$nome . '|' . $a['tipo']] = $a + ['documento' => $nome];
    }
}

// métricas contra o gabarito (por documento + tipo)
$acertos = count(array_intersect(array_keys($validos), $esperados));
$precisao = $validos ? $acertos / count($validos) : 0;
$cobertura = $acertos / count($esperados);
$temEliminatorio = (bool) array_filter($validos, fn ($a) => !empty($a['eliminatorio']) && $a['tipo'] === 'sancao');
$nivel = $temEliminatorio ? 'ELEVADO' : (count($validos) >= 2 ? 'MEDIO' : 'BAIXO');

printf("\n=== %s ===\n", $modelo);
printf("Achados válidos: %d | descartados pelas travas: %d\n", count($validos), $descartados);
printf("Acertos: %d de %d esperados | precisão %.0f%% | cobertura %.0f%%\n", $acertos, count($esperados), $precisao * 100, $cobertura * 100);
printf("Faltaram: %s\n", implode(', ', array_diff($esperados, array_keys($validos))) ?: 'nenhum');
printf("Sobraram (falsos positivos): %s\n", implode(', ', array_diff(array_keys($validos), $esperados)) ?: 'nenhum');
printf("Nível final (regra do código): %s (esperado: ELEVADO)\n", $nivel);
printf("Tempo total: %.1fs\n", microtime(true) - $inicio);

file_put_contents("$pasta/resultado-$modelo.json", json_encode([
    'modelo' => $modelo, 'acertos' => $acertos, 'esperados' => count($esperados),
    'precisao' => round($precisao, 2), 'cobertura' => round($cobertura, 2),
    'descartados' => $descartados, 'nivel' => $nivel, 'achados_validos' => array_values($validos),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
