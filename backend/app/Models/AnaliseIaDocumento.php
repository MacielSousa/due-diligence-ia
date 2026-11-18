<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** O que a IA leu e respondeu sobre um documento dentro de uma análise. */
class AnaliseIaDocumento extends Model
{
    protected $table = 'analise_ia_documentos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['achados' => 'array'];
    }

    public function analise(): BelongsTo
    {
        return $this->belongsTo(AnaliseIa::class, 'analise_ia_id');
    }

    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class);
    }
}
