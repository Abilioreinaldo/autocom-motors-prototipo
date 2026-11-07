<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\LeadEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Histórico';

    protected static ?string $modelLabel = 'anotação';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('nota')->label('Anotação')->required()->rows(3)->columnSpanFull(),
            Forms\Components\Hidden::make('tipo')->default('nota'),
            Forms\Components\Hidden::make('user_id')->default(fn () => auth()->id()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('descricao')->label('Evento')->getStateUsing(fn (LeadEvent $r) => $r->descricao)->badge()->color(fn (LeadEvent $r) => match ($r->tipo) { 'criado' => 'info', 'status' => 'warning', 'contato', 'whatsapp' => 'success', default => 'gray' }),
                Tables\Columns\TextColumn::make('nota')->wrap()->placeholder('—'),
                Tables\Columns\TextColumn::make('user.name')->label('Por')->placeholder('sistema'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Adicionar anotação')])
            ->actions([Tables\Actions\DeleteAction::make()->visible(fn (LeadEvent $r) => $r->tipo === 'nota')]);
    }
}
