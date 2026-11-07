<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Novo lead')];
    }

    /** Abas por status: visão "kanban" em colunas de lista */
    public function getTabs(): array
    {
        $tabs = ['todos' => Tab::make('Todos')->badge(Lead::count())];
        foreach (LeadStatus::funnel() as $s) {
            $tabs[$s->value] = Tab::make($s->getLabel())
                ->badge(Lead::where('status', $s)->count())
                ->badgeColor($s->getColor())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', $s));
        }

        return $tabs;
    }
}
