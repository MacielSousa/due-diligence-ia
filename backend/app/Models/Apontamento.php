<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apontamento extends Model
{
    public const TIPOS = ['sancao', 'processo_criminal', 'midia_negativa', 'processo_trabalhista'];

    protected $table = 'apontamentos';

    protected $fillable = ['tipo', 'ano'];

    protected function casts(): array
    {
        return ['ano' => 'integer'];
    }

    public function terceiro(): BelongsTo
    {
        return $this->belongsTo(Terceiro::class);
    }
}
