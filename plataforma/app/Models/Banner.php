<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'subtitulo', 'imagem', 'link', 'cta', 'posicao', 'ordem', 'ativo', 'inicio_em', 'fim_em'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'ordem' => 'integer', 'inicio_em' => 'datetime', 'fim_em' => 'datetime'];
    }

    public function scopeActive(Builder $q, ?string $posicao = null): Builder
    {
        return $q->where('ativo', true)
            ->when($posicao, fn ($w) => $w->where('posicao', $posicao))
            ->where(fn ($w) => $w->whereNull('inicio_em')->orWhere('inicio_em', '<=', now()))
            ->where(fn ($w) => $w->whereNull('fim_em')->orWhere('fim_em', '>=', now()))
            ->orderBy('ordem');
    }

    public function getImagemUrlAttribute(): ?string
    {
        if (! $this->imagem) {
            return null;
        }

        return Str::startsWith($this->imagem, ['http://', 'https://', '/']) ? $this->imagem : Storage::disk('public')->url($this->imagem);
    }
}
