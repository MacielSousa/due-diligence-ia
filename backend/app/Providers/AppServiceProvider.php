<?php

namespace App\Providers;

use App\Services\Ia\ClienteLlm;
use App\Services\Ia\OllamaClienteLlm;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Inversão de dependência: o job pede um ClienteLlm; aqui escolhemos qual (Strategy).
        // Novo provedor (ex.: Claude, OpenAI) = nova classe + um "case" aqui.
        $this->app->bind(ClienteLlm::class, fn ($app) => match (config('ia.driver')) {
            'ollama' => $app->make(OllamaClienteLlm::class),
            default => throw new InvalidArgumentException('Driver de IA desconhecido: '.config('ia.driver')),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
