<?php

use App\Livewire\Site\LeadForm;
use App\Livewire\Site\Simulador;
use App\Models\Vehicle;
use Livewire\Livewire;

it('cria lead de proposta pelo formulário Livewire', function () {
    $v = Vehicle::factory()->create();

    Livewire::test(LeadForm::class, ['tipo' => 'proposta', 'vehicle' => $v])
        ->set('nome', 'João Teste')->set('fone', '15999990000')->set('valorProposta', 90000)
        ->call('enviar')
        ->assertHasNoErrors()
        ->assertSet('enviado', true);

    $this->assertDatabaseHas('leads', ['tipo' => 'proposta', 'nome' => 'João Teste', 'veiculo_id' => $v->id]);
});

it('valida campos obrigatórios da troca', function () {
    Livewire::test(LeadForm::class, ['tipo' => 'troca'])
        ->set('nome', 'A')->call('enviar')
        ->assertHasErrors(['nome', 'fone', 'trocaMarca', 'trocaModelo', 'trocaAno']);
});

it('bloqueia honeypot preenchido', function () {
    Livewire::test(LeadForm::class, ['tipo' => 'contato'])
        ->set('nome', 'Bot')->set('fone', '15999990000')->set('website', 'spam')
        ->call('enviar')->assertHasErrors(['website']);

    $this->assertDatabaseCount('leads', 0);
});

it('simula e envia lead de financiamento', function () {
    $v = Vehicle::factory()->create(['preco' => 100000]);

    $c = Livewire::test(Simulador::class, ['vehicle' => $v])
        ->set('entrada', 20000)->set('prazo', 48);

    expect($c->get('resultado')['parcela'])->toBe(round(80000 * 0.0169 / (1 - 1.0169 ** -48), 2));

    $c->set('nome', 'Maria')->set('fone', '15999991111')->call('enviar')->assertHasNoErrors()->assertSet('enviado', true);

    $this->assertDatabaseHas('leads', ['tipo' => 'financiamento', 'veiculo_id' => $v->id, 'nome' => 'Maria']);
});
