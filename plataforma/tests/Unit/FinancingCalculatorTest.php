<?php

use App\Services\FinancingCalculator;

it('calcula a parcela pelo sistema PRICE', function () {
    $calc = new FinancingCalculator;

    // f = 80.000, i = 1,69% a.m., n = 48  → parcela = f*i/(1-(1+i)^-n)
    $i = 0.0169;
    $esperado = round(80000 * $i / (1 - (1 + $i) ** -48), 2);

    $r = $calc->simulate(preco: 100000, entrada: 20000, parcelas: 48, taxaMensalPercent: 1.69);

    expect($r['financiado'])->toBe(80000.0)
        ->and($r['parcela'])->toBe($esperado)
        ->and($r['total'])->toBe(round($esperado * 48, 2))
        ->and($r['juros'])->toBe(round($esperado * 48 - 80000, 2))
        ->and($r['disclaimer'])->toContain('Simulação ilustrativa');
});

it('usa a taxa padrão de 1,69% quando nenhuma é informada', function () {
    $calc = new FinancingCalculator;

    expect($calc->defaultRate())->toBe(1.69)
        ->and($calc->simulate(50000, 10000, 12)['taxa_mensal'])->toBe(1.69);
});

it('com taxa zero divide o valor financiado pelo número de parcelas', function () {
    $r = (new FinancingCalculator)->simulate(60000, 0, 12, 0);

    expect($r['parcela'])->toBe(5000.0)->and($r['juros'])->toBe(0.0);
});

it('rejeita entrada maior ou igual ao preço', function () {
    (new FinancingCalculator)->simulate(50000, 50000, 24);
})->throws(InvalidArgumentException::class);

it('rejeita número de parcelas inválido', function () {
    (new FinancingCalculator)->simulate(50000, 5000, 0);
})->throws(InvalidArgumentException::class);
