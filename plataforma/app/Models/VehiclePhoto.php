<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehiclePhoto extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'path', 'disk', 'ordem', 'capa', 'alt'];

    protected function casts(): array
    {
        return ['capa' => 'boolean', 'ordem' => 'integer'];
    }

    protected static function booted(): void
    {
        // Garante uma única capa por veículo
        static::saved(function (VehiclePhoto $photo) {
            if ($photo->capa) {
                static::where('vehicle_id', $photo->vehicle_id)->whereKeyNot($photo->id)->update(['capa' => false]);
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getUrlAttribute(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://', 'data:', '/'])) {
            return $this->path;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }
}
