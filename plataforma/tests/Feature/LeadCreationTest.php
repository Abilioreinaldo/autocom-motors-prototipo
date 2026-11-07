<?php

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\Vehicle;
use App\Services\LeadService;

it('cria um lead de proposta vinculado ao veículo com evento inicial', function () {
    $v = Vehicle::factory()->create();

    $lead = app(LeadService::class)->create(LeadType::Proposta, [
        'nome' => 'Maria Teste', 'fone' => '(15) 99999-1234', 'email' => 'maria@exemplo.com',
        'mensagem' => 'Aceita 120 mil?', 'meta' => ['valor_proposta' => 120000, 'vazio' => ''],
    ], $v);

    expect($lead->status)->toBe(LeadStatus::Novo)
        ->and($lead->tipo)->toBe(LeadType::Proposta)
        ->and($lead->veiculo_id)->toBe($v->id)
        ->and($lead->meta)->toBe(['valor_proposta' => 120000])
        ->and($lead->events()->count())->toBe(1)
        ->and($lead->events()->first()->tipo)->toBe('criado')
        ->and($lead->whatsapp_url)->toStartWith('https://wa.me/5515999991234?text=');
});

it('registra evento ao mudar o status', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Novo]);
    $lead->update(['status' => LeadStatus::EmContato]);

    $ev = $lead->events()->where('tipo', 'status')->first();
    expect($ev)->not->toBeNull()->and($ev->de)->toBe('novo')->and($ev->para)->toBe('em_contato');
});

it('registra contato e atualiza last_contact_at', function () {
    $lead = Lead::factory()->create();
    $lead->registerContact('Liguei, pediu retorno amanhã.');

    expect($lead->fresh()->last_contact_at)->not->toBeNull()
        ->and($lead->events()->where('tipo', 'contato')->count())->toBe(1);
});

it('gera lead ao clicar no WhatsApp', function () {
    $v = Vehicle::factory()->create();
    $lead = app(LeadService::class)->whatsappClick($v);

    expect($lead->tipo)->toBe(LeadType::Whatsapp)->and($lead->veiculo_id)->toBe($v->id);
});
