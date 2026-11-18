<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

use InvalidArgumentException;

/** Pessoa física ou jurídica avaliada na diligência. */
abstract class Terceiro
{
    public const RISCO_MAXIMO = 100.0;

    protected string $documento;

    /** @var Apontamento[] */
    private array $apontamentos = [];

    public function __construct(
        private string $nome,
        string $documento,
    ) {
        $digitos = Documento::somenteDigitos($documento);

        if (! $this->documentoValido($digitos)) {
            throw new InvalidArgumentException("Documento inválido: {$documento}");
        }

        $this->documento = $digitos;
    }

    /** CPF para PF, CNPJ para PJ. */
    abstract protected function documentoValido(string $documento): bool;

    /**
     * @param  string[]  $cadeia  documentos já visitados no caminho societário (detecção de ciclo)
     */
    abstract public function calcularRisco(int $anoReferencia, array $cadeia = []): float;

    public function adicionarApontamento(Apontamento $apontamento): void
    {
        $this->apontamentos[] = $apontamento;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getDocumento(): string
    {
        return $this->documento;
    }

    /** @return Apontamento[] */
    public function getApontamentos(): array
    {
        return $this->apontamentos; // array é copiado: quem recebe não altera o estado interno
    }

    /** Soma dos pesos dos apontamentos do próprio terceiro. */
    protected function riscoProprio(int $anoReferencia): float
    {
        $total = 0;
        foreach ($this->apontamentos as $apontamento) {
            $total += $apontamento->peso($anoReferencia);
        }

        return (float) $total;
    }

    protected function limitar(float $score): float
    {
        return min(self::RISCO_MAXIMO, round($score, 1));
    }
}
