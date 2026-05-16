<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\AuthorizesAdminWrites;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    use AuthorizesAdminWrites;
    use PreservesNavigationSearch;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Администраторы';

    protected static ?string $modelLabel = 'Администратор';

    protected static ?string $pluralModelLabel = 'Администраторы';

    protected static string|UnitEnum|null $navigationGroup = 'Панель';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('role')
                    ->label('Роль')
                    ->options(User::adminRoleOptions())
                    ->default(User::ROLE_ADMIN)
                    ->required()
                    ->disabled(fn (?User $record): bool => static::isAuthenticatedUser($record))
                    ->dehydrated(fn (?User $record): bool => ! static::isAuthenticatedUser($record)),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required()
                    ->disabled(fn (?User $record): bool => static::isAuthenticatedUser($record))
                    ->dehydrated(fn (?User $record): bool => ! static::isAuthenticatedUser($record)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => User::adminRoleOptions()[$state] ?? 'Неизвестная роль')
                    ->color(fn (?string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'success',
                        User::ROLE_READONLY_ADMIN => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Роль')
                    ->options(User::adminRoleOptions()),
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => static::canDelete($record)),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAdminWriteAccess();
    }

    public static function canAccess(): bool
    {
        return static::hasAdminWriteAccess()
            && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::hasAdminWriteAccess()
            && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return static::hasAdminWriteAccess()
            && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof User
            && ! static::isAuthenticatedUser($record)
            && static::hasAdminWriteAccess()
            && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function isAuthenticatedUser(?User $record): bool
    {
        if (! $record) {
            return false;
        }

        $user = Filament::auth()->user();

        return $user instanceof User
            && $record->is($user);
    }
}
