<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadEvent extends Model
{
    protected $fillable = ['lead_id', 'user_id', 'tipo', 'de', 'para', 'nota', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDescricaoAttribute(): string
    {
        return match ($this->tipo) {
            'criado' => 'Lead criado',
            'status' => 'Status: '.($this->de ?: '—').' → '.$this->para,
            'contato' => 'Contato registrado',
            'whatsapp' => 'Mensagem no WhatsApp',
            'nota' => 'Anotação',
            'atribuido' => 'Atribuído a '.($this->para ?: '—'),
            default => ucfirst($this->tipo),
        };
    }
}
