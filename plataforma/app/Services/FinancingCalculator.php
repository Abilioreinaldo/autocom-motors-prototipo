<?php

namespace App\Services;

use App\Support\Settings;
use InvalidArgumentException;

/**
 * Simulador de financiamento pelo sistema PRICE (parcelas fixas).
 *
 *   parcela = f * i / (1 - (1 + i)^-n)
 *
 * onde f = valor financiado, i = taxa mensal (decimal), n = número de parcelas.
 */
class FinancingCalculator
{
    public const DISCLAIMER = 'Simulação ilustrativa, sujeita à análise e condições da instituição financeira.';

    /**
     * @param  float  $preco  valor do veículo
     * @param  float  $entrada  valor de entrada
     * @param  int  $parcelas  número de parcelas (n)
     * @param  float|null  $taxaMensalPercent  taxa em % a.m. (ex.: 1.69). Null = taxa configurada.
     * @return array{preco:float,entrada:float,financiado:float,parcelas:int,taxa_mensal:float,parcela:float,total:float,juros:float,cet_anual_aprox:float,disclaimer:string}
     */
    public function simulate(float $preco, float $entrada, int $parcelas, ?float $taxaMensalPercent = null): array
    {
        if ($preco <= 0) {
            throw new InvalidArgumentException('Valor do veículo inválido.');
        }
        if ($entrada < 0 || $entrada >= $preco) {
            throw new InvalidArgumentException('A entrada deve ser menor que o valor do veículo.');
        }
        if ($parcelas < 1 || $parcelas > 120) {
            throw new InvalidArgumentException('Número de parcelas inválido.');
        }

        $taxa = $taxaMensalPercent ?? $this->defaultRate();
        $i = $taxa / 100;
        $f = $preco - $entrada;

        $parcela = $i > 0
            ? $f * $i / (1 - (1 + $i) ** -$parcelas)
            : $f / $parcelas;

        $parcela = round($parcela, 2);
        $total = round($parcela * $parcelas, 2);

        return [
            'preco' => round($preco, 2),
            'entrada' => round($entrada, 2),
            'financiado' => round($f, 2),
            'parcelas' => $parcelas,
            'taxa_mensal' => $taxa,
            'parcela' => $parcela,
            'total' => $total,
            'juros' => round($total - $f, 2),
            'cet_anual_aprox' => round(((1 + $i) ** 12 - 1) * 100, 2),
            'disclaimer' => $this->disclaimer(),
        ];
    }

    /** Calcula apenas a parcela (sem validações extras) */
    public function installment(float $financiado, int $parcelas, float $taxaMensalPercent): float
    {
        $i = $taxaMensalPercent / 100;
        if ($i <= 0) {
            return round($financiado / $parcelas, 2);
        }

        return round($financiado * $i / (1 - (1 + $i) ** -$parcelas), 2);
    }

    public function defaultRate(): float
    {
        return Settings::float('financiamento.taxa_mensal', (float) config('autocom.financiamento.taxa_mensal', 1.69));
    }

    public function minDownPaymentPercent(): float
    {
        return Settings::float('financiamento.entrada_minima_percent', (float) config('autocom.financiamento.entrada_minima_percent', 20));
    }

    /** @return list<int> */
    public function terms(): array
    {
        return config('autocom.financiamento.prazos', [12, 24, 36, 48, 60]);
    }

    public function disclaimer(): string
    {
        return (string) Settings::get('financiamento.disclaimer', self::DISCLAIMER);
    }
}
