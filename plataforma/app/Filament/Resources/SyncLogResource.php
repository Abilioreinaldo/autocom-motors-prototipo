<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyncLogResource\Pages;
use App\Jobs\SyncWebmotorsInventory;
use App\Models\SyncLog;
use App\Services\WebmotorsSyncService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Integrações';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Sincronizações';

    protected static ?string $modelLabel = 'sincronização';

    protected static ?string $pluralModelLabel = 'sincronizações';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('provider')->label('Provedor')->badge(),
                Tables\Columns\TextColumn::make('trigger')->label('Origem')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) { 'success' => 'success', 'error' => 'danger', 'running' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('fetched')->label('Lidos')->alignCenter(),
                Tables\Columns\TextColumn::make('created')->label('Novos')->alignCenter(),
                Tables\Columns\TextColumn::make('updated')->label('Atualiz.')->alignCenter(),
                Tables\Columns\TextColumn::make('missing')->label('Ausentes')->alignCenter(),
                Tables\Columns\TextColumn::make('message')->label('Mensagem')->limit(60)->wrap()->tooltip(fn (SyncLog $r) => $r->message),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync')->label('Sincronizar agora')->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Driver atual: '.config('webmotors.driver').(config('webmotors.enabled') ? '' : ' (integração DESATIVADA em .env — rodará mesmo assim, com o driver atual).'))
                    ->action(function () {
                        $log = app(WebmotorsSyncService::class)->sync('manual');
                        Notification::make()->title($log->status === 'success' ? 'Sincronização concluída' : 'Falha na sincronização')->body($log->message)->status($log->status === 'success' ? 'success' : 'danger')->send();
                    }),
                Tables\Actions\Action::make('queue')->label('Enfileirar')->icon('heroicon-o-queue-list')->color('gray')
                    ->action(function () {
                        SyncWebmotorsInventory::dispatch('manual');
                        Notification::make()->title('Sincronização enfileirada')->success()->send();
                    }),
            ])
            ->actions([Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSyncLogs::route('/')];
    }
}
