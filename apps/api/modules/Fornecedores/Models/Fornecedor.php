<?php

declare(strict_types=1);

namespace Modules\Fornecedores\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Fornecedores\Database\Factories\FornecedorFactory;

/**
 * @property int $id
 * @property string $razao_social
 * @property string $cnpj
 */
class Fornecedor extends Model
{
    /** @use HasFactory<FornecedorFactory> */
    use HasFactory;

    protected $table = 'fornecedores';

    protected $fillable = ['razao_social', 'cnpj', 'segmento', 'valor_contrato_estimado', 'observacoes'];

    protected function casts(): array
    {
        return ['valor_contrato_estimado' => 'decimal:2'];
    }

    protected static function newFactory(): FornecedorFactory
    {
        return FornecedorFactory::new();
    }

    public function cnpjFormatado(): string
    {
        return vsprintf('%s.%s.%s/%s-%s', sscanf($this->cnpj, '%2s%3s%3s%4s%2s'));
    }
}
