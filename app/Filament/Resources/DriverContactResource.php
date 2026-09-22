<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\AuthorizesAdminWrites;
use App\Filament\Resources\DriverContactResource\Pages\CreateDriverContact;
use App\Filament\Resources\DriverContactResource\Pages\EditDriverContact;
use App\Filament\Resources\DriverContactResource\Pages\ListDriverContacts;
use App\Models\DriverContact;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;
use UnitEnum;

class DriverContactResource extends Resource
{
    use AuthorizesAdminWrites;

    protected static ?string $model = DriverContact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Телефоны водителей';

    protected static ?string $modelLabel = 'Контакт водителя';

    protected static ?string $pluralModelLabel = 'Телефоны водителей';

    protected static string|UnitEnum|null $navigationGroup = 'Карта бункеров';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Имя')->required()->maxLength(255),
            TextInput::make('phone')->label('Телефон')->tel()->required()->maxLength(32),
            Select::make('source')
                ->label('Платформа бота')
                ->options(['telegram' => 'Telegram', 'max' => 'MAX'])
                ->nullable(),
            TextInput::make('source_user_id')->label('ID пользователя бота')->maxLength(64),
            Toggle::make('is_active')->label('Показывать на карте')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Имя')->searchable()->sortable(),
                TextColumn::make('phone')->label('Телефон')->url(fn (string $state): string => 'tel:'.$state),
                TextColumn::make('source')->label('Платформа')->badge(),
                TextColumn::make('source_user_id')->label('ID пользователя')->toggleable(),
                IconColumn::make('is_active')->label('На карте')->boolean(),
            ])
            ->recordActions(static::hasAdminWriteAccess() ? [EditAction::make(), DeleteAction::make()] : [])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDriverContacts::route('/'),
            'create' => CreateDriverContact::route('/create'),
            'edit' => EditDriverContact::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        try {
            return Filament::getCurrentPanel()?->getId() === 'admin'
                && SchemaFacade::hasTable('driver_contacts');
        } catch (Throwable) {
            return false;
        }
    }

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin' && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::hasAdminWriteAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::hasAdminWriteAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::hasAdminWriteAccess();
    }
}
