<?php

namespace App\Filament\Resources;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers\EventsRelationManager;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'lead';

    protected static ?string $pluralModelLabel = 'leads';

    public static function getNavigationBadge(): ?string
    {
        $n = Lead::where('status', LeadStatus::Novo)->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(3)->schema([
                Forms\Components\TextInput::make('nome')->required()->maxLength(120),
                Forms\Components\TextInput::make('fone')->label('Telefone / WhatsApp')->tel()->maxLength(30),
                Forms\Components\TextInput::make('email')->email()->maxLength(160),
                Forms\Components\Select::make('tipo')->options(LeadType::class)->required()->native(false),
                Forms\Components\Select::make('status')->options(LeadStatus::class)->required()->native(false),
                Forms\Components\Select::make('assigned_to')->label('Responsável')->options(fn () => User::where('is_active', true)->pluck('name', 'id'))->searchable()->native(false),
                Forms\Components\Select::make('veiculo_id')->label('Veículo')->relationship('vehicle', 'modelo')->getOptionLabelFromRecordUsing(fn ($r) => $r->titulo_completo)->searchable(['marca', 'modelo'])->preload()->native(false),
                Forms\Components\TextInput::make('origem')->maxLength(60),
                Forms\Components\Textarea::make('mensagem')->rows(4)->columnSpanFull(),
                Forms\Components\KeyValue::make('meta')->label('Dados extras')->columnSpanFull()->keyLabel('Campo')->valueLabel('Valor')->reorderable(false),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->columns(3)->schema([
                Infolists\Components\TextEntry::make('nome')->weight('bold')->size('lg'),
                Infolists\Components\TextEntry::make('fone')->label('Telefone')->url(fn (Lead $r) => $r->whatsapp_url)->openUrlInNewTab()->icon('heroicon-o-chat-bubble-oval-left')->color('success'),
                Infolists\Components\TextEntry::make('email')->placeholder('—'),
                Infolists\Components\TextEntry::make('tipo')->badge(),
                Infolists\Components\TextEntry::make('status')->badge(),
                Infolists\Components\TextEntry::make('assignee.name')->label('Responsável')->placeholder('Sem responsável'),
                Infolists\Components\TextEntry::make('vehicle.titulo_completo')->label('Veículo')->placeholder('—')->url(fn (Lead $r) => $r->vehicle ? VehicleResource::getUrl('edit', ['record' => $r->vehicle]) : null),
                Infolists\Components\TextEntry::make('origem')->placeholder('—'),
                Infolists\Components\TextEntry::make('created_at')->label('Recebido')->dateTime('d/m/Y H:i'),
                Infolists\Components\TextEntry::make('mensagem')->columnSpanFull()->placeholder('—'),
                Infolists\Components\KeyValueEntry::make('meta')->label('Dados extras')->columnSpanFull()->visible(fn (Lead $r) => ! empty($r->meta)),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->weight('bold')->description(fn (Lead $r) => $r->fone),
                Tables\Columns\TextColumn::make('tipo')->badge()->sortable(),
                Tables\Columns\TextColumn::make('vehicle.titulo')->label('Veículo')->placeholder('—')->limit(28)->toggleable(),
                Tables\Columns\SelectColumn::make('status')->options(LeadStatus::class)->selectablePlaceholder(false)->sortable()
                    ->afterStateUpdated(fn () => Notification::make()->title('Status atualizado')->success()->send()),
                Tables\Columns\TextColumn::make('assignee.name')->label('Responsável')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('last_contact_at')->label('Último contato')->since()->placeholder('nunca')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Recebido')->since()->sortable()->description(fn (Lead $r) => $r->created_at->format('d/m H:i')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(LeadStatus::class)->multiple()->default([LeadStatus::Novo->value, LeadStatus::EmContato->value, LeadStatus::Negociando->value]),
                Tables\Filters\SelectFilter::make('tipo')->options(LeadType::class)->multiple(),
                Tables\Filters\SelectFilter::make('assigned_to')->label('Responsável')->options(fn () => User::pluck('name', 'id')),
                Tables\Filters\Filter::make('hoje')->label('Recebidos hoje')->query(fn (Builder $q) => $q->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')->label('WhatsApp')->icon('heroicon-o-chat-bubble-oval-left')->color('success')
                    ->url(fn (Lead $r) => $r->whatsapp_url)->openUrlInNewTab()->visible(fn (Lead $r) => (bool) $r->whatsapp_url)
                    ->after(fn (Lead $r) => $r->registerContact('Abriu conversa no WhatsApp', tipo: 'whatsapp')),
                static::contactAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('atribuir')->label('Atribuir responsável')->icon('heroicon-o-user')
                        ->form([Forms\Components\Select::make('assigned_to')->label('Responsável')->options(fn () => User::where('is_active', true)->pluck('name', 'id'))->required()])
                        ->action(fn ($records, array $data) => $records->each(fn (Lead $l) => tap($l)->update(['assigned_to' => $data['assigned_to']])->events()->create(['tipo' => 'atribuido', 'para' => User::find($data['assigned_to'])?->name, 'user_id' => auth()->id()])))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('status')->label('Alterar status')->icon('heroicon-o-arrow-path')
                        ->form([Forms\Components\Select::make('status')->options(LeadStatus::class)->required()])
                        ->action(fn ($records, array $data) => $records->each->update(['status' => $data['status']]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function contactAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('contato')->label('Registrar contato')->icon('heroicon-o-phone')->color('gray')
            ->form([
                Forms\Components\Textarea::make('nota')->label('O que foi conversado?')->required()->rows(3),
                Forms\Components\Select::make('status')->label('Novo status (opcional)')->options(LeadStatus::class)->native(false),
            ])
            ->action(function (Lead $record, array $data) {
                $record->registerContact($data['nota']);
                if (! empty($data['status'])) {
                    $record->update(['status' => $data['status']]);
                }
                Notification::make()->title('Contato registrado')->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [EventsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['vehicle', 'assignee']);
    }
}
