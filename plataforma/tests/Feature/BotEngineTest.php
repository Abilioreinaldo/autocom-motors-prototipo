<?php

use App\Livewire\Site\ChatBot;
use App\Models\BotSession;
use App\Models\Lead;
use App\Models\Vehicle;
use App\Services\BotEngine;
use Livewire\Livewire;

function botSession(): BotSession
{
    return BotSession::create(['messages' => []]);
}

it('busca no estoque por carroceria, câmbio e teto de preço', function () {
    Vehicle::factory()->create(['marca' => 'Honda', 'modelo' => 'HR-V', 'carroceria' => 'suv', 'cambio' => 'cvt', 'preco' => 120000]);
    Vehicle::factory()->create(['marca' => 'Toyota', 'modelo' => 'Hilux', 'carroceria' => 'picape', 'cambio' => 'automatico', 'preco' => 220000]);
    Vehicle::factory()->create(['marca' => 'Fiat', 'modelo' => 'Mobi', 'carroceria' => 'hatch', 'cambio' => 'manual', 'preco' => 50000]);

    $r = app(BotEngine::class)->reply(botSession(), 'quero um SUV automático até 150 mil');

    expect($r['cards'])->toHaveCount(1)->and($r['cards'][0]->modelo)->toBe('HR-V')->and($r['text'])->toContain('Encontrei 1');
});

it('busca por marca e modelo', function () {
    Vehicle::factory()->create(['marca' => 'Chevrolet', 'modelo' => 'Onix']);
    Vehicle::factory()->create(['marca' => 'Chevrolet', 'modelo' => 'Tracker']);

    $r = app(BotEngine::class)->reply(botSession(), 'tem onix?');
    expect($r['cards'])->toHaveCount(1)->and($r['cards'][0]->modelo)->toBe('Onix');

    $r = app(BotEngine::class)->reply(botSession(), 'chevrolet');
    expect($r['cards'])->toHaveCount(2);
});

it('conduz a simulação em etapas e cria lead tipo bot', function () {
    $s = botSession();
    $e = app(BotEngine::class);

    expect($e->reply($s, 'simular financiamento')['text'])->toContain('valor do veículo');
    expect($e->reply($s, '100 mil')['text'])->toContain('entrada');
    expect($e->reply($s, '20 mil')['text'])->toContain('parcelas');
    $r = $e->reply($s, '48x');
    expect($r['text'])->toContain('48x')->toContain('Simulação ilustrativa');
    expect($e->reply($s, 'Ana Souza')['text'])->toContain('Ana');
    $r = $e->reply($s, '(15) 99999-0000');

    expect($r['links'])->not->toBeEmpty()
        ->and(Lead::count())->toBe(1);
    $lead = Lead::first();
    expect($lead->tipo->value)->toBe('bot')
        ->and($lead->nome)->toBe('Ana Souza')
        ->and($lead->meta['sim']['parcelas'])->toBe(48)
        ->and($lead->meta['sim']['parcela'])->toBe(round(80000 * 0.0169 / (1 - 1.0169 ** -48), 2))
        ->and($s->lead_id)->toBe($lead->id);
});

it('trata troca e LocarX', function () {
    $e = app(BotEngine::class);
    $s = botSession();

    expect($e->reply($s, 'tenho um carro na troca')['text'])->toContain('marca, modelo e ano');
    expect($e->reply($s, 'Fiat Argo 2019')['text'])->toContain('km');
    expect($e->reply($s, '60 mil')['text'])->toContain('nome');

    $r = $e->reply(botSession(), 'quero alugar um carro');
    expect($r['text'])->toContain('LocarX')->and($r['links'][0][1])->toContain('wa.me');
});

it('o componente Livewire persiste a conversa em bot_sessions', function () {
    Vehicle::factory()->create(['marca' => 'Jeep', 'modelo' => 'Renegade', 'carroceria' => 'suv']);

    Livewire::test(ChatBot::class)
        ->call('abrir')->assertSet('aberto', true)
        ->set('texto', 'quero um suv')->call('enviar')
        ->assertSee('Encontrei 1');

    $s = BotSession::first();
    expect($s)->not->toBeNull()->and($s->mensagens)->toBe(3)->and($s->messages[1]['text'])->toBe('quero um suv');
});
