<?php

namespace App\Services;

use App\Domain\DueDiligence\Apontamento as ApontamentoDominio;
use App\Domain\DueDiligence\PessoaFisica;
use App\Domain\DueDiligence\PessoaJuridica;
use App\Domain\DueDiligence\Terceiro as TerceiroDominio;
use App\Models\Terceiro;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Converte os models do banco (Eloquent) nos objetos de domínio do desafio (Data Mapper).
 *
 * Guarda cada objeto já montado pelo id (Identity Map): assim o mesmo sócio que aparece
 * por dois caminhos vira o MESMO objeto, e um ciclo A -> B -> A não gera
 * recursão infinita aqui (quem detecta o ciclo é o domínio, no calcularRisco).
 *
 * Sem N+1: quando recebe os terceiros já carregados (com apontamentos e sócios), monta
 * qualquer quantidade sem ir ao banco — o relatório inteiro sai em 3 consultas fixas.
 * Sem eles (um terceiro só), carrega o que precisar sob demanda.
 */
class MontadorDeTerceiros
{
    /** @var array<int, TerceiroDominio> */
    private array $montados = [];

    /**
     * @param  Collection<int, Terceiro>|null  $carregados  terceiros por id, com apontamentos e socios carregados
     */
    public function __construct(private ?Collection $carregados = null) {}

    /** Todos os terceiros (ordenados) + um montador que usa o que já veio do banco. */
    public static function comTodos(string $ordem = 'nome'): self
    {
        return new self(Terceiro::with(['apontamentos', 'socios'])->orderBy($ordem)->get()->keyBy('id'));
    }

    /** @return Collection<int, Terceiro> */
    public function carregados(): Collection
    {
        return $this->carregados ?? collect();
    }

    public function montar(Terceiro $model): TerceiroDominio
    {
        if (isset($this->montados[$model->id])) {
            return $this->montados[$model->id];
        }

        $model = $this->carregados?->get($model->id) ?? $model;

        $terceiro = $model->isPj()
            ? new PessoaJuridica($model->nome, $model->documento)
            : new PessoaFisica($model->nome, $model->documento, pep: $model->pep);

        $this->montados[$model->id] = $terceiro; // antes dos sócios, por causa de ciclos

        try {
            foreach ($model->apontamentos as $apontamento) {
                $terceiro->adicionarApontamento(new ApontamentoDominio($apontamento->tipo, $apontamento->ano));
            }

            if ($terceiro instanceof PessoaJuridica) {
                foreach ($model->socios as $vinculo) {
                    $socio = $this->carregados?->get($vinculo->socio_id) ?? $vinculo->socio;
                    $terceiro->adicionarSocio($this->montar($socio), (float) $vinculo->participacao);
                }
            }
        } catch (Throwable $e) {
            // não deixa um objeto montado pela metade no cache (o montador é reaproveitado no relatório)
            unset($this->montados[$model->id]);
            throw $e;
        }

        return $terceiro;
    }
}
