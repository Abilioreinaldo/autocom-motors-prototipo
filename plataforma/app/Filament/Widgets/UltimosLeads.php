<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UltimosLeads extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Últimos leads';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Lead::query()->with(['vehicle'])->latest()->limit(8))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('nome')->weight('bold')->description(fn (Lead $r) => $r->fone),
                Tables\Columns\TextColumn::make('tipo')->badge(),
                Tables\Columns\TextColumn::make('vehicle.titulo')->label('Veículo')->placeholder('—'),
                Tables\Columns\SelectColumn::make('status')->options(LeadStatus::class)->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('created_at')->label('Recebido')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')->label('')->icon('heroicon-o-chat-bubble-oval-left')->color('success')->url(fn (Lead $r) => $r->whatsapp_url)->openUrlInNewTab()->visible(fn (Lead $r) => (bool) $r->whatsapp_url),
                Tables\Actions\Action::make('ver')->label('Abrir')->url(fn (Lead $r) => LeadResource::getUrl('view', ['record' => $r])),
            ]);
    }
}
