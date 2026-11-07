<?php

namespace App\Livewire\Site;

use App\Enums\LeadType;
use App\Models\Vehicle;
use App\Services\FinancingCalculator;
use App\Services\LeadService;
use App\Support\Whatsapp;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Simulador extends Component
{
    public ?int $vehicleId = null;

    public float $preco = 80000;

    public float $entrada = 16000;

    public int $prazo = 48;

    public string $nome = '';

    public string $fone = '';

    public bool $enviado = false;

    public ?string $waUrl = null;

    public function mount(?Vehicle $vehicle = null): void
    {
        if ($vehicle?->exists) {
            $this->vehicleId = $vehicle->id;
            $this->preco = (float) $vehicle->preco;
        }
        $this->entrada = round($this->preco * app(FinancingCalculator::class)->minDownPaymentPercent() / 100, -2);
    }

    public function updatedPreco(): void
    {
        $this->preco = max(1000, (float) $this->preco);
        $this->entrada = min($this->entrada, $this->preco - 1000);
    }

    public function updatedEntrada(): void
    {
        $this->entrada = max(0, min((float) $this->entrada, $this->preco - 1000));
    }

    #[Computed]
    public function calc(): FinancingCalculator
    {
        return app(FinancingCalculator::class);
    }

    #[Computed]
    public function vehicle(): ?Vehicle
    {
        return $this->vehicleId ? Vehicle::find($this->vehicleId) : null;
    }

    #[Computed]
    public function resultado(): ?array
    {
        try {
            return $this->calc->simulate($this->preco, $this->entrada, $this->prazo);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function enviar(LeadService $leads): void
    {
        $this->validate([
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'fone' => ['required', 'string', 'min:10', 'max:30'],
        ], [], ['nome' => 'nome', 'fone' => 'telefone']);

        $r = $this->resultado;
        if (! $r) {
            return;
        }

        $v = $this->vehicle;
        $lead = $leads->create(LeadType::Financiamento, [
            'nome' => $this->nome,
            'fone' => $this->fone,
            'mensagem' => sprintf('Simulação: %s · entrada R$ %s · %dx de R$ %s (%.2f%% a.m.)', $v?->titulo_completo ?? 'valor livre', number_format($r['entrada'], 0, ',', '.'), $r['parcelas'], number_format($r['parcela'], 2, ',', '.'), $r['taxa_mensal']),
            'meta' => ['simulacao' => $r],
        ], $v);

        $msg = sprintf("Olá! Fiz uma simulação no site da Autocom Motors%s: entrada de R$ %s em %dx de R$ %s. Quero uma proposta. (ref. #%d)",
            $v ? ' para o '.$v->nome_whatsapp : '', number_format($r['entrada'], 0, ',', '.'), $r['parcelas'], number_format($r['parcela'], 2, ',', '.'), $lead->id);

        $this->waUrl = Whatsapp::link($msg);
        $this->enviado = true;
    }

    public function render()
    {
        return view('livewire.site.simulador');
    }
}
