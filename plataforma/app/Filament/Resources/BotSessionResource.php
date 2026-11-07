<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotSessionResource\Pages;
use App\Models\BotSession;
use App\Support\Settings;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BotSessionResource extends Resource
{
    protected static ?string $model = BotSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Assistente virtual';

    protected static ?string $modelLabel = 'conversa do assistente';

    protected static ?string $pluralModelLabel = 'conversas do assistente';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->columns(4)->schema([
                Infolists\Components\TextEntry::make('nome')->placeholder('Não informado'),
                Infolists\Components\TextEntry::make('fone')->label('Telefone')->placeholder('—'),
                Infolists\Components\TextEntry::make('lead.id')->label('Lead')->placeholder('Sem lead')->formatStateUsing(fn ($state) => "#$state")->url(fn (BotSession $r) => $r->lead ? LeadResource::getUrl('view', ['record' => $r->lead]) : null),
                Infolists\Components\TextEntry::make('mensagens')->label('Mensagens'),
                Infolists\Components\TextEntry::make('created_at')->label('Início')->dateTime('d/m/Y H:i'),
                Infolists\Components\TextEntry::make('last_message_at')->label('Última mensagem')->dateTime('d/m/Y H:i'),
            ]),
            Infolists\Components\Section::make('Transcrição')->schema([
                Infolists\Components\ViewEntry::make('messages')->label('')->view('filament.infolists.transcript'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nome')->placeholder('Visitante')->searchable()->weight('bold')->description(fn (BotSession $r) => $r->fone),
                Tables\Columns\TextColumn::make('preview')->label('Última mensagem do cliente')->getStateUsing(fn (BotSession $r) => collect($r->messages)->where('role', 'user')->last()['text'] ?? '—')->limit(50)->wrap(),
                Tables\Columns\TextColumn::make('mensagens')->label('Msgs')->sortable()->alignCenter(),
                Tables\Columns\IconColumn::make('lead_id')->label('Lead')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-minus'),
                Tables\Columns\TextColumn::make('last_message_at')->label('Última atividade')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('com_lead')->label('Gerou lead')->queries(true: fn (Builder $q) => $q->whereNotNull('lead_id'), false: fn (Builder $q) => $q->whereNull('lead_id')),
            ])
            ->actions([Tables\Actions\ViewAction::make()->label('Transcrição'), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->headerActions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn () => Settings::bool('bot.ativo', true) ? 'Assistente ATIVO — desativar' : 'Assistente DESATIVADO — ativar')
                    ->icon(fn () => Settings::bool('bot.ativo', true) ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn () => Settings::bool('bot.ativo', true) ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(function () {
                        Settings::set('bot.ativo', Settings::bool('bot.ativo', true) ? '0' : '1', 'bot');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotSessions::route('/'),
            'view' => Pages\ViewBotSession::route('/{record}'),
        ];
    }
}
