<?php

use Illuminate\Support\Facades\Route;

// O backend é só API: a interface fica no projeto frontend/ (Vue).
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'api' => url('/api'),
    'saude' => url('/up'),
]));
