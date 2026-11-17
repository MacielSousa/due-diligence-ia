<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Providers;

use Modules\Shared\Providers\ModuleServiceProvider;

final class FornecedoresServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'fornecedores';
    }
}
