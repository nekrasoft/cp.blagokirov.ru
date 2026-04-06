<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterpartyUserResource\Pages\CreateCounterpartyUser;
use App\Filament\Resources\CounterpartyUserResource\Pages\EditCounterpartyUser;
use App\Filament\Resources\CounterpartyUserResource\Pages\ListCounterpartyUsers;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class CounterpartyUserResource extends Resource
{
    protected static ?string $model = CounterpartyUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Пользователи контрагентов';

    protected static ?string $modelLabel = 'Пользователь контрагента';

    protected static ?string $pluralModelLabel = 'Пользователи контрагентов';

    protected static string|UnitEnum|null $navigationGroup = 'Карта бункеров';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('login')
                    ->label('Логин')
                    ->required()
                    ->maxLength(191)
                    ->unique(ignoreRecord: true),
                TextInput::make('password_hash')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(6)
                    ->maxLength(255)
                    ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                        $component->state('');
                    })
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (?string $state): string => Hash::make((string) $state)),
                Select::make('counterparty_id')
                    ->label('Контрагент')
                    ->relationship(
                        name: 'counterparty',
                        titleAttribute: 'short_name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('short_name'),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('login')
            ->columns([
                TextColumn::make('login')
                    ->label('Логин')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('counterparty.short_name')
                    ->label('Контрагент')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('counterparty.name')
                    ->label('Полное наименование')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCounterpartyUsers::route('/'),
            'create' => CreateCounterpartyUser::route('/create'),
            'edit' => EditCounterpartyUser::route('/{record}/edit'),
        ];
    }
}
