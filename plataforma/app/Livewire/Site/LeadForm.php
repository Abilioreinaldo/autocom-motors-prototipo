<?php

namespace App\Livewire\Site;

use App\Enums\LeadType;
use App\Models\Vehicle;
use App\Services\LeadService;
use App\Support\Whatsapp;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Formulário genérico de lead: proposta | troca | contato (financiamento usa o Simulador).
 */
class LeadForm extends Component
{
    public string $tipo = 'contato';

    public ?int $vehicleId = null;

    public string $titulo = '';

    public string $nome = '';

    public string $fone = '';

    public string $email = '';

    public string $mensagem = '';

    public ?float $valorProposta = null;

    // troca
    public string $trocaMarca = '';

    public string $trocaModelo = '';

    public ?int $trocaAno = null;

    public ?int $trocaKm = null;

    public string $website = ''; // honeypot

    public bool $enviado = false;

    public ?string $waUrl = null;

    public function mount(string $tipo = 'contato', ?Vehicle $vehicle = null, string $titulo = ''): void
    {
        $this->tipo = in_array($tipo, ['proposta', 'troca', 'contato'], true) ? $tipo : 'contato';
        $this->vehicleId = $vehicle?->exists ? $vehicle->id : null;
        $this->titulo = $titulo ?: match ($this->tipo) {
            'proposta' => 'Fazer uma proposta',
            'troca' => 'Avaliar meu usado na troca',
            default => 'Fale com a gente',
        };
    }

    #[Computed]
    public function vehicle(): ?Vehicle
    {
        return $this->vehicleId ? Vehicle::find($this->vehicleId) : null;
    }

    protected function rules(): array
    {
        $r = [
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'fone' => ['required', 'string', 'min:10', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'mensagem' => ['nullable', 'string', 'max:2000'],
            'website' => ['max:0'],
        ];
        if ($this->tipo === 'proposta') {
            $r['valorProposta'] = ['nullable', 'numeric', 'min:1000'];
        }
        if ($this->tipo === 'troca') {
            $r['trocaMarca'] = ['required', 'string', 'max:60'];
            $r['trocaModelo'] = ['required', 'string', 'max:80'];
            $r['trocaAno'] = ['required', 'integer', 'min:1990', 'max:'.(date('Y') + 1)];
            $r['trocaKm'] = ['nullable', 'integer', 'min:0'];
        }

        return $r;
    }

    protected function validationAttributes(): array
    {
        return ['nome' => 'nome', 'fone' => 'telefone', 'valorProposta' => 'valor', 'trocaMarca' => 'marca', 'trocaModelo' => 'modelo', 'trocaAno' => 'ano', 'trocaKm' => 'km'];
    }

    public function enviar(LeadService $leads): void
    {
        $this->validate();

        $meta = [];
        if ($this->tipo === 'proposta') {
            $meta['valor_proposta'] = $this->valorProposta;
        }
        if ($this->tipo === 'troca') {
            $meta['troca'] = ['marca' => $this->trocaMarca, 'modelo' => $this->trocaModelo, 'ano' => $this->trocaAno, 'km' => $this->trocaKm];
        }

        $v = $this->vehicle;
        $lead = $leads->create(LeadType::from($this->tipo), [
            'nome' => $this->nome, 'fone' => $this->fone, 'email' => $this->email ?: null,
            'mensagem' => $this->mensagem ?: null, 'meta' => $meta,
        ], $v);

        $msg = match ($this->tipo) {
            'proposta' => sprintf('Olá! Enviei uma proposta%s pelo site da Autocom Motors%s. (ref. #%d)', $v ? ' para o '.$v->nome_whatsapp : '', $this->valorProposta ? ' de R$ '.number_format($this->valorProposta, 0, ',', '.') : '', $lead->id),
            'troca' => sprintf('Olá! Quero avaliar meu %s %s %d%s na troca%s. (ref. #%d)', $this->trocaMarca, $this->trocaModelo, $this->trocaAno, $this->trocaKm ? ' com '.number_format($this->trocaKm, 0, ',', '.').' km' : '', $v ? ' pelo '.$v->nome_whatsapp : '', $lead->id),
            default => sprintf('Olá! Enviei uma mensagem pelo site da Autocom Motors. (ref. #%d)', $lead->id),
        };

        $this->waUrl = Whatsapp::link($msg);
        $this->enviado = true;
    }

    public function render()
    {
        return view('livewire.site.lead-form');
    }
}
