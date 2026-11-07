<?php

namespace App\Filament\Resources;

use App\Enums\Cambio;
use App\Enums\Carroceria;
use App\Enums\Combustivel;
use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers\PhotosRelationManager;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Loja';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'veículo';

    protected static ?string $pluralModelLabel = 'veículos';

    protected static ?string $recordTitleAttribute = 'modelo';

    /** Campos que a sincronização Webmotors pode sobrescrever (e que podem ser travados) */
    public const SYNC_FIELDS = ['marca', 'modelo', 'versao', 'ano_fab', 'ano_mod', 'km', 'preco', 'cambio', 'combustivel', 'carroceria', 'cor', 'motor', 'portas', 'placa_final', 'opcionais', 'descricao'];

    public static function getNavigationBadge(): ?string
    {
        return (string) Vehicle::available()->count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    /** Esquema reutilizado pela página "Importar por link" */
    public static function formSchema(bool $withStatus = true): array
    {
        return [
            Forms\Components\Section::make('Identificação')->columns(4)->schema([
                Forms\Components\TextInput::make('marca')->required()->maxLength(80)->datalist(fn () => Vehicle::query()->select('marca')->distinct()->orderBy('marca')->pluck('marca')->all()),
                Forms\Components\TextInput::make('modelo')->required()->maxLength(120),
                Forms\Components\TextInput::make('versao')->label('Versão')->maxLength(180)->columnSpan(2),
                Forms\Components\TextInput::make('ano_fab')->label('Ano fabricação')->numeric()->required()->minValue(1950)->maxValue((int) date('Y') + 1),
                Forms\Components\TextInput::make('ano_mod')->label('Ano modelo')->numeric()->required()->minValue(1950)->maxValue((int) date('Y') + 2),
                Forms\Components\TextInput::make('km')->numeric()->required()->default(0)->suffix('km'),
                Forms\Components\TextInput::make('placa_final')->label('Final da placa')->maxLength(1),
            ]),
            Forms\Components\Section::make('Preço e status')->columns(4)->schema([
                Forms\Components\TextInput::make('preco')->label('Preço')->numeric()->required()->prefix('R$')->step('0.01'),
                Forms\Components\TextInput::make('preco_anterior')->label('Preço anterior (oferta)')->numeric()->prefix('R$')->step('0.01')->helperText('Preencha para exibir o selo "Oferta".'),
                Forms\Components\Select::make('status')->options(VehicleStatus::class)->default(VehicleStatus::Disponivel)->required()->native(false)->visible($withStatus),
                Forms\Components\Toggle::make('destaque')->label('Destaque na home')->inline(false)->visible($withStatus),
            ]),
            Forms\Components\Section::make('Características')->columns(4)->schema([
                Forms\Components\Select::make('cambio')->label('Câmbio')->options(Cambio::class)->native(false),
                Forms\Components\Select::make('combustivel')->label('Combustível')->options(Combustivel::class)->native(false),
                Forms\Components\Select::make('carroceria')->options(Carroceria::class)->native(false),
                Forms\Components\TextInput::make('cor')->maxLength(40)->datalist(['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Verde', 'Marrom', 'Bege']),
                Forms\Components\TextInput::make('motor')->maxLength(40)->placeholder('1.0 Turbo'),
                Forms\Components\Select::make('portas')->options([2 => '2', 3 => '3', 4 => '4', 5 => '5'])->native(false),
                Forms\Components\TagsInput::make('opcionais')->label('Opcionais')->placeholder('Digite e pressione Enter')->columnSpan(2)
                    ->suggestions(['Ar-condicionado', 'Direção elétrica', 'Vidros elétricos', 'Travas elétricas', 'Airbags', 'Freios ABS', 'Central multimídia', 'Câmera de ré', 'Sensor de estacionamento', 'Bancos em couro', 'Teto solar', 'Rodas de liga leve', 'Controle de estabilidade', 'Piloto automático', 'Faróis de LED']),
            ]),
            Forms\Components\Section::make('Descrição')->schema([
                Forms\Components\Textarea::make('descricao')->label('Descrição do anúncio')->rows(5)->maxLength(5000),
                Forms\Components\TextInput::make('slug')->maxLength(220)->helperText('Deixe em branco para gerar automaticamente.')->visible($withStatus)->unique(ignoreRecord: true),
            ])->collapsible(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')->label('')->getStateUsing(fn (Vehicle $r) => $r->cover_url)->width(76)->height(48)->extraImgAttributes(['class' => 'rounded object-cover']),
                Tables\Columns\TextColumn::make('titulo')->label('Veículo')->searchable(['marca', 'modelo', 'versao'])->sortable(['marca', 'modelo'])
                    ->description(fn (Vehicle $r) => trim(($r->versao ?? '')." · {$r->ano_fab}/{$r->ano_mod}"))->weight('bold'),
                Tables\Columns\TextColumn::make('preco')->money('BRL')->sortable(),
                Tables\Columns\TextColumn::make('km')->numeric(locale: 'pt_BR')->suffix(' km')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('cambio')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\IconColumn::make('destaque')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('leads_count')->counts('leads')->label('Leads')->sortable()->toggleable(),
                Tables\Columns\IconColumn::make('webmotorsRef.external_id')->label('WM')->boolean()->trueIcon('heroicon-o-link')->falseIcon('heroicon-o-minus')->tooltip(fn (Vehicle $r) => $r->webmotorsRef ? 'Webmotors #'.$r->webmotorsRef->external_id : 'Cadastro manual')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Cadastro')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(VehicleStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('marca')->options(fn () => Vehicle::query()->select('marca')->distinct()->orderBy('marca')->pluck('marca', 'marca')->all())->multiple(),
                Tables\Filters\SelectFilter::make('carroceria')->options(Carroceria::class)->multiple(),
                Tables\Filters\TernaryFilter::make('destaque'),
                Tables\Filters\TernaryFilter::make('webmotors')->label('Origem Webmotors')->queries(
                    true: fn (Builder $q) => $q->whereHas('webmotorsRef'),
                    false: fn (Builder $q) => $q->whereDoesntHave('webmotorsRef'),
                ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')->label('Ver no site')->icon('heroicon-o-eye')->url(fn (Vehicle $r) => $r->url)->openUrlInNewTab()->color('gray'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('reservar')->icon('heroicon-o-bookmark')->visible(fn (Vehicle $r) => $r->status === VehicleStatus::Disponivel)->action(fn (Vehicle $r) => $r->update(['status' => VehicleStatus::Reservado])),
                    Tables\Actions\Action::make('vender')->label('Marcar vendido')->icon('heroicon-o-check-badge')->color('success')->requiresConfirmation()->visible(fn (Vehicle $r) => $r->status !== VehicleStatus::Vendido)->action(fn (Vehicle $r) => $r->update(['status' => VehicleStatus::Vendido])),
                    Tables\Actions\Action::make('disponibilizar')->label('Voltar a disponível')->icon('heroicon-o-arrow-uturn-left')->visible(fn (Vehicle $r) => $r->status !== VehicleStatus::Disponivel)->action(fn (Vehicle $r) => $r->update(['status' => VehicleStatus::Disponivel])),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('destacar')->label('Marcar destaque')->icon('heroicon-o-star')->action(fn ($records) => $records->each->update(['destaque' => true]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('remover_destaque')->label('Remover destaque')->icon('heroicon-o-star')->color('gray')->action(fn ($records) => $records->each->update(['destaque' => false]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [PhotosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class])->with(['cover', 'webmotorsRef']);
    }
}
