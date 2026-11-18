<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

class PessoaFisica extends Terceiro
{
    // TODO Nível 1: validação real de CPF (dígitos verificadores em laço)
    // TODO Nível 2: risco = soma dos pesos + 20 se PEP (teto 100)
}
