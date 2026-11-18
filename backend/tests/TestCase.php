<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // O cliente de teste manda "Accept-Language: en-us" por padrão (Symfony);
        // aqui simulamos o frontend, que manda o idioma escolhido (pt-BR).
        $this->withHeader('Accept-Language', 'pt-BR');
    }
}
