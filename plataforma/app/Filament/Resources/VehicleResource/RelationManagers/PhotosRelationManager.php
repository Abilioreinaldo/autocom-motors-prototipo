<?php

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use App\Models\VehiclePhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotos';

    protected static ?string $modelLabel = 'foto';

    protected static ?string $pluralModelLabel = 'fotos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')->label('Imagem')
                ->disk('public')->directory('veiculos')->visibility('public')
                ->image()->imageEditor()->maxSize(8192)
                ->imageResizeMode('cover')->imageResizeTargetWidth('1600')->imageResizeTargetHeight('1000')
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText('JPG/PNG/WebP até 8 MB. A imagem é redimensionada para 1600×1000.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('alt')->label('Texto alternativo')->maxLength(255),
            Forms\Components\TextInput::make('ordem')->numeric()->default(fn () => (int) $this->getOwnerRecord()->photos()->max('ordem') + 1),
            Forms\Components\Toggle::make('capa')->label('Usar como capa'),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('ordem')
            ->defaultSort('ordem')
            ->columns([
                Tables\Columns\ImageColumn::make('url')->label('Foto')->getStateUsing(fn (VehiclePhoto $r) => $r->url)->width(140)->height(88)->extraImgAttributes(['class' => 'rounded object-cover']),
                Tables\Columns\TextColumn::make('ordem')->sortable(),
                Tables\Columns\IconColumn::make('capa')->boolean(),
                Tables\Columns\TextColumn::make('path')->label('Origem')->formatStateUsing(fn (string $state) => str_starts_with($state, 'http') ? 'Webmotors / URL' : 'Upload')->badge()->color(fn (string $state) => str_starts_with($state, 'http') ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('alt')->limit(30)->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Enviar foto'),
                Tables\Actions\Action::make('por_url')->label('Adicionar por URL')->icon('heroicon-o-link')->color('gray')
                    ->form([Forms\Components\TextInput::make('url')->url()->required()->label('URL da imagem')])
                    ->action(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $owner->photos()->create(['path' => $data['url'], 'ordem' => (int) $owner->photos()->max('ordem') + 1, 'capa' => ! $owner->photos()->where('capa', true)->exists()]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('capa')->label('Definir capa')->icon('heroicon-o-star')->visible(fn (VehiclePhoto $r) => ! $r->capa)->action(fn (VehiclePhoto $r) => $r->update(['capa' => true])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}
