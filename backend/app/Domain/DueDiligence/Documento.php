<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

/**
 * Validação de CPF e CNPJ pelos dígitos verificadores.
 * PHP puro: usado pelo domínio (PessoaFisica/PessoaJuridica) e pelas regras de validação da API.
 */
final class Documento
{
    public static function somenteDigitos(string $documento): string
    {
        return preg_replace('/\D/', '', $documento) ?? '';
    }

    public static function cpfValido(string $cpf): bool
    {
        $cpf = self::somenteDigitos($cpf);

        if (strlen($cpf) !== 11 || self::todosIguais($cpf)) {
            return false;
        }

        // 1º DV: 9 dígitos × pesos 10..2 | 2º DV: 10 dígitos × pesos 11..2
        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $resto = ($soma * 10) % 11;
            $digito = $resto === 10 ? 0 : $resto;

            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(string $cnpj): bool
    {
        $cnpj = self::somenteDigitos($cnpj);

        if (strlen($cnpj) !== 14 || self::todosIguais($cnpj)) {
            return false;
        }

        $pesosPorDigito = [
            12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($pesosPorDigito as $posicao => $pesos) {
            $soma = 0;
            foreach ($pesos as $i => $peso) {
                $soma += (int) $cnpj[$i] * $peso;
            }

            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;

            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    /** 111.111.111-11 passa no cálculo, mas não é um documento válido. */
    private static function todosIguais(string $digitos): bool
    {
        return preg_match('/^(\d)\1+$/', $digitos) === 1;
    }
}
