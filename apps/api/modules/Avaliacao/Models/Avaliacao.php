<?php

declare(strict_types=1);

namespace Modules\Avaliacao\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Avaliacao\Enums\Decisao;
use Modules\Avaliacao\Enums\StatusAvaliacao;
use Modules\Fornecedores\Models\Fornecedor;
use RiskEngine\Enums\RiskLevel;

/**
 * @property int $id
 * @property int $fornecedor_id
 * @property StatusAvaliacao $status
 * @property RiskLevel|null $nivel
 * @property array<string, mixed>|null $resultado
 */
class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pendente'];

    protected function casts(): array
    {
        return [
            'status' => StatusAvaliacao::class,
            'nivel' => RiskLevel::class,
            'decisao' => Decisao::class,
            'score' => 'float',
            'confianca' => 'float',
            'requer_revisao' => 'boolean',
            'resultado' => 'array',
            'documentos_usados' => 'array',
            'iniciada_em' => 'datetime',
            'concluida_em' => 'datetime',
            'decidido_em' => 'datetime',
        ];
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
