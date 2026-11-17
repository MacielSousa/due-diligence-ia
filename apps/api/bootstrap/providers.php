<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Módulos (modules/*)
    Modules\Fornecedores\Providers\FornecedoresServiceProvider::class,
    Modules\Documentos\Providers\DocumentosServiceProvider::class,
    Modules\Auditoria\Providers\AuditoriaServiceProvider::class,
    Modules\IA\Providers\IAServiceProvider::class,
    Modules\Avaliacao\Providers\AvaliacaoServiceProvider::class,
];
