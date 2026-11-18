<?php
declare(strict_types=1);

namespace App\Domain\DueDiligence;

use InvalidArgumentException;
use LogicException;

class PessoaJuridica extends Terceiro
{
    private const PARTICIPACAO_MAXIMA = 100.0;

    /** @var array<int, array{socio: Terceiro, participacao: float}> */
    private array $socios = [];

    protected function documentoValido(string $documento): bool
    {
        return Documento::cnpjValido($documento);
    }

    public function adicionarSocio(Terceiro $socio, float $participacao): void
    {
        if ($participacao <= 0) {
            throw new InvalidArgumentException('A participação deve ser maior que zero.');
        }

        $total = $this->participacaoTotal() + $participacao;
        if ($total > self::PARTICIPACAO_MAXIMA) {
            throw new InvalidArgumentException("Participação total excede 100% em {$this->getNome()}");
        }

        $this->socios[] = ['socio' => $socio, 'participacao' => $participacao];
    }

    public function participacaoTotal(): float
    {
        $total = 0.0;
        foreach ($this->socios as $vinculo) {
            $total += $vinculo['participacao'];
        }

        return $total;
    }

    /**
     * Risco próprio + risco de cada sócio ponderado pela participação.
     *
     * $cadeia guarda os documentos do caminho atual. Como array em PHP é passado
     * POR VALOR, cada ramo recebe a sua cópia: o mesmo sócio por dois caminhos
     * diferentes não é ciclo; só é ciclo quando a empresa reaparece no próprio caminho.
     */
    public function explicarRisco(int $anoReferencia, array $cadeia = []): array
    {
        if (in_array($this->documento, $cadeia, true)) {
            throw new LogicException("Ciclo societário detectado em {$this->getNome()}");
        }
        $cadeia[] = $this->documento;

        $riscoProprio = $this->riscoProprio($anoReferencia);
        $soma = $riscoProprio;
        $socios = [];

        foreach ($this->socios as $vinculo) {
            $explicacao = $vinculo['socio']->explicarRisco($anoReferencia, $cadeia);
            $contribuicao = $explicacao['score'] * $vinculo['participacao'] / 100;
            $soma += $contribuicao;

            $socios[] = [
                'nome' => $explicacao['nome'],
                'participacao' => $vinculo['participacao'],
                'score_socio' => $explicacao['score'],
                'contribuicao' => round($contribuicao, 2),
                'explicacao' => $explicacao,
            ];
        }

        return $this->resultado('pj', [
            'apontamentos' => $this->explicarApontamentos($anoReferencia),
            'risco_proprio' => $riscoProprio,
            'pep' => false,
            'adicional_pep' => 0,
            'socios' => $socios,
        ], $soma);
    }
}
