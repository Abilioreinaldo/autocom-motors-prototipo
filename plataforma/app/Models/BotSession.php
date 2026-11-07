<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BotSession extends Model
{
    protected $fillable = ['uuid', 'visitor_id', 'messages', 'context', 'nome', 'fone', 'lead_id', 'mensagens', 'last_message_at'];

    protected function casts(): array
    {
        return ['messages' => 'array', 'context' => 'array', 'last_message_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (BotSession $s) {
            $s->uuid ??= (string) Str::uuid();
            $s->messages ??= [];
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function pushMessage(string $role, string $text, array $extra = []): static
    {
        $messages = $this->messages ?? [];
        $messages[] = array_merge(['role' => $role, 'text' => $text, 'at' => now()->toIso8601String()], $extra);
        $this->messages = $messages;
        $this->mensagens = count($messages);
        $this->last_message_at = now();

        return $this;
    }

    public function getTranscriptAttribute(): string
    {
        return collect($this->messages ?? [])
            ->map(fn ($m) => ($m['role'] === 'user' ? 'Cliente' : 'Bot').': '.$m['text'])
            ->implode("\n");
    }
}
