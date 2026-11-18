<?php

namespace App\Services\Ia;

/**
 * Travas do CÓDIGO sobre a resposta do modelo: o modelo sugere, o código confere.
 * Um achado só é aceito se tiver tipo válido, evidência literal no documento e ano plausível.
 */
class ValidadorDeAchados
{
    public const TIPOS = ['sancao', 'processo_criminal', 'processo_trabalhista', 'midia_negativa', 'pep', 'debito_fiscal'];

    /** Tipos que entram no score como apontamento; os demais ficam como achado informativo. */
    public const TIPOS_DO_SCORE = ['sancao', 'processo_criminal', 'processo_trabalhista', 'midia_negativa'];

    /**
     * @param  array<int, mixed>  $achados  resposta crua do modelo
     * @return array<int, array{tipo: string, ano: ?int, envolvido: string, trecho: string, eliminatorio: bool, valido: bool, motivo: ?string, entra_no_score: bool}>
     */
    public function validar(array $achados, string $texto, int $anoReferencia): array
    {
        $textoNormalizado = self::normalizar($texto);
        $resultado = [];
        $vistos = [];

        foreach ($achados as $bruto) {
            $tipo = is_array($bruto) ? (string) ($bruto['tipo'] ?? '') : '';
            $trecho = is_array($bruto) ? trim((string) ($bruto['trecho'] ?? '')) : '';
            $ano = is_array($bruto) && is_numeric($bruto['ano'] ?? null) ? (int) $bruto['ano'] : null;

            $motivo = match (true) {
                ! in_array($tipo, self::TIPOS, true) => "Tipo inválido: \"{$tipo}\".",
                $trecho === '' => 'O modelo não citou o trecho do documento.',
                ! str_contains($textoNormalizado, self::normalizar($trecho)) => 'O trecho citado não existe no documento (possível invenção do modelo).',
                (bool) preg_match('/n[aã]o\s+consta|nada\s+consta/iu', $trecho) => 'O trecho é de uma certidão negativa (nada consta).',
                $ano === null || $ano < 1990 || $ano > $anoReferencia => 'Ano ausente ou fora do período válido.',
                ! str_contains($texto, (string) $ano) => "O ano {$ano} não aparece no documento.",
                isset($vistos[$tipo.'|'.self::normalizar($trecho)]) => 'Achado repetido.',
                default => null,
            };

            if ($motivo === null) {
                $vistos[$tipo.'|'.self::normalizar($trecho)] = true;
            }

            $resultado[] = [
                'tipo' => $tipo,
                'ano' => $ano,
                'envolvido' => is_array($bruto) ? trim((string) ($bruto['envolvido'] ?? '')) : '',
                'trecho' => $trecho,
                // só sanção pode ser eliminatória, mesmo que o modelo diga outra coisa
                'eliminatorio' => $tipo === 'sancao' && (bool) ($bruto['eliminatorio'] ?? false),
                'valido' => $motivo === null,
                'motivo' => $motivo,
                'entra_no_score' => $motivo === null && in_array($tipo, self::TIPOS_DO_SCORE, true),
            ];
        }

        return $resultado;
    }

    /** Compara trechos ignorando quebras de linha, espaços duplicados, aspas e maiúsculas. */
    public static function normalizar(string $texto): string
    {
        $texto = str_replace(['“', '”', '"', "\n"], ['', '', '', ' '], $texto);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $texto)));
    }
}
