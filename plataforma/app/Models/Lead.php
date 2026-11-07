<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo', 'nome', 'fone', 'email', 'veiculo_id', 'mensagem', 'status', 'meta',
        'assigned_to', 'origem', 'last_contact_at',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => LeadType::class,
            'status' => LeadStatus::class,
            'meta' => 'array',
            'last_contact_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Lead $lead) {
            $lead->events()->create(['tipo' => 'criado', 'para' => $lead->status->value, 'meta' => ['tipo' => $lead->tipo->value]]);
        });

        static::updating(function (Lead $lead) {
            if ($lead->isDirty('status')) {
                $lead->events()->create([
                    'tipo' => 'status',
                    'de' => $lead->getOriginal('status')?->value,
                    'para' => $lead->status->value,
                    'user_id' => auth()->id(),
                ]);
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'veiculo_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class)->latest();
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNotIn('status', [LeadStatus::Ganho->value, LeadStatus::Perdido->value]);
    }

    public function getFoneDigitsAttribute(): string
    {
        return \App\Support\Whatsapp::normalize($this->fone);
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = $this->fone_digits;
        if ($digits === '') {
            return null;
        }
        $texto = 'Olá '.explode(' ', trim($this->nome))[0].', aqui é da Autocom Motors.';
        if ($this->vehicle) {
            $texto .= ' Sobre o '.$this->vehicle->nome_whatsapp.':';
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($texto);
    }

    /** Registra um contato feito pela equipe */
    public function registerContact(string $nota, ?int $userId = null, string $tipo = 'contato'): LeadEvent
    {
        $this->forceFill(['last_contact_at' => now()])->saveQuietly();

        return $this->events()->create(['tipo' => $tipo, 'nota' => $nota, 'user_id' => $userId ?? auth()->id()]);
    }
}
