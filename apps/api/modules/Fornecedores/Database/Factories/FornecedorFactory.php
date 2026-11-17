<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fornecedores\Models\Fornecedor;
use Modules\Fornecedores\Support\Cnpj;

/** @extends Factory<Fornecedor> */
class FornecedorFactory extends Factory
{
    protected $model = Fornecedor::class;

    public function definition(): array
    {
        return [
            'razao_social' => 'Construtora ' . fake()->unique()->lastName() . ' S.A.',
            'cnpj' => Cnpj::gerar(),
            'segmento' => 'construcao_civil',
            'valor_contrato_estimado' => fake()->randomFloat(2, 1e8, 5e9),
        ];
    }
}
