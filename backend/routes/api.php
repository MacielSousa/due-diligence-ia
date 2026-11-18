<?php

use App\Http\Controllers\Api\AnexoController;
use App\Http\Controllers\Api\ApontamentoController;
use App\Http\Controllers\Api\RiscoController;
use App\Http\Controllers\Api\SocioController;
use App\Http\Controllers\Api\TerceiroController;
use Illuminate\Support\Facades\Route;

// CRUD de terceiros (pessoas físicas e jurídicas)
Route::apiResource('terceiros', TerceiroController::class)
    ->parameters(['terceiros' => 'terceiro']);

// Sub-recursos: o {apontamento}/{socio} precisa pertencer ao {terceiro} da URL
Route::scopeBindings()->prefix('terceiros/{terceiro}')->group(function () {
    Route::post('apontamentos', [ApontamentoController::class, 'store']);
    Route::delete('apontamentos/{apontamento}', [ApontamentoController::class, 'destroy']);

    Route::post('socios', [SocioController::class, 'store']);
    Route::delete('socios/{socio}', [SocioController::class, 'destroy']);

    Route::get('anexos', [AnexoController::class, 'index']);
    Route::post('anexos', [AnexoController::class, 'store']);
    Route::get('anexos/{anexo}/download', [AnexoController::class, 'download']);
    Route::delete('anexos/{anexo}', [AnexoController::class, 'destroy']);

    Route::get('risco', [RiscoController::class, 'show']);
});

Route::get('relatorio-risco', [RiscoController::class, 'relatorio']);
