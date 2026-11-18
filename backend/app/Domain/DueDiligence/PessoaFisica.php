<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

class PessoaFisica extends Terceiro
{
    /** Acréscimo no score para pessoa politicamente exposta. */
    private const ADICIONAL_PEP = 20;

    public function __construct(
        string $nome,
        string $cpf,
        private bool $pep = false,
    ) {
        parent::__construct($nome, $cpf);
    }

    public function isPep(): bool
    {
        return $this->pep;
    }

    protected function documentoValido(string $documento): bool
    {
        return Documento::cpfValido($documento);
    }

    public function calcularRisco(int $anoReferencia, array $cadeia = []): float
    {
        $score = $this->riscoProprio($anoReferencia) + ($this->pep ? self::ADICIONAL_PEP : 0);

        return $this->limitar($score);
    }
}
