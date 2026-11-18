<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Socio extends Model
{
    protected $table = 'socios';

    protected $fillable = ['socio_id', 'participacao'];

    protected function casts(): array
    {
        return ['participacao' => 'float'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Terceiro::class, 'empresa_id');
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Terceiro::class, 'socio_id');
    }
}
