<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'usuários';

    public const ROLES = ['admin' => 'Administrador', 'gerente' => 'Gerente', 'vendedor' => 'Vendedor'];

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\TextInput::make('phone')->label('Telefone')->tel()->maxLength(30),
                Forms\Components\Select::make('role')->label('Perfil')->options(self::ROLES)->required()->default('vendedor')->native(false),
                Forms\Components\TextInput::make('password')->label('Senha')->password()->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)->helperText('Deixe em branco para manter a senha atual.'),
                Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->weight('bold')->description(fn (User $r) => $r->email),
                Tables\Columns\TextColumn::make('role')->label('Perfil')->formatStateUsing(fn ($state) => self::ROLES[$state] ?? $state)->badge()->color(fn ($state) => $state === 'admin' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('phone')->label('Telefone')->placeholder('—'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Ativo')->disabled(fn (User $r) => $r->id === auth()->id()),
                Tables\Columns\TextColumn::make('created_at')->label('Criado')->since(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->hidden(fn (User $r) => $r->id === auth()->id())]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
