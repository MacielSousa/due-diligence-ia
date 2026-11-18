<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Terceiro extends Model
{
    protected $table = 'terceiros';

    protected $fillable = ['tipo', 'nome', 'documento', 'pep'];

    protected function casts(): array
    {
        return ['pep' => 'boolean'];
    }

    protected static function booted(): void
    {
        // o banco apaga os anexos em cascata, mas não os ARQUIVOS: apagamos model a model
        static::deleting(fn (Terceiro $terceiro) => $terceiro->anexos->each->delete());
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class);
    }

    public function analises(): HasMany
    {
        return $this->hasMany(AnaliseIa::class);
    }

    /** Sócios desta empresa (só faz sentido para PJ). */
    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class, 'empresa_id');
    }

    /** Empresas das quais este terceiro é sócio. */
    public function participacoes(): HasMany
    {
        return $this->hasMany(Socio::class, 'socio_id');
    }

    public function isPj(): bool
    {
        return $this->tipo === 'pj';
    }
}
