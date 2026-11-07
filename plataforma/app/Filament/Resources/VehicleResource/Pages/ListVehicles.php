<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Enums\VehicleStatus;
use App\Filament\Pages\ImportarPorLink;
use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importar')->label('Importar por link')->icon('heroicon-o-link')->color('gray')->url(ImportarPorLink::getUrl()),
            Actions\CreateAction::make()->label('Novo veículo'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),
            'disponiveis' => Tab::make('Disponíveis')->modifyQueryUsing(fn (Builder $q) => $q->where('status', VehicleStatus::Disponivel)),
            'reservados' => Tab::make('Reservados')->modifyQueryUsing(fn (Builder $q) => $q->where('status', VehicleStatus::Reservado)),
            'vendidos' => Tab::make('Vendidos')->modifyQueryUsing(fn (Builder $q) => $q->where('status', VehicleStatus::Vendido)),
            'destaques' => Tab::make('Destaques')->modifyQueryUsing(fn (Builder $q) => $q->where('destaque', true)),
        ];
    }
}
