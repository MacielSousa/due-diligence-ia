<?php

use Illuminate\Support\Facades\Route;
use Modules\Documentos\Http\Controllers\DocumentoController;

Route::get('documentos/tipos', [DocumentoController::class, 'tipos']);
Route::get('fornecedores/{fornecedor}/documentos', [DocumentoController::class, 'index']);
Route::post('fornecedores/{fornecedor}/documentos', [DocumentoController::class, 'store']);
