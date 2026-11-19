<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Copia os dados do antigo database/database.sqlite para o banco atual (MySQL),
 * mantendo os IDs. Rodar depois de "php artisan migrate" (sem --seed).
 */
class ImportarSqliteCommand extends Command
{
    protected $signature = 'db:importar-sqlite {arquivo=database/database.sqlite}';

    protected $description = 'Importa terceiros, sócios, anexos, apontamentos e análises de um arquivo SQLite';

    /** Ordem respeitando as chaves estrangeiras. */
    private const TABELAS = ['terceiros', 'socios', 'anexos', 'analises_ia', 'analise_ia_documentos', 'apontamentos'];

    public function handle(): int
    {
        $arquivo = base_path($this->argument('arquivo'));

        if (! is_file($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");

            return self::FAILURE;
        }

        config(['database.connections.sqlite_origem' => [
            'driver' => 'sqlite',
            'database' => $arquivo,
            'foreign_key_constraints' => false,
        ]]);
        $origem = DB::connection('sqlite_origem');
        $destino = DB::connection();

        if ($destino->table('terceiros')->exists()) {
            $this->error('O banco de destino já tem terceiros. Rode "php artisan migrate:fresh" antes de importar.');

            return self::FAILURE;
        }

        $destino->transaction(function () use ($origem, $destino) {
            foreach (self::TABELAS as $tabela) {
                $linhas = $origem->table($tabela)->orderBy('id')->get()
                    ->map(fn ($linha) => (array) $linha)->all();

                foreach (array_chunk($linhas, 200) as $lote) {
                    $destino->table($tabela)->insert($lote);
                }

                $this->line(sprintf('  %-22s %d registro(s)', $tabela, count($linhas)));
            }
        });

        $this->info('Importação concluída.');

        return self::SUCCESS;
    }
}
