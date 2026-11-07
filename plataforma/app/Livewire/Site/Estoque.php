<?php

namespace App\Livewire\Site;

use App\Enums\Cambio;
use App\Enums\Carroceria;
use App\Enums\Combustivel;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.site')]
#[Title('Estoque')]
class Estoque extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $busca = '';
    #[Url] public string $marca = '';
    #[Url] public string $modelo = '';
    #[Url] public string $versao = '';
    #[Url(as: 'ano_min')] public ?int $anoMin = null;
    #[Url(as: 'ano_max')] public ?int $anoMax = null;
    #[Url(as: 'preco_min')] public ?int $precoMin = null;
    #[Url(as: 'preco_max')] public ?int $precoMax = null;
    #[Url(as: 'km_max')] public ?int $kmMax = null;
    #[Url] public array $combustivel = [];
    #[Url] public array $cambio = [];
    #[Url] public array $carroceria = [];
    #[Url] public array $cor = [];
    #[Url] public bool $oferta = false;
    #[Url] public string $ordem = 'recentes';

    public bool $mostrarFiltros = false;

    public function updated($prop): void
    {
        if ($prop !== 'mostrarFiltros') {
            $this->resetPage();
        }
        if ($prop === 'marca') {
            $this->modelo = '';
            $this->versao = '';
        }
        if ($prop === 'modelo') {
            $this->versao = '';
        }
    }

    public function limpar(): void
    {
        $this->reset(['busca', 'marca', 'modelo', 'versao', 'anoMin', 'anoMax', 'precoMin', 'precoMax', 'kmMax', 'combustivel', 'cambio', 'carroceria', 'cor', 'oferta']);
        $this->resetPage();
    }

    public function toggle(string $campo, string $valor): void
    {
        $lista = $this->{$campo};
        $this->{$campo} = in_array($valor, $lista, true) ? array_values(array_diff($lista, [$valor])) : [...$lista, $valor];
        $this->resetPage();
    }

    public function removerFiltro(string $campo, ?string $valor = null): void
    {
        if ($valor !== null && is_array($this->{$campo})) {
            $this->toggle($campo, $valor);

            return;
        }
        $this->reset($campo);
        if ($campo === 'marca') {
            $this->reset(['modelo', 'versao']);
        }
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        return Vehicle::query()->published()->with('cover');
    }

    #[Computed]
    public function marcas(): array
    {
        return $this->baseQuery()->select('marca')->distinct()->orderBy('marca')->pluck('marca')->all();
    }

    #[Computed]
    public function modelos(): array
    {
        if ($this->marca === '') {
            return [];
        }

        return $this->baseQuery()->where('marca', $this->marca)->select('modelo')->distinct()->orderBy('modelo')->pluck('modelo')->all();
    }

    #[Computed]
    public function versoes(): array
    {
        if ($this->modelo === '') {
            return [];
        }

        return $this->baseQuery()->where('marca', $this->marca)->where('modelo', $this->modelo)->whereNotNull('versao')->select('versao')->distinct()->orderBy('versao')->pluck('versao')->all();
    }

    #[Computed]
    public function cores(): array
    {
        return $this->baseQuery()->whereNotNull('cor')->select('cor')->distinct()->orderBy('cor')->pluck('cor')->all();
    }

    #[Computed]
    public function anos(): array
    {
        $min = (int) ($this->baseQuery()->min('ano_mod') ?: date('Y') - 10);
        $max = (int) ($this->baseQuery()->max('ano_mod') ?: date('Y') + 1);

        return range($max, $min);
    }

    #[Computed]
    public function filtrosAtivos(): array
    {
        $ativos = [];
        foreach (['busca' => 'Busca', 'marca' => 'Marca', 'modelo' => 'Modelo', 'versao' => 'Versão'] as $k => $label) {
            if ($this->{$k} !== '') {
                $ativos[] = ['campo' => $k, 'valor' => null, 'label' => "$label: {$this->{$k}}"];
            }
        }
        if ($this->anoMin) {
            $ativos[] = ['campo' => 'anoMin', 'valor' => null, 'label' => "Ano ≥ {$this->anoMin}"];
        }
        if ($this->anoMax) {
            $ativos[] = ['campo' => 'anoMax', 'valor' => null, 'label' => "Ano ≤ {$this->anoMax}"];
        }
        if ($this->precoMin) {
            $ativos[] = ['campo' => 'precoMin', 'valor' => null, 'label' => 'A partir de R$ '.number_format($this->precoMin, 0, ',', '.')];
        }
        if ($this->precoMax) {
            $ativos[] = ['campo' => 'precoMax', 'valor' => null, 'label' => 'Até R$ '.number_format($this->precoMax, 0, ',', '.')];
        }
        if ($this->kmMax) {
            $ativos[] = ['campo' => 'kmMax', 'valor' => null, 'label' => 'Até '.number_format($this->kmMax, 0, ',', '.').' km'];
        }
        foreach (['combustivel' => Combustivel::class, 'cambio' => Cambio::class, 'carroceria' => Carroceria::class] as $k => $enum) {
            foreach ($this->{$k} as $v) {
                $ativos[] = ['campo' => $k, 'valor' => $v, 'label' => $enum::tryFrom($v)?->getLabel() ?? $v];
            }
        }
        foreach ($this->cor as $v) {
            $ativos[] = ['campo' => 'cor', 'valor' => $v, 'label' => $v];
        }
        if ($this->oferta) {
            $ativos[] = ['campo' => 'oferta', 'valor' => null, 'label' => 'Só ofertas'];
        }

        return $ativos;
    }

    public function render()
    {
        $q = $this->baseQuery()
            ->search($this->busca)
            ->when($this->marca !== '', fn ($w) => $w->where('marca', $this->marca))
            ->when($this->modelo !== '', fn ($w) => $w->where('modelo', $this->modelo))
            ->when($this->versao !== '', fn ($w) => $w->where('versao', $this->versao))
            ->when($this->anoMin, fn ($w) => $w->where('ano_mod', '>=', $this->anoMin))
            ->when($this->anoMax, fn ($w) => $w->where('ano_mod', '<=', $this->anoMax))
            ->when($this->precoMin, fn ($w) => $w->where('preco', '>=', $this->precoMin))
            ->when($this->precoMax, fn ($w) => $w->where('preco', '<=', $this->precoMax))
            ->when($this->kmMax, fn ($w) => $w->where('km', '<=', $this->kmMax))
            ->when($this->combustivel, fn ($w) => $w->whereIn('combustivel', $this->combustivel))
            ->when($this->cambio, fn ($w) => $w->whereIn('cambio', $this->cambio))
            ->when($this->carroceria, fn ($w) => $w->whereIn('carroceria', $this->carroceria))
            ->when($this->cor, fn ($w) => $w->whereIn('cor', $this->cor))
            ->when($this->oferta, fn ($w) => $w->onOffer());

        match ($this->ordem) {
            'menor_preco' => $q->orderBy('preco'),
            'maior_preco' => $q->orderByDesc('preco'),
            'menor_km' => $q->orderBy('km'),
            'mais_novo' => $q->orderByDesc('ano_mod')->orderBy('km'),
            default => $q->orderByDesc('destaque')->orderByDesc('published_at'),
        };

        return view('livewire.site.estoque', [
            'veiculos' => $q->paginate(12),
            'combustiveis' => Combustivel::cases(),
            'cambios' => Cambio::cases(),
            'carrocerias' => Carroceria::cases(),
        ]);
    }
}
