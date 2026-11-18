<?php
declare(strict_types=1);

/*
 * LIVE CODING — Motor de Due Diligence (nível hard)
 * Implemente as classes em app/Domain/DueDiligence/. NÃO altere os testes abaixo.
 * Rodar:  composer desafio   (ou: php desafio.php)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Domain\DueDiligence\AnalisadorDeRisco;
use App\Domain\DueDiligence\Apontamento;
use App\Domain\DueDiligence\PessoaFisica;
use App\Domain\DueDiligence\PessoaJuridica;

// ================== TESTES (não alterar) ==================

$ana = new PessoaFisica('Ana Souza', '529.982.247-25');
$ana->adicionarApontamento(new Apontamento('midia_negativa', 2024));

$carlos = new PessoaFisica('Carlos Lima', '123.456.789-09', pep: true);
$carlos->adicionarApontamento(new Apontamento('processo_criminal', 2018));
$carlos->adicionarApontamento(new Apontamento('sancao', 2025));

$holding = new PessoaJuridica('Holding XPTO', '11.222.333/0001-81');
$holding->adicionarSocio($carlos, 60);

$abc = new PessoaJuridica('Fornecedor ABC', '44.555.666/0001-81');
$abc->adicionarApontamento(new Apontamento('processo_trabalhista', 2023));
$abc->adicionarApontamento(new Apontamento('processo_trabalhista', 2022));
$abc->adicionarSocio($ana, 50);
$abc->adicionarSocio($holding, 50);

$analisador = new AnalisadorDeRisco(2026);
foreach ([$ana, $carlos, $holding, $abc] as $t) {
    $analisador->adicionar($t);
}
foreach ($analisador->relatorio() as $linha) {
    printf("%-16s %5.1f  %s\n", $linha['nome'], $linha['score'], $linha['nivel']);
}

echo "---\n";
try {
    new PessoaFisica('Fake', '111.111.111-11');
} catch (InvalidArgumentException $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
}
try {
    $abc->adicionarSocio($carlos, 10);
} catch (InvalidArgumentException $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
}
$alfa = new PessoaJuridica('Alfa Ltda', '10.000.000/0001-45');
$beta = new PessoaJuridica('Beta Ltda', '20.000.000/0001-07');
$alfa->adicionarSocio($beta, 100);
$beta->adicionarSocio($alfa, 100);
try {
    $alfa->calcularRisco(2026);
} catch (LogicException $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
}
