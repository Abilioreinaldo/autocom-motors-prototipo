<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleExternalRef extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'provider', 'external_id', 'source_url', 'payload_hash', 'payload', 'locked_fields', 'synced_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'locked_fields' => 'array', 'synced_at' => 'datetime'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isLocked(string $field): bool
    {
        return in_array($field, $this->locked_fields ?? [], true);
    }

    public function lock(array $fields): static
    {
        $this->locked_fields = array_values(array_unique(array_merge($this->locked_fields ?? [], $fields)));

        return $this;
    }
}
