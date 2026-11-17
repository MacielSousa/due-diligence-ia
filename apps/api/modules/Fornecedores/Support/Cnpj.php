<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Support;

/** Validação e geração de CNPJ (dígitos verificadores). */
final class Cnpj
{
    public static function limpar(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }

    public static function valido(string $cnpj): bool
    {
        $cnpj = self::limpar($cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $base = substr($cnpj, 0, 12);

        return $base . self::digitos($base) === $cnpj;
    }

    public static function gerar(): string
    {
        $base = '';
        for ($i = 0; $i < 8; $i++) {
            $base .= random_int(0, 9);
        }
        $base .= '0001';

        return $base . self::digitos($base);
    }

    private static function digitos(string $base): string
    {
        $d1 = self::digito($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $d2 = self::digito($base . $d1, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $d1 . $d2;
    }

    /** @param list<int> $pesos */
    private static function digito(string $numeros, array $pesos): string
    {
        $soma = 0;
        foreach ($pesos as $i => $peso) {
            $soma += (int) $numeros[$i] * $peso;
        }
        $resto = $soma % 11;

        return (string) ($resto < 2 ? 0 : 11 - $resto);
    }
}
