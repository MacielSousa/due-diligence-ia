<?php

namespace App\Services;

use App\Domain\DueDiligence\Apontamento as ApontamentoDominio;
use App\Domain\DueDiligence\PessoaFisica;
use App\Domain\DueDiligence\PessoaJuridica;
use App\Domain\DueDiligence\Terceiro as TerceiroDominio;
use App\Models\Terceiro;

/**
 * Converte os models do banco (Eloquent) nos objetos de domínio do desafio.
 *
 * Guarda cada objeto já montado pelo id: assim o mesmo sócio que aparece
 * por dois caminhos vira o MESMO objeto, e um ciclo A -> B -> A não gera
 * recursão infinita aqui (quem detecta o ciclo é o domínio, no calcularRisco).
 */
class MontadorDeTerceiros
{
    /** @var array<int, TerceiroDominio> */
    private array $montados = [];

    public function montar(Terceiro $model): TerceiroDominio
    {
        if (isset($this->montados[$model->id])) {
            return $this->montados[$model->id];
        }

        $terceiro = $model->isPj()
            ? new PessoaJuridica($model->nome, $model->documento)
            : new PessoaFisica($model->nome, $model->documento, pep: $model->pep);

        $this->montados[$model->id] = $terceiro; // antes dos sócios, por causa de ciclos

        foreach ($model->apontamentos as $apontamento) {
            $terceiro->adicionarApontamento(new ApontamentoDominio($apontamento->tipo, $apontamento->ano));
        }

        if ($terceiro instanceof PessoaJuridica) {
            foreach ($model->socios()->with('socio')->get() as $vinculo) {
                $terceiro->adicionarSocio($this->montar($vinculo->socio), (float) $vinculo->participacao);
            }
        }

        return $terceiro;
    }
}
