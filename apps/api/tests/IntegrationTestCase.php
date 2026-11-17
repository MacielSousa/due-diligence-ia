<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Base dos testes de integração: MySQL real (banco "testing" do Sail, recriado por RefreshDatabase)
 * e MongoDB real (banco "testing", coleções limpas a cada teste).
 */
abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['documentos_conteudo', 'auditoria_llm'] as $colecao) {
            DB::connection('mongodb')->table($colecao)->truncate();
        }
    }
}
