<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Documento anexado a um terceiro (contrato social, certidão...). */
class Anexo extends Model
{
    public const CATEGORIAS = ['contrato_social', 'certidao_negativa', 'documento_identidade', 'comprovante_endereco', 'outro'];

    public const DISCO = 'local';

    protected $table = 'anexos';

    protected $fillable = ['categoria', 'nome_original', 'caminho', 'mime', 'tamanho'];

    protected function casts(): array
    {
        return ['tamanho' => 'integer'];
    }

    protected static function booted(): void
    {
        // registro apagado -> arquivo apagado do disco
        static::deleted(fn (Anexo $anexo) => Storage::disk(self::DISCO)->delete($anexo->caminho));
    }

    public function terceiro(): BelongsTo
    {
        return $this->belongsTo(Terceiro::class);
    }
}
