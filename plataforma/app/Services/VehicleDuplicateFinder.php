<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleExternalRef;
use Illuminate\Support\Str;

/**
 * Detecta duplicidade de veículos:
 *  1) pelo external_id do provedor (vehicle_external_refs)
 *  2) por marca + modelo + ano modelo + km (± tolerância, padrão 500)
 */
class VehicleDuplicateFinder
{
    public function __construct(protected ?int $kmTolerance = null)
    {
        $this->kmTolerance ??= (int) config('webmotors.sync.km_tolerance', 500);
    }

    public function byExternalId(string $externalId, string $provider = 'webmotors'): ?Vehicle
    {
        return VehicleExternalRef::query()
            ->where('provider', $provider)
            ->where('external_id', $externalId)
            ->with('vehicle')
            ->first()?->vehicle;
    }

    public function bySpecs(string $marca, string $modelo, int $anoMod, int $km, ?int $ignoreId = null): ?Vehicle
    {
        return Vehicle::query()
            ->whereRaw('LOWER(marca) = ?', [Str::lower(trim($marca))])
            ->whereRaw('LOWER(modelo) = ?', [Str::lower(trim($modelo))])
            ->where('ano_mod', $anoMod)
            ->whereBetween('km', [max(0, $km - $this->kmTolerance), $km + $this->kmTolerance])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{vehicle: Vehicle|null, reason: string|null}
     */
    public function find(array $attrs, ?string $externalId = null, string $provider = 'webmotors'): array
    {
        if ($externalId && ($v = $this->byExternalId($externalId, $provider))) {
            return ['vehicle' => $v, 'reason' => 'external_id'];
        }

        if (! empty($attrs['marca']) && ! empty($attrs['modelo']) && ! empty($attrs['ano_mod'])) {
            $v = $this->bySpecs($attrs['marca'], $attrs['modelo'], (int) $attrs['ano_mod'], (int) ($attrs['km'] ?? 0), $attrs['ignore_id'] ?? null);
            if ($v) {
                return ['vehicle' => $v, 'reason' => 'specs'];
            }
        }

        return ['vehicle' => null, 'reason' => null];
    }
}
