<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Uma execução da análise de documentos por IA. */
class AnaliseIa extends Model
{
    public const PENDENTE = 'pendente';
    public const PROCESSANDO = 'processando';
    public const CONCLUIDA = 'concluida';
    public const FALHOU = 'falhou';

    protected $table = 'analises_ia';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'iniciada_em' => 'datetime',
            'concluida_em' => 'datetime',
            'eliminatorio' => 'boolean',
            'explicacao' => 'array',
            'score' => 'float',
        ];
    }

    public function terceiro(): BelongsTo
    {
        return $this->belongsTo(Terceiro::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(AnaliseIaDocumento::class)->orderBy('id');
    }

    public function emAndamento(): bool
    {
        return in_array($this->status, [self::PENDENTE, self::PROCESSANDO], true);
    }
}
