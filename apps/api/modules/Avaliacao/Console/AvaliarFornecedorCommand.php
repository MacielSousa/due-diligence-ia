<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Console;

use Illuminate\Console\Command;
use Modules\Avaliacao\Models\Avaliacao;
use Modules\Avaliacao\Services\AvaliacaoService;
use Modules\Fornecedores\Models\Fornecedor;

/** Executa a avaliação de forma síncrona no terminal (útil para demo e depuração). */
class AvaliarFornecedorCommand extends Command
{
    protected $signature = 'risco:avaliar {fornecedor : ID do fornecedor}';

    protected $description = 'Avalia o risco de contratação de um fornecedor (síncrono)';

    public function handle(AvaliacaoService $service): int
    {
        $fornecedor = Fornecedor::findOrFail($this->argument('fornecedor'));
        $avaliacao = $service->executar(Avaliacao::create(['fornecedor_id' => $fornecedor->id]));
        $resultado = $avaliacao->resultado;

        $this->newLine();
        $this->line("<options=bold>{$fornecedor->razao_social}</> ({$fornecedor->cnpjFormatado()})");
        $this->line(sprintf(
            'Risco: <options=bold>%s</>  |  Score: %.1f  |  Confiança: %.2f  |  Revisão humana: %s',
            $resultado['classificacao_rotulo'],
            $resultado['score'],
            $resultado['confianca'],
            $resultado['requer_revisao_humana'] ? 'SIM' : 'não',
        ));

        $this->table(
            ['Dimensão', 'Nota', 'Justificativa'],
            array_map(fn ($d) => [$d['rotulo'], $d['nota'], $d['justificativa']], $resultado['dimensoes']),
        );

        $this->line('<options=bold>Motivos</>');
        foreach ($resultado['motivos'] as $motivo) {
            $this->line("  • {$motivo}");
        }
        $this->line("<options=bold>Recomendação:</> {$resultado['recomendacao']}");

        return self::SUCCESS;
    }
}
