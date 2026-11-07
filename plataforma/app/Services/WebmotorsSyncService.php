<?php

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Integrations\Webmotors\VehicleSourceInterface;
use App\Integrations\Webmotors\WebmotorsVehicle;
use App\Models\SyncLog;
use App\Models\Vehicle;
use App\Models\VehicleExternalRef;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Faz o upsert do estoque da Webmotors em vehicles/vehicle_external_refs.
 *
 * Regras:
 *  - chave de upsert: (provider, external_id)
 *  - campos em locked_fields NUNCA são sobrescritos pelo sync
 *  - payload_hash evita gravações quando nada mudou
 *  - veículos ausentes do feed recebem o status configurado (padrão: vendido)
 */
class WebmotorsSyncService
{
    public const PROVIDER = 'webmotors';

    public function __construct(
        protected VehicleSourceInterface $source,
        protected VehicleDuplicateFinder $duplicates,
    ) {}

    public function sync(string $trigger = 'command'): SyncLog
    {
        $log = SyncLog::create(['provider' => self::PROVIDER, 'status' => 'running', 'trigger' => $trigger, 'started_at' => now()]);

        try {
            $items = $this->source->fetchInventory();
            $seen = [];
            $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

            foreach ($items as $item) {
                $seen[] = $item->externalId;
                $result = $this->upsert($item);
                $counts[$result]++;
            }

            $missing = $this->markMissing($seen);

            $log->update([
                'status' => 'success',
                'fetched' => count($items),
                'created' => $counts['created'],
                'updated' => $counts['updated'],
                'skipped' => $counts['skipped'],
                'missing' => $missing,
                'finished_at' => now(),
                'message' => sprintf('%d anúncios · %d novos · %d atualizados · %d sem alteração · %d ausentes', count($items), $counts['created'], $counts['updated'], $counts['skipped'], $missing),
                'details' => ['driver' => $this->source->name(), 'ids' => $seen],
            ]);
        } catch (Throwable $e) {
            $log->update(['status' => 'error', 'message' => $e->getMessage(), 'finished_at' => now()]);
            report($e);
        }

        return $log;
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    public function upsert(WebmotorsVehicle $item): string
    {
        return DB::transaction(function () use ($item) {
            $ref = VehicleExternalRef::query()
                ->where('provider', self::PROVIDER)
                ->where('external_id', $item->externalId)
                ->with('vehicle')
                ->first();

            $hash = $item->hash();

            if ($ref && $ref->vehicle) {
                if ($ref->payload_hash === $hash) {
                    $ref->forceFill(['synced_at' => now()])->save();

                    return 'skipped';
                }

                $this->applyAttributes($ref->vehicle, $item, $ref->locked_fields ?? []);
                $this->syncPhotos($ref->vehicle, $item, $ref->locked_fields ?? []);
                $ref->forceFill(['payload_hash' => $hash, 'payload' => $item->toArray(), 'source_url' => $item->sourceUrl, 'synced_at' => now()])->save();

                return 'updated';
            }

            // Sem ref: tenta casar com veículo cadastrado manualmente (marca+modelo+ano+km)
            $existing = $this->duplicates->bySpecs($item->marca, $item->modelo, $item->anoMod, $item->km);

            if ($existing) {
                $existing->externalRefs()->updateOrCreate(
                    ['provider' => self::PROVIDER],
                    ['external_id' => $item->externalId, 'source_url' => $item->sourceUrl, 'payload_hash' => $hash, 'payload' => $item->toArray(), 'synced_at' => now(), 'locked_fields' => ['preco', 'descricao', 'destaque']]
                );
                $this->applyAttributes($existing, $item, ['preco', 'descricao', 'destaque']);
                if ($existing->photos()->count() === 0) {
                    $this->syncPhotos($existing, $item, []);
                }

                return 'updated';
            }

            $vehicle = new Vehicle(['status' => VehicleStatus::Disponivel, 'published_at' => now()]);
            $this->applyAttributes($vehicle, $item, []);
            $this->syncPhotos($vehicle, $item, []);
            $vehicle->externalRefs()->create([
                'provider' => self::PROVIDER,
                'external_id' => $item->externalId,
                'source_url' => $item->sourceUrl,
                'payload_hash' => $hash,
                'payload' => $item->toArray(),
                'synced_at' => now(),
                'locked_fields' => [],
            ]);

            return 'created';
        });
    }

    protected function applyAttributes(Vehicle $vehicle, WebmotorsVehicle $item, array $locked): void
    {
        $attrs = $item->toVehicleAttributes();
        foreach ($locked as $field) {
            unset($attrs[$field]);
        }
        // nunca altera status/destaque/slug a partir do feed
        unset($attrs['status'], $attrs['destaque'], $attrs['slug']);
        $vehicle->fill($attrs);
        $vehicle->save();
    }

    protected function syncPhotos(Vehicle $vehicle, WebmotorsVehicle $item, array $locked): void
    {
        if (in_array('fotos', $locked, true) || $item->fotos === []) {
            return;
        }
        $atuais = $vehicle->photos()->pluck('path')->all();
        if ($atuais === $item->fotos) {
            return;
        }
        // substitui apenas fotos vindas do feed (URLs absolutas); fotos enviadas no painel (path relativo) ficam
        $vehicle->photos()->where('path', 'like', 'http%')->delete();
        $ordem = (int) $vehicle->photos()->max('ordem') + 1;
        $temCapa = $vehicle->photos()->where('capa', true)->exists();
        foreach ($item->fotos as $i => $url) {
            $vehicle->photos()->create(['path' => $url, 'ordem' => $ordem + $i, 'capa' => ! $temCapa && $i === 0, 'alt' => $vehicle->titulo]);
        }
    }

    protected function markMissing(array $seenIds): int
    {
        $status = config('webmotors.sync.missing_status', 'vendido');
        if (! $status) {
            return 0;
        }

        $refs = VehicleExternalRef::query()
            ->where('provider', self::PROVIDER)
            ->whereNotIn('external_id', $seenIds)
            ->with('vehicle')
            ->get();

        $n = 0;
        foreach ($refs as $ref) {
            $v = $ref->vehicle;
            if ($v && $v->status === VehicleStatus::Disponivel && ! $ref->isLocked('status')) {
                $v->forceFill(['status' => $status])->save();
                $n++;
            }
        }

        return $n;
    }
}
