<x-filament-panels::page>
    <form wire:submit="salvar" class="space-y-6">
        {{ $this->form }}
        <div class="flex gap-2">
            <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled">Salvar configurações</x-filament::button>
            <x-filament::button tag="a" href="{{ route('home') }}" target="_blank" color="gray" icon="heroicon-o-arrow-top-right-on-square">Ver site</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
