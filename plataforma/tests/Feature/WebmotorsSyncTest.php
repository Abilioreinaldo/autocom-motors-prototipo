<?php

use App\Enums\VehicleStatus;
use App\Integrations\Webmotors\FakeWebmotorsClient;
use App\Integrations\Webmotors\VehicleSourceInterface;
use App\Jobs\SyncWebmotorsInventory;
use App\Models\SyncLog;
use App\Models\Vehicle;
use App\Models\VehicleExternalRef;
use App\Services\WebmotorsSyncService;

beforeEach(function () {
    config(['webmotors.enabled' => true, 'webmotors.driver' => 'fake']);
});

it('resolve o driver fake a partir da config', function () {
    expect(app(VehicleSourceInterface::class))->toBeInstanceOf(FakeWebmotorsClient::class);
});

it('o cliente fake expõe as fixtures com os ids esperados', function () {
    $client = new FakeWebmotorsClient;

    expect(collect($client->fetchInventory())->pluck('externalId')->all())->toBe(['54810001', '54810003', '54810021', '54810022'])
        ->and($client->fetchById('54810021')?->modelo)->toBe('Hilux')
        ->and($client->fetchById('00000000'))->toBeNull()
        ->and($client->pushLead(['nome' => 'Teste'])['ok'])->toBeTrue();
});

it('cria veículos, fotos e refs a partir do estoque fake', function () {
    $log = app(WebmotorsSyncService::class)->sync('test');

    expect($log->status)->toBe('success')
        ->and($log->fetched)->toBe(4)
        ->and($log->created)->toBe(4)
        ->and(Vehicle::count())->toBe(4)
        ->and(VehicleExternalRef::where('provider', 'webmotors')->count())->toBe(4);

    $hilux = VehicleExternalRef::where('external_id', '54810021')->first()->vehicle;
    expect($hilux->marca)->toBe('Toyota')
        ->and($hilux->cambio->value)->toBe('automatico')
        ->and($hilux->combustivel->value)->toBe('diesel')
        ->and($hilux->carroceria->value)->toBe('picape')
        ->and($hilux->photos()->count())->toBe(4)
        ->and($hilux->photos()->where('capa', true)->count())->toBe(1)
        ->and($hilux->slug)->not->toBeEmpty();
});

it('é idempotente: segunda execução não altera nada', function () {
    $service = app(WebmotorsSyncService::class);
    $service->sync('test');
    $log = $service->sync('test');

    expect($log->created)->toBe(0)->and($log->updated)->toBe(0)->and($log->skipped)->toBe(4)->and(Vehicle::count())->toBe(4);
});

it('atualiza por external_id respeitando locked_fields', function () {
    $service = app(WebmotorsSyncService::class);
    $service->sync('test');

    $ref = VehicleExternalRef::where('external_id', '54810001')->first();
    $ref->vehicle->update(['preco' => 99999, 'descricao' => 'Texto editado no painel']);
    $ref->lock(['preco', 'descricao'])->save();

    // altera o feed: preço e km diferentes
    $fixtures = json_decode(file_get_contents(app_path('Integrations/Webmotors/Fixtures/inventory.json')), true);
    $fixtures[0]['preco'] = 111111;
    $fixtures[0]['km'] = 30000;
    $fixtures[0]['descricao'] = 'Descrição nova do feed';
    app()->instance(VehicleSourceInterface::class, new FakeWebmotorsClient($fixtures));

    $log = app(WebmotorsSyncService::class)->sync('test');
    $v = $ref->vehicle->fresh();

    expect($log->updated)->toBe(1)
        ->and((float) $v->preco)->toBe(99999.0)            // travado
        ->and($v->descricao)->toBe('Texto editado no painel') // travado
        ->and($v->km)->toBe(30000);                          // atualizado
});

it('marca como vendido veículos que sumiram do feed', function () {
    $service = app(WebmotorsSyncService::class);
    $service->sync('test');

    $fixtures = json_decode(file_get_contents(app_path('Integrations/Webmotors/Fixtures/inventory.json')), true);
    array_pop($fixtures); // remove 54810022
    app()->instance(VehicleSourceInterface::class, new FakeWebmotorsClient($fixtures));

    $log = app(WebmotorsSyncService::class)->sync('test');
    $civic = VehicleExternalRef::where('external_id', '54810022')->first()->vehicle->fresh();

    expect($log->missing)->toBe(1)->and($civic->status)->toBe(VehicleStatus::Vendido);
});

it('casa com veículo cadastrado manualmente por marca+modelo+ano+km em vez de duplicar', function () {
    $manual = Vehicle::factory()->create(['marca' => 'Honda', 'modelo' => 'Civic', 'ano_mod' => 2020, 'km' => 66000, 'preco' => 115000]);

    app(WebmotorsSyncService::class)->sync('test');

    expect(Vehicle::where('modelo', 'Civic')->count())->toBe(1)
        ->and($manual->fresh()->externalRefs()->where('external_id', '54810022')->exists())->toBeTrue()
        ->and((float) $manual->fresh()->preco)->toBe(115000.0); // preço manual preservado
});

it('o job ignora a sincronização quando a integração está desativada', function () {
    config(['webmotors.enabled' => false]);

    $log = (new SyncWebmotorsInventory('test'))->handle(app(WebmotorsSyncService::class));

    expect($log)->toBeInstanceOf(SyncLog::class)->and($log->status)->toBe('skipped')->and(Vehicle::count())->toBe(0);
});

it('o comando artisan executa a sincronização', function () {
    $this->artisan('autocom:sync-webmotors')->assertSuccessful();

    expect(Vehicle::count())->toBe(4);
});
