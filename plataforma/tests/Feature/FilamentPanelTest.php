<?php

use App\Filament\Pages\Configuracoes;
use App\Filament\Pages\ImportarPorLink;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\VehicleResource;
use App\Models\Lead;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExternalRef;
use App\Support\Settings;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->actingAs($this->admin);
    config(['webmotors.driver' => 'fake']);
});

it('exige login no painel', function () {
    auth()->logout();
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renderiza dashboard e páginas principais', function () {
    Vehicle::factory()->count(3)->create();
    Lead::factory()->count(2)->create();

    $this->get('/admin')->assertOk();
    Livewire::test(\App\Filament\Widgets\StatsOverview::class)->assertSee('Veículos disponíveis')->assertSee('3');
    Livewire::test(\App\Filament\Widgets\LeadsPorDiaChart::class)->assertOk();
    Livewire::test(\App\Filament\Widgets\FunilLeadsChart::class)->assertOk();
    Livewire::test(\App\Filament\Widgets\UltimosLeads::class)->assertCanSeeTableRecords(Lead::all());
    $this->get(VehicleResource::getUrl('index'))->assertOk();
    $this->get(LeadResource::getUrl('index'))->assertOk();
    $this->get(ImportarPorLink::getUrl())->assertOk()->assertSee('Cole o link');
    $this->get(Configuracoes::getUrl())->assertOk();
});

it('usuário inativo não acessa o painel', function () {
    $this->actingAs(User::factory()->create(['is_active' => false]));
    $this->get('/admin')->assertForbidden();
});

it('vendedor não vê usuários', function () {
    $this->actingAs(User::factory()->create(['role' => 'vendedor']));
    $this->get('/admin/users')->assertForbidden();
});

it('importa por link: encontra na fonte, preenche e cria veículo com ref e fotos', function () {
    Livewire::test(ImportarPorLink::class)
        ->set('urlData.url', 'https://www.webmotors.com.br/comprar/toyota/hilux/28-srx-4x4-diesel/4-portas/2020-2020/54810021')
        ->call('buscar')
        ->assertSet('stage', 'review')
        ->assertSet('externalId', '54810021')
        ->assertSet('data.marca', 'Toyota')
        ->assertSet('data.modelo', 'Hilux')
        ->set('data.preco', 219900)
        ->call('importar')
        ->assertSet('stage', 'done');

    $ref = VehicleExternalRef::where('external_id', '54810021')->first();
    expect($ref)->not->toBeNull()
        ->and((float) $ref->vehicle->preco)->toBe(219900.0)
        ->and($ref->locked_fields)->toContain('preco')
        ->and($ref->vehicle->photos()->count())->toBe(4)
        ->and($ref->source_url)->toContain('54810021');
});

it('importa por link: bloqueia duplicidade por external_id', function () {
    $v = Vehicle::factory()->create();
    VehicleExternalRef::factory()->for($v)->create(['external_id' => '54810001']);

    Livewire::test(ImportarPorLink::class)
        ->set('urlData.url', '54810001')
        ->call('buscar')
        ->assertSet('stage', 'url')
        ->assertSet('duplicate.reason', 'external_id')
        ->assertSet('duplicate.id', $v->id);
});

it('importa por link: detecta duplicidade por specs e permite vincular', function () {
    $manual = Vehicle::factory()->create(['marca' => 'Honda', 'modelo' => 'Civic', 'ano_mod' => 2020, 'km' => 66000]);

    Livewire::test(ImportarPorLink::class)
        ->set('urlData.url', 'https://www.webmotors.com.br/comprar/honda/civic/a/b/54810022')
        ->call('buscar')
        ->assertSet('stage', 'review')
        ->assertSet('duplicate.reason', 'specs')
        ->call('vincular')
        ->assertSet('stage', 'done')
        ->assertSet('createdId', $manual->id);

    expect(Vehicle::count())->toBe(1)
        ->and($manual->externalRefs()->where('external_id', '54810022')->exists())->toBeTrue();
});

it('importa por link: não encontrado vai para cadastro assistido com source_url', function () {
    Livewire::test(ImportarPorLink::class)
        ->set('urlData.url', 'https://www.webmotors.com.br/comprar/fiat/argo/x/y/99999999')
        ->call('buscar')
        ->assertSet('stage', 'manual')
        ->set('data.marca', 'Fiat')->set('data.modelo', 'Argo')->set('data.ano_fab', 2019)->set('data.ano_mod', 2020)->set('data.km', 40000)->set('data.preco', 60000)
        ->call('importar')
        ->assertSet('stage', 'done');

    $ref = VehicleExternalRef::where('external_id', '99999999')->first();
    expect($ref->source_url)->toBe('https://www.webmotors.com.br/comprar/fiat/argo/x/y/99999999')
        ->and($ref->vehicle->modelo)->toBe('Argo')
        ->and($ref->synced_at)->toBeNull();
});

it('importa por link: rejeita link que não é da Webmotors', function () {
    Livewire::test(ImportarPorLink::class)
        ->set('urlData.url', 'https://www.olx.com.br/anuncio/123456')
        ->call('buscar')
        ->assertSet('stage', 'url')
        ->assertSet('externalId', null)
        ->assertNotified();
});

it('salva configurações e reflete no simulador e no WhatsApp', function () {
    Livewire::test(Configuracoes::class)
        ->set('data.financiamento__taxa_mensal', '1.99')
        ->set('data.whatsapp__numero', '5515988887777')
        ->set('data.bot__ativo', false)
        ->call('salvar')
        ->assertNotified();

    expect(Settings::float('financiamento.taxa_mensal', 0))->toBe(1.99)
        ->and(\App\Support\Whatsapp::number())->toBe('5515988887777')
        ->and(Settings::bool('bot.ativo', true))->toBeFalse();
});

it('editar veículo sincronizado trava os campos alterados', function () {
    $v = Vehicle::factory()->create(['preco' => 100000, 'km' => 50000]);
    $ref = VehicleExternalRef::factory()->for($v)->create(['external_id' => '54810003', 'locked_fields' => []]);

    Livewire::test(VehicleResource\Pages\EditVehicle::class, ['record' => $v->id])
        ->fillForm(['preco' => 95000, 'descricao' => 'Editado no painel'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ref->fresh()->locked_fields)->toContain('preco')->toContain('descricao')->not->toContain('km');
});

it('registra contato no lead pelo painel', function () {
    $lead = Lead::factory()->create(['status' => 'novo']);

    Livewire::test(LeadResource\Pages\ListLeads::class)
        ->callTableAction('contato', $lead, ['nota' => 'Liguei e combinei visita', 'status' => 'em_contato'])
        ->assertHasNoTableActionErrors();

    $lead->refresh();
    expect($lead->status->value)->toBe('em_contato')
        ->and($lead->last_contact_at)->not->toBeNull()
        ->and($lead->events()->where('tipo', 'contato')->count())->toBe(1)
        ->and($lead->events()->where('tipo', 'status')->count())->toBe(1);
});
