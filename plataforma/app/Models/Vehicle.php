<?php

namespace App\Models;

use App\Enums\Cambio;
use App\Enums\Carroceria;
use App\Enums\Combustivel;
use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'marca', 'modelo', 'versao', 'ano_fab', 'ano_mod', 'km', 'preco', 'preco_anterior',
        'cambio', 'combustivel', 'carroceria', 'cor', 'motor', 'portas', 'placa_final',
        'opcionais', 'descricao', 'destaque', 'status', 'slug', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'ano_fab' => 'integer',
            'ano_mod' => 'integer',
            'km' => 'integer',
            'portas' => 'integer',
            'preco' => 'decimal:2',
            'preco_anterior' => 'decimal:2',
            'opcionais' => 'array',
            'destaque' => 'boolean',
            'status' => VehicleStatus::class,
            'cambio' => Cambio::class,
            'combustivel' => Combustivel::class,
            'carroceria' => Carroceria::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Vehicle $vehicle) {
            if (blank($vehicle->slug)) {
                $vehicle->slug = static::uniqueSlug($vehicle);
            }
        });
    }

    public static function uniqueSlug(Vehicle $vehicle): string
    {
        $base = Str::slug(trim("{$vehicle->marca} {$vehicle->modelo} {$vehicle->versao} {$vehicle->ano_mod}"));
        $slug = $base;
        $i = 2;
        while (static::withTrashed()->where('slug', $slug)->when($vehicle->exists, fn ($q) => $q->whereKeyNot($vehicle->getKey()))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    // ---- Relações -------------------------------------------------------

    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class)->orderBy('ordem');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(VehiclePhoto::class)->ofMany(['capa' => 'max', 'ordem' => 'min']);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'veiculo_id');
    }

    public function externalRefs(): HasMany
    {
        return $this->hasMany(VehicleExternalRef::class);
    }

    public function webmotorsRef(): HasOne
    {
        return $this->hasOne(VehicleExternalRef::class)->where('provider', 'webmotors');
    }

    // ---- Scopes ---------------------------------------------------------

    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where('status', VehicleStatus::Disponivel->value);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->whereIn('status', [VehicleStatus::Disponivel->value, VehicleStatus::Reservado->value]);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('destaque', true);
    }

    public function scopeOnOffer(Builder $q): Builder
    {
        return $q->whereNotNull('preco_anterior')->whereColumn('preco_anterior', '>', 'preco');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            foreach (preg_split('/\s+/', $term) as $word) {
                $w->where(function (Builder $x) use ($word) {
                    $like = '%'.$word.'%';
                    $x->where('marca', 'like', $like)
                        ->orWhere('modelo', 'like', $like)
                        ->orWhere('versao', 'like', $like)
                        ->orWhere('cor', 'like', $like)
                        ->orWhere('ano_mod', 'like', $like);
                });
            }
        });
    }

    // ---- Helpers --------------------------------------------------------

    public function getTituloAttribute(): string
    {
        return trim("{$this->marca} {$this->modelo}");
    }

    public function getTituloCompletoAttribute(): string
    {
        return trim("{$this->marca} {$this->modelo} {$this->versao} {$this->ano_fab}/{$this->ano_mod}");
    }

    /** "Marca Modelo Ano" usado na mensagem de WhatsApp */
    public function getNomeWhatsappAttribute(): string
    {
        return trim("{$this->marca} {$this->modelo} {$this->ano_mod}");
    }

    public function getPrecoFormatadoAttribute(): string
    {
        return 'R$ '.number_format((float) $this->preco, 0, ',', '.');
    }

    public function getKmFormatadoAttribute(): string
    {
        return number_format((int) $this->km, 0, ',', '.').' km';
    }

    public function getIsOfertaAttribute(): bool
    {
        return $this->preco_anterior !== null && (float) $this->preco_anterior > (float) $this->preco;
    }

    public function getCoverUrlAttribute(): ?string
    {
        $photo = $this->relationLoaded('cover') ? $this->cover : $this->cover()->first();

        return $photo?->url;
    }

    public function whatsappUrl(?string $mensagem = null): string
    {
        return \App\Support\Whatsapp::linkForVehicle($this, $mensagem);
    }

    public function getUrlAttribute(): string
    {
        return route('veiculo.show', $this->slug);
    }
}
