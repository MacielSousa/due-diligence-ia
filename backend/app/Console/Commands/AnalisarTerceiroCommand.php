<?php

namespace App\Console\Commands;

use App\Models\AnaliseIa;
use App\Models\Terceiro;
use App\Services\Ia\IniciadorDeAnalise;
use DomainException;
use Illuminate\Console\Command;

/**
 * Dispara a análise por IA pelo terminal (mesma lógica da API).
 *
 *   php artisan ia:analisar 5                 # coloca na fila (o worker executa)
 *   php artisan ia:analisar 5 --agora         # executa aqui mesmo, sem worker, e mostra o resultado
 *   php artisan ia:analisar 5 --ano=2025
 *
 * Criado com: php artisan make:command AnalisarTerceiroCommand
 * (no Laravel 12 todo command em app/Console/Commands é registrado automaticamente)
 */
class AnalisarTerceiroCommand extends Command
{
    protected $signature = 'ia:analisar
                            {terceiro : ID do terceiro}
                            {--ano= : Ano de referência do score (padrão: ano atual)}
                            {--agora : Executa os jobs nesta execução, sem precisar do worker}';

    protected $description = 'Dispara a análise por IA dos documentos anexados de um terceiro';

    public function handle(IniciadorDeAnalise $iniciador): int
    {
        $terceiro = Terceiro::find($this->argument('terceiro'));
        if (! $terceiro) {
            $this->error("Terceiro {$this->argument('terceiro')} não encontrado.");

            return self::FAILURE;
        }

        if ($this->option('agora')) {
            config(['queue.default' => 'sync']); // Job 1 e Job 2 rodam em sequência, aqui
        }

        $this->info("Analisando os documentos de {$terceiro->nome} com o modelo ".config('ia.modelo').'...');

        try {
            $analise = $iniciador->iniciar($terceiro, (int) ($this->option('ano') ?: date('Y')));
        } catch (DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('agora')) {
            $this->line("Análise #{$analise->id} na fila. Rode <comment>composer worker</comment> para processar.");

            return self::SUCCESS;
        }

        return $this->mostrarResultado($analise->fresh('documentos'));
    }

    private function mostrarResultado(AnaliseIa $analise): int
    {
        $this->table(
            ['Documento', 'Status', 'Duração', 'Achados válidos', 'Descartados'],
            $analise->documentos->map(fn ($d) => [
                $d->nome,
                $d->status,
                $d->duracao_ms !== null ? round($d->duracao_ms / 1000, 1).' s' : '-',
                collect($d->achados)->where('valido', true)->pluck('tipo')->implode(', ') ?: '-',
                collect($d->achados)->where('valido', false)->count(),
            ])
        );

        if ($analise->status !== AnaliseIa::CONCLUIDA) {
            $this->error("Análise {$analise->status}: {$analise->erro}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->line(sprintf(
            '<info>Score %.1f · %s · recomendação: %s%s</info> (em %.1f s)',
            $analise->score, $analise->nivel, $analise->recomendacao,
            $analise->eliminatorio ? ' · ACHADO ELIMINATÓRIO' : '',
            $analise->duracao_ms / 1000,
        ));

        return self::SUCCESS;
    }
}
