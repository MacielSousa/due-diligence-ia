<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Providers;

use Modules\Avaliacao\Console\AvaliarFornecedorCommand;
use Modules\Shared\Providers\ModuleServiceProvider;

final class AvaliacaoServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'avaliacao';
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([AvaliarFornecedorCommand::class]);
        }
    }
}
