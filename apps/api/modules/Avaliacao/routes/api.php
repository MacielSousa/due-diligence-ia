<?php

use Illuminate\Support\Facades\Route;
use Modules\Avaliacao\Http\Controllers\AvaliacaoController;

Route::get('fornecedores/{fornecedor}/avaliacoes', [AvaliacaoController::class, 'index']);
Route::post('fornecedores/{fornecedor}/avaliacoes', [AvaliacaoController::class, 'store']);
Route::get('avaliacoes/{avaliacao}', [AvaliacaoController::class, 'show'])->name('avaliacoes.show');
Route::post('avaliacoes/{avaliacao}/decisao', [AvaliacaoController::class, 'decidir']);
