<?php

declare(strict_types=1);

namespace Modules\Auditoria\Models;

use MongoDB\Laravel\Eloquent\Model;

/** Registro imutável de uma chamada ao LLM (coleção auditoria_llm no MongoDB). */
class ChamadaLlm extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'auditoria_llm';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['avaliacao_id' => 'integer'];
    }
}
