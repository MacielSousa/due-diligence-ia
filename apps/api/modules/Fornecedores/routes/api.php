<?php

use Illuminate\Support\Facades\Route;
use Modules\Fornecedores\Http\Controllers\FornecedorController;

Route::apiResource('fornecedores', FornecedorController::class)
    ->only(['index', 'store', 'show'])
    ->parameters(['fornecedores' => 'fornecedor']);
