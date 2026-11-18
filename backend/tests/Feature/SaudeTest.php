<?php

namespace Tests\Feature;

use Tests\TestCase;

class SaudeTest extends TestCase
{
    public function test_raiz_informa_onde_esta_a_api(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertJsonStructure(['app', 'api', 'saude']);
    }

    public function test_health_check_responde(): void
    {
        $this->get('/up')->assertOk();
    }
}
