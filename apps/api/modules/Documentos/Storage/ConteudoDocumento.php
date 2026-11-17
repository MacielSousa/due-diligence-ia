<?php

declare(strict_types=1);

namespace Modules\Documentos\Storage;

use MongoDB\Laravel\Eloquent\Model;

/** Documento MongoDB com o texto integral (coleção documentos_conteudo). */
class ConteudoDocumento extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'documentos_conteudo';

    protected $fillable = ['documento_id', 'fornecedor_id', 'sha256', 'texto'];

    protected function casts(): array
    {
        return ['documento_id' => 'integer', 'fornecedor_id' => 'integer'];
    }
}
