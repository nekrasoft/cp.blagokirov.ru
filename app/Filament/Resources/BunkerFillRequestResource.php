<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BunkerFillRequestResource\Pages\ListBunkerFillRequests;
use App\Models\BunkerFillRequest;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;
use UnitEnum;

class BunkerFillRequestResource extends Resource
{
    protected static ?string $model = BunkerFillRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'История заявок';

    protected static ?string $modelLabel = 'Заявка на заполнение';

    protected static ?string $pluralModelLabel = 'История заявок';

    protected static string|UnitEnum|null $navigationGroup = 'Карта бункеров';

    protected static ?int $navigationSort = 30;

    protected static ?bool $hasTableCache = null;

    protected static array $hasColumnCache = [];

    protected static array $hasCounterpartyColumnCache = [];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        $isCounterparty = static::isCounterpartyAuthenticated();
        $columns = [];

        if (static::hasColumn('id')) {
            $columns[] = TextColumn::make('id')
                ->label('ID')
                ->sortable();
        }

        if (static::hasColumn('bunker_id')) {
            $columns[] = TextColumn::make('bunker_id')
                ->label('ID бункера')
                ->searchable()
                ->sortable()
                ->copyable();
        }

        if (static::hasColumn('bunker_number')) {
            $columns[] = TextColumn::make('bunker_number')
                ->label('№ бункера')
                ->sortable();
        }

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $columns[] = TextColumn::make('counterparty.' . static::counterpartyTitleAttribute())
                ->label('Контрагент')
                ->searchable()
                ->sortable();
        }

        if (static::hasColumn('district')) {
            $columns[] = TextColumn::make('district')
                ->label('Район')
                ->searchable()
                ->sortable();
        }

        if (static::hasColumn('address')) {
            $columns[] = TextColumn::make('address')
                ->label('Адрес')
                ->searchable()
                ->wrap();
        }

        if (static::hasColumn('waste_type')) {
            $columns[] = TextColumn::make('waste_type')
                ->label('Тип отходов')
                ->badge()
                ->sortable();
        }

        if (static::hasColumn('fill_level')) {
            $columns[] = TextColumn::make('fill_level')
                ->label('Заполненность')
                ->formatStateUsing(fn ($state): string => (int) ($state ?? 0) . '%')
                ->badge()
                ->color(fn ($state): string => match (true) {
                    (int) $state >= 100 => 'danger',
                    (int) $state >= 70 => 'warning',
                    default => 'success',
                })
                ->sortable();
        }

        if (static::hasColumn('filled_by')) {
            $columns[] = TextColumn::make('filled_by')
                ->label('Кто создал')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('filled_at')) {
            $columns[] = TextColumn::make('filled_at')
                ->label('Дата заявки')
                ->dateTime('d.m.Y H:i')
                ->sortable();
        }

        if (! $isCounterparty && static::hasColumn('contractor')) {
            $columns[] = TextColumn::make('contractor')
                ->label('Подрядчик')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('created_at')) {
            $createdAtColumn = TextColumn::make('created_at')
                ->label('Создано')
                ->dateTime('d.m.Y H:i')
                ->sortable();

            if (! $isCounterparty) {
                $createdAtColumn->toggleable(isToggledHiddenByDefault: true);
            }

            $columns[] = $createdAtColumn;
        }

        $filters = [];

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $filters[] = SelectFilter::make('counterparty_id')
                ->label('Контрагент')
                ->relationship('counterparty', static::counterpartyTitleAttribute());
        }

        return $table
            ->defaultSort(static::hasColumn('filled_at') ? 'filled_at' : (static::hasColumn('id') ? 'id' : 'bunker_id'), 'desc')
            ->columns($columns)
            ->filters($filters)
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBunkerFillRequests::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasTable();
    }

    public static function canAccess(): bool
    {
        return static::hasTable() && parent::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $counterpartyUser = static::getAuthenticatedCounterpartyUser();

        if (! $counterpartyUser) {
            return $query;
        }

        if (! static::hasColumn('counterparty_id')) {
            return $query->whereRaw('1 = 0');
        }

        $counterpartyId = (int) $counterpartyUser->counterparty_id;

        if ($counterpartyId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('counterparty_id', $counterpartyId);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function hasTable(): bool
    {
        if (static::$hasTableCache !== null) {
            return static::$hasTableCache;
        }

        try {
            static::$hasTableCache = SchemaFacade::hasTable('bunker_fill_requests');
        } catch (Throwable) {
            static::$hasTableCache = false;
        }

        return static::$hasTableCache;
    }

    protected static function hasColumn(string $column): bool
    {
        if (array_key_exists($column, static::$hasColumnCache)) {
            return static::$hasColumnCache[$column];
        }

        try {
            static::$hasColumnCache[$column] = SchemaFacade::hasColumn('bunker_fill_requests', $column);
        } catch (Throwable) {
            static::$hasColumnCache[$column] = false;
        }

        return static::$hasColumnCache[$column];
    }

    protected static function hasCounterpartiesTable(): bool
    {
        try {
            return SchemaFacade::hasTable('counterparties');
        } catch (Throwable) {
            return false;
        }
    }

    protected static function counterpartyTitleAttribute(): string
    {
        if (array_key_exists('title_attribute', static::$hasCounterpartyColumnCache)) {
            return static::$hasCounterpartyColumnCache['title_attribute'] ? 'short_name' : 'name';
        }

        try {
            static::$hasCounterpartyColumnCache['title_attribute'] = SchemaFacade::hasColumn('counterparties', 'short_name');
        } catch (Throwable) {
            static::$hasCounterpartyColumnCache['title_attribute'] = false;
        }

        return static::$hasCounterpartyColumnCache['title_attribute'] ? 'short_name' : 'name';
    }

    protected static function isCounterpartyAuthenticated(): bool
    {
        return static::getAuthenticatedCounterpartyUser() !== null;
    }

    protected static function getAuthenticatedCounterpartyUser(): ?CounterpartyUser
    {
        $user = Filament::auth()->user();

        return $user instanceof CounterpartyUser ? $user : null;
    }
}
