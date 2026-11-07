<?php

use App\Jobs\SyncWebmotorsInventory;
use Illuminate\Support\Facades\Schedule;

// Sincronização do estoque Webmotors a cada 30 minutos (só executa de fato com WEBMOTORS_ENABLED=true)
Schedule::job(new SyncWebmotorsInventory('schedule'))
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();
