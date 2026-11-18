<?php

namespace Database\Seeders;

use App\Models\Terceiro;
use Illuminate\Database\Seeder;

/** Mesmo cenário dos testes do desafio (desafio.php). */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ana = Terceiro::create(['tipo' => 'pf', 'nome' => 'Ana Souza', 'documento' => '52998224725']);
        $ana->apontamentos()->create(['tipo' => 'midia_negativa', 'ano' => 2024]);

        $carlos = Terceiro::create(['tipo' => 'pf', 'nome' => 'Carlos Lima', 'documento' => '12345678909', 'pep' => true]);
        $carlos->apontamentos()->create(['tipo' => 'processo_criminal', 'ano' => 2018]);
        $carlos->apontamentos()->create(['tipo' => 'sancao', 'ano' => 2025]);

        $holding = Terceiro::create(['tipo' => 'pj', 'nome' => 'Holding XPTO', 'documento' => '11222333000181']);
        $holding->socios()->create(['socio_id' => $carlos->id, 'participacao' => 60]);

        $abc = Terceiro::create(['tipo' => 'pj', 'nome' => 'Fornecedor ABC', 'documento' => '44555666000181']);
        $abc->apontamentos()->create(['tipo' => 'processo_trabalhista', 'ano' => 2023]);
        $abc->apontamentos()->create(['tipo' => 'processo_trabalhista', 'ano' => 2022]);
        $abc->socios()->create(['socio_id' => $ana->id, 'participacao' => 50]);
        $abc->socios()->create(['socio_id' => $holding->id, 'participacao' => 50]);
    }
}
