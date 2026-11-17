<?php

declare(strict_types=1);

namespace Modules\Documentos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Fornecedores\Models\Fornecedor;
use RiskEngine\Enums\DocumentType;

/**
 * Metadados do documento (MySQL). O texto completo fica no MongoDB (ConteudoDocumentoStore),
 * ligado pelo id e pelo sha256 — o parecer sempre aponta a versão exata usada.
 *
 * @property int $id
 * @property int $fornecedor_id
 * @property DocumentType $tipo
 * @property string $titulo
 * @property \Illuminate\Support\Carbon|null $emitido_em
 * @property string $sha256
 */
class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = ['fornecedor_id', 'tipo', 'titulo', 'emitido_em', 'sha256', 'tamanho_bytes'];

    protected function casts(): array
    {
        return [
            'tipo' => DocumentType::class,
            'emitido_em' => 'date',
        ];
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
