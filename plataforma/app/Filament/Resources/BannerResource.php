<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Loja';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'banner';

    protected static ?string $pluralModelLabel = 'banners';

    public const POSICOES = ['home_hero' => 'Home — hero (topo)', 'home_faixa' => 'Home — faixa de chamadas', 'estoque_topo' => 'Estoque — topo'];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')->label('Título')->required()->maxLength(120)->helperText('No hero, o texto após a vírgula é destacado em azul.'),
                Forms\Components\TextInput::make('subtitulo')->label('Subtítulo')->maxLength(200),
                Forms\Components\Select::make('posicao')->label('Posição')->options(self::POSICOES)->required()->native(false),
                Forms\Components\TextInput::make('ordem')->numeric()->default(0),
                Forms\Components\TextInput::make('link')->maxLength(255)->placeholder('/estoque ou https://…'),
                Forms\Components\TextInput::make('cta')->label('Texto do botão')->maxLength(60),
                Forms\Components\FileUpload::make('imagem')->image()->disk('public')->directory('banners')->imageEditor()->maxSize(8192)->columnSpanFull(),
                Forms\Components\Toggle::make('ativo')->default(true),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DateTimePicker::make('inicio_em')->label('Exibir a partir de')->native(false),
                    Forms\Components\DateTimePicker::make('fim_em')->label('Exibir até')->native(false),
                ])->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordem')->defaultSort('ordem')
            ->columns([
                Tables\Columns\ImageColumn::make('imagem_url')->label('')->getStateUsing(fn (Banner $r) => $r->imagem_url)->width(96)->height(54),
                Tables\Columns\TextColumn::make('titulo')->label('Título')->searchable()->weight('bold')->description(fn (Banner $r) => $r->subtitulo),
                Tables\Columns\TextColumn::make('posicao')->label('Posição')->formatStateUsing(fn ($state) => self::POSICOES[$state] ?? $state)->badge(),
                Tables\Columns\ToggleColumn::make('ativo'),
                Tables\Columns\TextColumn::make('fim_em')->label('Até')->date('d/m/Y')->placeholder('—'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('posicao')->options(self::POSICOES)])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
