<?php

namespace Tests\Feature;

use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Escalabilidade: o relatório de risco faz um número FIXO de consultas, não importa quantos
 * terceiros, sócios e apontamentos existam (sem o problema N+1).
 */
class RelatorioRiscoConsultasTest extends TestCase
{
    use RefreshDatabase;

    private const CNPJS = ['11222333000181', '45278245000113', '19876543200021', '49664587000122'];

    private const CPFS = ['52998224725', '12345678909', '98765432100', '24681357928'];

    private function criarEmpresasComSocios(int $quantidade): void
    {
        for ($i = 0; $i < $quantidade; $i++) {
            $empresa = Terceiro::create(['tipo' => 'pj', 'nome' => "Empresa {$i}", 'documento' => self::CNPJS[$i]]);
            $socio = Terceiro::create(['tipo' => 'pf', 'nome' => "Sócio {$i}", 'documento' => self::CPFS[$i]]);
            $socio->apontamentos()->create(['tipo' => 'sancao', 'ano' => 2026]);
            $empresa->apontamentos()->create(['tipo' => 'midia_negativa', 'ano' => 2026]);
            $empresa->socios()->create(['socio_id' => $socio->id, 'participacao' => 50]);
        }
    }

    private function consultasDoRelatorio(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/relatorio-risco?ano=2026')->assertOk();

        return count(DB::getQueryLog());
    }

    public function test_numero_de_consultas_nao_cresce_com_a_quantidade_de_terceiros(): void
    {
        $this->criarEmpresasComSocios(1);
        $com2Terceiros = $this->consultasDoRelatorio();

        Terceiro::query()->delete(); // recomeça com 4x mais terceiros
        $this->criarEmpresasComSocios(4);
        $com8Terceiros = $this->consultasDoRelatorio();

        $this->assertSame($com2Terceiros, $com8Terceiros);
        $this->assertLessThanOrEqual(3, $com8Terceiros);
    }

    public function test_relatorio_continua_calculando_o_score_com_os_socios(): void
    {
        $this->criarEmpresasComSocios(1);

        $this->getJson('/api/relatorio-risco?ano=2026')
            ->assertOk()
            ->assertJsonPath('data.0.nome', 'Sócio 0')   // 40
            ->assertJsonPath('data.0.score', 40)
            ->assertJsonPath('data.1.nome', 'Empresa 0') // 15 + 40 × 50%
            ->assertJsonPath('data.1.score', 35);
    }
}
