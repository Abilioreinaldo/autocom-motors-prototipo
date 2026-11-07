<?php

namespace App\Console\Commands;

use App\Jobs\SyncWebmotorsInventory;
use App\Services\WebmotorsSyncService;
use Illuminate\Console\Command;

class SyncWebmotorsCommand extends Command
{
    protected $signature = 'autocom:sync-webmotors {--queue : Despacha para a fila em vez de executar agora} {--force : Executa mesmo com WEBMOTORS_ENABLED=false}';

    protected $description = 'Sincroniza o estoque da Webmotors (API Site) com a tabela de veículos';

    public function handle(WebmotorsSyncService $service): int
    {
        if (! config('webmotors.enabled') && ! $this->option('force')) {
            $this->warn('Integração Webmotors desativada (WEBMOTORS_ENABLED=false). Use --force para rodar com o driver atual ('.config('webmotors.driver').').');

            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            SyncWebmotorsInventory::dispatch('command');
            $this->info('Sincronização enfileirada.');

            return self::SUCCESS;
        }

        $log = $service->sync('command');
        $this->{$log->status === 'success' ? 'info' : 'error'}($log->message ?? $log->status);

        return $log->status === 'success' ? self::SUCCESS : self::FAILURE;
    }
}
