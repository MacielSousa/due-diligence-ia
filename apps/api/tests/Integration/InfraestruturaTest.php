<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\Avaliacao\Jobs\ProcessarAvaliacaoJob;
use PHPUnit\Framework\Attributes\Test;
use Tests\IntegrationTestCase;

/** Garante que os serviços dos containers (MySQL, Redis, MongoDB) estão acessíveis e configurados. */
class InfraestruturaTest extends IntegrationTestCase
{
    #[Test]
    public function mysql_responde(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertEquals(1, DB::selectOne('select 1 as ok')->ok);
    }

    #[Test]
    public function mongodb_responde(): void
    {
        DB::connection('mongodb')->table('auditoria_llm')->insert(['ping' => true]);

        $this->assertSame(1, DB::connection('mongodb')->table('auditoria_llm')->where('ping', true)->count());
    }

    #[Test]
    public function redis_funciona_como_cache_e_fila(): void
    {
        Cache::store('redis')->put('teste:ping', 'pong', 10);
        $this->assertSame('pong', Cache::store('redis')->get('teste:ping'));

        $fila = 'testes-' . uniqid();
        Queue::connection('redis')->pushOn($fila, new ProcessarAvaliacaoJob(999));

        $this->assertSame(1, Queue::connection('redis')->size($fila));
        Redis::connection()->del('queues:' . $fila);
    }
}
