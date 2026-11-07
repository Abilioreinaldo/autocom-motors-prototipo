<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">1. Cole o link do anúncio</x-slot>
        <x-slot name="description">
            Integração oficial via API Site da Webmotors — driver atual: <b>{{ config('webmotors.driver') }}</b>{{ config('webmotors.enabled') ? '' : ' (WEBMOTORS_ENABLED=false)' }}. Nenhuma página pública é lida (sem scraping).
        </x-slot>
        <form wire:submit="buscar" class="space-y-4">
            {{ $this->urlForm }}
            <div class="flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass" wire:loading.attr="disabled">Buscar anúncio</x-filament::button>
                @if ($externalId)<span class="text-sm text-gray-400">ID identificado: <code class="font-mono text-primary-400">{{ $externalId }}</code></span>@endif
                @if ($stage !== 'url')<x-filament::button color="gray" wire:click="reiniciar" size="sm">Recomeçar</x-filament::button>@endif
            </div>
        </form>
    </x-filament::section>

    @if ($duplicate)
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <x-slot name="heading">Possível duplicidade</x-slot>
            <p class="text-sm">
                @if ($duplicate['reason'] === 'external_id')
                    Este anúncio (<code>#{{ $externalId }}</code>) já está vinculado a <b>{{ $duplicate['titulo'] }}</b>.
                @else
                    Já existe <b>{{ $duplicate['titulo'] }}</b> cadastrado com mesma marca, modelo, ano e km semelhante (±{{ config('webmotors.sync.km_tolerance') }} km).
                @endif
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-filament::button tag="a" href="{{ $this->getDuplicateUrl() }}" color="gray" size="sm" icon="heroicon-o-pencil-square">Abrir veículo existente</x-filament::button>
                @if ($stage === 'review' || $stage === 'manual')
                    @if ($duplicate['reason'] === 'specs')
                        <x-filament::button wire:click="vincular" color="warning" size="sm" icon="heroicon-o-link">Vincular anúncio ao existente</x-filament::button>
                        <x-filament::button wire:click="cadastrarMesmoAssim" color="danger" size="sm" outlined>Cadastrar mesmo assim</x-filament::button>
                    @endif
                @endif
            </div>
        </x-filament::section>
    @endif

    @if (in_array($stage, ['review', 'manual']))
        <x-filament::section>
            <x-slot name="heading">2. {{ $stage === 'review' ? 'Revise os dados importados' : 'Cadastro assistido' }}</x-slot>
            <x-slot name="description">
                @if ($stage === 'review')
                    Dados preenchidos a partir do anúncio #{{ $externalId }}. Ajuste o que precisar: preço e descrição editados aqui ficam protegidos das próximas sincronizações.
                @else
                    O anúncio #{{ $externalId }} não foi encontrado na fonte. Preencha os dados manualmente — o link será salvo como <code>source_url</code> para referência.
                @endif
            </x-slot>

            @if ($fotos)
                <div class="mb-6">
                    <p class="mb-2 text-sm font-medium text-gray-300">{{ count($fotos) }} foto(s) do anúncio serão importadas</p>
                    <div class="grid grid-cols-3 gap-2 md:grid-cols-6">
                        @foreach ($fotos as $f)<img src="{{ $f }}" alt="" class="aspect-[16/10] w-full rounded-lg object-cover bg-gray-800">@endforeach
                    </div>
                </div>
            @endif

            <form wire:submit="importar" class="space-y-6">
                {{ $this->vehicleForm }}
                <div class="flex flex-wrap gap-2">
                    <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled">{{ $stage === 'review' ? 'Importar veículo' : 'Cadastrar veículo' }}</x-filament::button>
                    <x-filament::button color="gray" wire:click="reiniciar">Cancelar</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    @endif

    @if ($stage === 'done')
        <x-filament::section icon="heroicon-o-check-circle" icon-color="success">
            <x-slot name="heading">Concluído</x-slot>
            <p class="text-sm">Veículo salvo e vinculado ao anúncio #{{ $externalId }}.</p>
            <div class="mt-3 flex gap-2">
                <x-filament::button tag="a" href="{{ $this->getEditUrl() }}" icon="heroicon-o-pencil-square">Abrir veículo</x-filament::button>
                <x-filament::button color="gray" wire:click="reiniciar">Importar outro</x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
