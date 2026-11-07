<?php

use App\Models\Vehicle;
use App\Models\VehicleExternalRef;
use App\Services\VehicleDuplicateFinder;

it('encontra duplicidade pelo external_id', function () {
    $v = Vehicle::factory()->create();
    VehicleExternalRef::factory()->for($v)->create(['external_id' => '54810001']);

    $r = (new VehicleDuplicateFinder)->find(['marca' => 'Outra', 'modelo' => 'Coisa', 'ano_mod' => 2000, 'km' => 1], '54810001');

    expect($r['vehicle']?->id)->toBe($v->id)->and($r['reason'])->toBe('external_id');
});

it('encontra duplicidade por marca, modelo, ano e km com tolerância de 500', function () {
    $v = Vehicle::factory()->create(['marca' => 'Chevrolet', 'modelo' => 'Onix', 'ano_mod' => 2023, 'km' => 31500]);

    $finder = new VehicleDuplicateFinder;

    expect($finder->bySpecs('chevrolet', 'ONIX', 2023, 31900)?->id)->toBe($v->id)
        ->and($finder->bySpecs('Chevrolet', 'Onix', 2023, 31000)?->id)->toBe($v->id)
        ->and($finder->bySpecs('Chevrolet', 'Onix', 2023, 32100))->toBeNull()
        ->and($finder->bySpecs('Chevrolet', 'Onix', 2022, 31500))->toBeNull();
});

it('não retorna nada quando não há duplicidade', function () {
    Vehicle::factory()->create(['marca' => 'Fiat', 'modelo' => 'Toro', 'ano_mod' => 2021, 'km' => 70000]);

    $r = (new VehicleDuplicateFinder)->find(['marca' => 'Fiat', 'modelo' => 'Strada', 'ano_mod' => 2021, 'km' => 70000], '99999999');

    expect($r['vehicle'])->toBeNull();
});
