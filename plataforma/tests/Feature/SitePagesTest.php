<?php

use App\Models\Vehicle;
use App\Models\VehiclePhoto;

it('renderiza a home com destaques', function () {
    $v = Vehicle::factory()->featured()->create(['marca' => 'Toyota', 'modelo' => 'Corolla']);
    VehiclePhoto::factory()->for($v)->create(['capa' => true]);

    $this->get('/')->assertOk()->assertSee('Toyota')->assertSee('Corolla')->assertSee('LocarX');
});

it('renderiza o estoque com filtros via query string', function () {
    Vehicle::factory()->create(['marca' => 'Fiat', 'modelo' => 'Toro', 'cambio' => 'automatico']);
    Vehicle::factory()->create(['marca' => 'Renault', 'modelo' => 'Kwid', 'cambio' => 'manual']);

    $this->get('/estoque?marca=Fiat')->assertOk()->assertSee('Toro')->assertDontSee('Kwid');
    $this->get('/estoque?cambio[]=manual')->assertOk()->assertSee('Kwid')->assertDontSee('Toro');
});

it('renderiza a página do veículo com simulador e formulários', function () {
    $v = Vehicle::factory()->create(['marca' => 'Honda', 'modelo' => 'HR-V', 'preco' => 150000]);

    $this->get($v->url)->assertOk()
        ->assertSee('Honda')
        ->assertSee('Simule sua parcela')
        ->assertSee('Fazer uma proposta')
        ->assertSee('Simulação ilustrativa');
});

it('não exibe veículo vendido para visitantes', function () {
    $v = Vehicle::factory()->sold()->create();

    $this->get($v->url)->assertNotFound();
});

it('redireciona para o wa.me com a mensagem do veículo e registra lead', function () {
    $v = Vehicle::factory()->create(['marca' => 'Jeep', 'modelo' => 'Compass', 'ano_mod' => 2022]);

    $r = $this->get(route('whatsapp', $v));

    $r->assertRedirect();
    expect($r->headers->get('Location'))->toContain('wa.me/5515999990000')->toContain(rawurlencode('Vi o Jeep Compass 2022'));
    $this->assertDatabaseHas('leads', ['tipo' => 'whatsapp', 'veiculo_id' => $v->id]);
});
