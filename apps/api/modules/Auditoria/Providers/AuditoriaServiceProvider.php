<?php

declare(strict_types=1);

namespace Modules\Auditoria\Providers;

use Modules\Auditoria\Contracts\AuditoriaLlm;
use Modules\Auditoria\Services\MongoAuditoriaLlm;
use Modules\Shared\Providers\ModuleServiceProvider;

final class AuditoriaServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'auditoria';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AuditoriaLlm::class, MongoAuditoriaLlm::class);
    }
}
