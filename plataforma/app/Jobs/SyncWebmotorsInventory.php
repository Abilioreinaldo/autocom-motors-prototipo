<?php

namespace App\Jobs;

use App\Models\SyncLog;
use App\Services\WebmotorsSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncWebmotorsInventory implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public string $trigger = 'schedule') {}

    public function handle(WebmotorsSyncService $service): ?SyncLog
    {
        if (! config('webmotors.enabled')) {
            return SyncLog::create([
                'provider' => 'webmotors', 'status' => 'skipped', 'trigger' => $this->trigger,
                'message' => 'Integração desativada (WEBMOTORS_ENABLED=false).', 'started_at' => now(), 'finished_at' => now(),
            ]);
        }

        return $service->sync($this->trigger);
    }
}
