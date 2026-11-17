<?php

namespace Modules\Fornecedores\Tests\Integration;

use Modules\Fornecedores\Models\Fornecedor;
use PHPUnit\Framework\Attributes\Test;
use Tests\IntegrationTestCase;

class FornecedorApiTest extends IntegrationTestCase
{
    #[Test]
    public function cadastra_fornecedor_normalizando_o_cnpj(): void
    {
        $this->postJson('/api/fornecedores', [
            'razao_social' => 'Construtora Teste S.A.',
            'cnpj' => '11.222.333/0001-81',
            'valor_contrato_estimado' => 1500000000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.cnpj', '11222333000181')
            ->assertJsonPath('data.cnpj_formatado', '11.222.333/0001-81');

        $this->assertDatabaseHas('fornecedores', ['cnpj' => '11222333000181']);
    }

    #[Test]
    public function rejeita_cnpj_invalido_e_duplicado(): void
    {
        Fornecedor::factory()->create(['cnpj' => '11222333000181']);

        $this->postJson('/api/fornecedores', ['razao_social' => 'X', 'cnpj' => '11222333000182'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnpj');

        $this->postJson('/api/fornecedores', ['razao_social' => 'X', 'cnpj' => '11222333000181'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnpj');
    }

    #[Test]
    public function lista_e_exibe_fornecedores(): void
    {
        $fornecedores = Fornecedor::factory()->count(3)->create();

        $this->getJson('/api/fornecedores')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson("/api/fornecedores/{$fornecedores[0]->id}")
            ->assertOk()
            ->assertJsonPath('data.razao_social', $fornecedores[0]->razao_social);
    }
}
