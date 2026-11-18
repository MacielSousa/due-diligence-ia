<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usa o cabeçalho Accept-Language (enviado pelo frontend) para escolher
 * o idioma das mensagens da API, inclusive as de validação.
 */
class DefinirIdioma
{
    private const SUPORTADOS = ['pt_BR', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('Accept-Language')) {
            // "pt-BR,pt;q=0.9,en;q=0.8" -> "pt_BR"
            App::setLocale($request->getPreferredLanguage(self::SUPORTADOS));
        }

        return $next($request);
    }
}
