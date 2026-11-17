<?php

use Illuminate\Support\Facades\Route;
use Modules\Auditoria\Http\Controllers\AuditoriaController;

Route::get('avaliacoes/{avaliacao}/auditoria', [AuditoriaController::class, 'avaliacao'])->whereNumber('avaliacao');
