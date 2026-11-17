<?php

declare(strict_types=1);

namespace Modules\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base de todo módulo: carrega automaticamente migrations, rotas de API e config
 * a partir da pasta do módulo (modules/<Nome>/{Database/Migrations,routes/api.php,config.php}).
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Nome do módulo, usado como chave de config (ex.: "ia" → config('ia.*')). */
    abstract protected function name(): string;

    public function register(): void
    {
        $config = $this->modulePath('config.php');

        if (is_file($config)) {
            $this->mergeConfigFrom($config, $this->name());
        }
    }

    public function boot(): void
    {
        $migrations = $this->modulePath('Database/Migrations');
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $routes = $this->modulePath('routes/api.php');
        if (is_file($routes) && ! $this->app->routesAreCached()) {
            Route::prefix('api')->middleware('api')->group($routes);
        }
    }

    protected function modulePath(string $path = ''): string
    {
        $providerDir = dirname((new ReflectionClass($this))->getFileName());

        return dirname($providerDir) . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
