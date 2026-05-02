<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BunkerResource\Pages\CreateBunker;
use App\Filament\Resources\BunkerResource\Pages\EditBunker;
use App\Filament\Resources\BunkerResource\Pages\ListBunkers;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Models\Bunker;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class BunkerResource extends Resource
{
    use PreservesNavigationSearch;

    protected static ?string $model = Bunker::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Бункеры';

    protected static ?string $modelLabel = 'Бункер';

    protected static ?string $pluralModelLabel = 'Бункеры';

    protected static string|UnitEnum|null $navigationGroup = 'Карта бункеров';

    protected static ?int $navigationSort = 20;

    protected static ?bool $hasTableCache = null;

    protected static array $hasColumnCache = [];

    protected static array $hasCounterpartyColumnCache = [];

    public static function form(Schema $schema): Schema
    {
        $components = [];

        if (static::hasColumn('id')) {
            $components[] = TextInput::make('id')
                ->label('ID')
                ->default(fn (): string => (string) Str::uuid())
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation): bool => $operation === 'edit');
        }

        if (static::hasColumn('number')) {
            $components[] = TextInput::make('number')
                ->label('Номер')
                ->numeric()
                ->rule('integer')
                ->default(0)
                ->required();
        }

        if (static::hasColumn('volume')) {
            $components[] = TextInput::make('volume')
                ->label('Объём, м³')
                ->numeric()
                ->inputMode('decimal')
                ->default('8.00')
                ->required();
        }

        if (static::hasColumn('address')) {
            $components[] = TextInput::make('address')
                ->label('Адрес')
                ->required()
                ->maxLength(255);
        }

        if (static::hasColumn('district')) {
            $components[] = TextInput::make('district')
                ->label('Район')
                ->required()
                ->maxLength(255);
        }

        if (static::hasColumn('contractor')) {
            $components[] = TextInput::make('contractor')
                ->label('Подрядчик')
                ->required()
                ->maxLength(255);
        }

        if (static::hasColumn('counterparty_id')) {
            if (static::hasCounterpartiesTable()) {
                $components[] = Select::make('counterparty_id')
                    ->label('Контрагент')
                    ->relationship(
                        name: 'counterparty',
                        titleAttribute: static::counterpartyTitleAttribute(),
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy(static::counterpartyTitleAttribute()),
                    )
                    ->searchable()
                    ->preload()
                    ->nullable();
            } else {
                $components[] = TextInput::make('counterparty_id')
                    ->label('ID контрагента')
                    ->numeric()
                    ->rule('integer')
                    ->nullable();
            }
        }

        if (static::hasColumn('waste_type')) {
            $components[] = TextInput::make('waste_type')
                ->label('Тип отходов')
                ->default('КГО')
                ->required()
                ->maxLength(100);
        }

        if (static::hasColumn('last_pickup_date')) {
            $components[] = DatePicker::make('last_pickup_date')
                ->label('Последний вывоз')
                ->dehydrateStateUsing(fn ($state): string => filled($state) ? substr((string) $state, 0, 10) : '');
        }

        if (static::hasColumn('fill_level')) {
            $components[] = TextInput::make('fill_level')
                ->label('Заполненность, %')
                ->numeric()
                ->rule('integer')
                ->minValue(0)
                ->maxValue(100)
                ->default(0)
                ->required();
        }

        if (static::hasColumn('last_filled_at')) {
            $components[] = DateTimePicker::make('last_filled_at')
                ->label('Последнее заполнение')
                ->seconds(false);
        }

        if (static::hasColumn('last_filled_by')) {
            $components[] = TextInput::make('last_filled_by')
                ->label('Кто отметил заполнение')
                ->maxLength(255);
        }

        if (static::hasColumn('contact_phone')) {
            $components[] = TextInput::make('contact_phone')
                ->label('Телефон')
                ->tel()
                ->default('')
                ->maxLength(50)
                ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state));
        }

        if (static::hasColumn('lat')) {
            $components[] = TextInput::make('lat')
                ->label('Широта')
                ->numeric()
                ->inputMode('decimal')
                ->default('0.00000000000000')
                ->required();
        }

        if (static::hasColumn('lng')) {
            $components[] = TextInput::make('lng')
                ->label('Долгота')
                ->numeric()
                ->inputMode('decimal')
                ->default('0.00000000000000')
                ->required();
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $columns = [];

        if (static::hasColumn('number')) {
            $columns[] = TextColumn::make('number')
                ->label('№')
                ->searchable()
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

        if (static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $columns[] = TextColumn::make('counterparty.' . static::counterpartyTitleAttribute())
                ->label('Контрагент')
                ->searchable()
                ->sortable()
                ->color(fn (?string $state): string => filled($state) ? 'primary' : 'gray')
                ->url(fn (?string $state): ?string => static::counterpartySearchUrl($state))
                ->toggleable();
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

        if (static::hasColumn('last_pickup_date')) {
            $columns[] = TextColumn::make('last_pickup_date')
                ->label('Последний вывоз')
                ->date('d.m.Y')
                ->sortable()
                ->toggleable();
        }

        if (static::hasColumn('contact_phone')) {
            $columns[] = TextColumn::make('contact_phone')
                ->label('Телефон')
                ->searchable()
                ->copyable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('lat')) {
            $columns[] = TextColumn::make('lat')
                ->label('Широта')
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('lng')) {
            $columns[] = TextColumn::make('lng')
                ->label('Долгота')
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('last_filled_at')) {
            $columns[] = TextColumn::make('last_filled_at')
                ->label('Заполнен')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('updated_at')) {
            $columns[] = TextColumn::make('updated_at')
                ->label('Обновлён')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        $filters = [];

        if (static::hasColumn('district')) {
            $filters[] = SelectFilter::make('district')
                ->label('Район')
                ->options(fn (): array => static::distinctOptions('district'));
        }

        if (static::hasColumn('waste_type')) {
            $filters[] = SelectFilter::make('waste_type')
                ->label('Тип отходов')
                ->options(fn (): array => static::distinctOptions('waste_type'));
        }

        if (static::hasColumn('contractor')) {
            $filters[] = SelectFilter::make('contractor')
                ->label('Подрядчик')
                ->options(fn (): array => static::distinctOptions('contractor'));
        }

        if (static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $filters[] = SelectFilter::make('counterparty_id')
                ->label('Контрагент')
                ->relationship('counterparty', static::counterpartyTitleAttribute());
        }

        return $table
            ->defaultSort(static::hasColumn('number') ? 'number' : 'id')
            ->columns($columns)
            ->filters($filters)
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
            'index' => ListBunkers::route('/'),
            'create' => CreateBunker::route('/create'),
            'edit' => EditBunker::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasTable();
    }

    public static function canAccess(): bool
    {
        return static::hasTable()
            && ! static::isCounterpartyAuthenticated()
            && parent::canAccess();
    }

    protected static function hasTable(): bool
    {
        if (static::$hasTableCache !== null) {
            return static::$hasTableCache;
        }

        try {
            static::$hasTableCache = SchemaFacade::hasTable('bunkers');
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
            static::$hasColumnCache[$column] = SchemaFacade::hasColumn('bunkers', $column);
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

    protected static function counterpartySearchUrl(?string $counterpartyName): ?string
    {
        $counterpartyName = trim((string) $counterpartyName);

        if ($counterpartyName === '') {
            return null;
        }

        return static::getUrl('index', [
            'search' => $counterpartyName,
        ]);
    }

    protected static function distinctOptions(string $column): array
    {
        if (! static::hasTable() || ! static::hasColumn($column)) {
            return [];
        }

        try {
            return Bunker::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->orderBy($column)
                ->pluck($column, $column)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected static function isCounterpartyAuthenticated(): bool
    {
        return Filament::auth()->user() instanceof CounterpartyUser;
    }
}
