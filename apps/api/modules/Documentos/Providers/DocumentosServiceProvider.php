<?php

declare(strict_types=1);

namespace Modules\Documentos\Providers;

use Modules\Documentos\Contracts\ConteudoDocumentoStore;
use Modules\Documentos\Storage\MongoConteudoDocumentoStore;
use Modules\Shared\Providers\ModuleServiceProvider;

final class DocumentosServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'documentos';
    }

    public function register(): void
    {
        parent::register();

        $this->app->bind(ConteudoDocumentoStore::class, MongoConteudoDocumentoStore::class);
    }
}
