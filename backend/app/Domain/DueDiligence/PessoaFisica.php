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

    public function explicarRisco(int $anoReferencia, array $cadeia = []): array
    {
        $riscoProprio = $this->riscoProprio($anoReferencia);
        $adicionalPep = $this->pep ? self::ADICIONAL_PEP : 0;

        return $this->resultado('pf', [
            'apontamentos' => $this->explicarApontamentos($anoReferencia),
            'risco_proprio' => $riscoProprio,
            'pep' => $this->pep,
            'adicional_pep' => $adicionalPep,
            'socios' => [],
        ], $riscoProprio + $adicionalPep);
    }
}
