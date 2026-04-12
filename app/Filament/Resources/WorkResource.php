<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkResource\Pages\CreateWork;
use App\Filament\Resources\WorkResource\Pages\EditWork;
use App\Filament\Resources\WorkResource\Pages\ListWorks;
use App\Models\CounterpartyUser;
use App\Models\Work;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;
use UnitEnum;

class WorkResource extends Resource
{
    protected static ?string $model = Work::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Работы';

    protected static ?string $modelLabel = 'Работа';

    protected static ?string $pluralModelLabel = 'Работы';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?bool $hasTableCache = null;

    protected static array $hasColumnCache = [];

    public static function form(Schema $schema): Schema
    {
        $components = [];

        if (static::hasColumn('date')) {
            $components[] = DatePicker::make('date')
                ->label('Дата')
                ->required();
        }

        if (static::hasColumn('counterparty_name')) {
            $components[] = TextInput::make('counterparty_name')
                ->label('Контрагент (имя из работ)')
                ->required()
                ->maxLength(255);
        }

        if (static::hasColumn('note')) {
            $components[] = TextInput::make('note')
                ->label('Примечание')
                ->maxLength(255);
        }

        if (static::hasColumn('structure')) {
            $components[] = TextInput::make('structure')
                ->label('Структура')
                ->maxLength(255);
        }

        if (static::hasColumn('operation')) {
            $components[] = TextInput::make('operation')
                ->label('Операция')
                ->maxLength(255);
        }

        if (static::hasColumn('object_count')) {
            $components[] = TextInput::make('object_count')
                ->label('Количество')
                ->maxLength(50);
        }

        if (static::hasColumn('revenue')) {
            $components[] = TextInput::make('revenue')
                ->label('Сумма')
                ->numeric()
                ->inputMode('decimal');
        }

        if (static::hasColumn('sheet_row_hash')) {
            $components[] = TextInput::make('sheet_row_hash')
                ->label('Хеш строки')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true);
        }

        if (static::hasColumn('invoice_id') && static::hasInvoicesTable()) {
            $components[] = Select::make('invoice_id')
                ->label('Счёт')
                ->relationship(
                    name: 'invoice',
                    titleAttribute: 'invoice_number',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->orderByDesc('issued_at'),
                )
                ->searchable()
                ->preload()
                ->nullable();
        }

        return $schema->components($components);
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

        if (static::hasColumn('date')) {
            $columns[] = TextColumn::make('date')
                ->label('Дата')
                ->date('d.m.Y')
                ->sortable();
        }

        if (! $isCounterparty && static::hasColumn('counterparty_name')) {
            $columns[] = TextColumn::make('counterparty_name')
                ->label('Контрагент')
                ->searchable()
                ->sortable();
        }

        if (static::hasColumn('operation')) {
            $operationColumn = TextColumn::make('operation')
                ->label('Операция')
                ->searchable()
                ->sortable();

            if (! $isCounterparty) {
                $operationColumn->toggleable();
            }

            $columns[] = $operationColumn;
        }

        if (static::hasColumn('object_count')) {
            $columns[] = TextColumn::make('object_count')
                ->label('Кол-во')
                ->sortable();
        }

        if (static::hasColumn('revenue')) {
            $columns[] = TextColumn::make('revenue')
                ->label('Сумма')
                ->numeric(decimalPlaces: 2)
                ->sortable();
        }

        if (static::hasColumn('invoice_id') && static::hasInvoicesTable()) {
            $invoiceColumn = TextColumn::make('invoice.invoice_number')
                ->label('Счёт')
                ->searchable()
                ->sortable();

            if (! $isCounterparty) {
                $invoiceColumn->toggleable();
            }

            $columns[] = $invoiceColumn;
        }

        if (! $isCounterparty && static::hasColumn('sheet_row_hash')) {
            $sheetHashColumn = TextColumn::make('sheet_row_hash')
                ->label('Хеш строки');

            $sheetHashColumn->toggleable(isToggledHiddenByDefault: true);

            $columns[] = $sheetHashColumn;
        }

        if (static::hasColumn('created_at')) {
            $createdAtColumn = TextColumn::make('created_at')
                ->label('Создано')
                ->dateTime('d.m.Y H:i')
                ->sortable();

            if (! $isCounterparty) {
                $createdAtColumn->toggleable();
            }

            $columns[] = $createdAtColumn;
        }

        $filters = [];

        if (static::hasColumn('invoice_id')) {
            $filters[] = TernaryFilter::make('invoice_id')
                ->label('Привязка к счёту')
                ->nullable()
                ->trueLabel('Есть счёт')
                ->falseLabel('Без счёта');
        }

        $recordActions = [];
        $toolbarActions = [];

        if (! $isCounterparty) {
            $recordActions = [
                EditAction::make(),
                DeleteAction::make(),
            ];

            $toolbarActions = [
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ];
        }

        return $table
            ->defaultSort(static::hasColumn('date') ? 'date' : (static::hasColumn('id') ? 'id' : 'counterparty_name'), 'desc')
            ->columns($columns)
            ->filters($filters)
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorks::route('/'),
            'create' => CreateWork::route('/create'),
            'edit' => EditWork::route('/{record}/edit'),
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

        $counterpartyId = (int) $counterpartyUser->counterparty_id;
        if ($counterpartyId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $hasInvoiceColumn = static::hasColumn('invoice_id');
        $hasInvoiceScope = $hasInvoiceColumn && static::hasInvoicesTable();
        $counterpartyNames = static::resolveCounterpartyNames($counterpartyUser);
        $hasNameScope = static::hasColumn('counterparty_name') && $counterpartyNames !== [];

        if (! $hasInvoiceScope && ! $hasNameScope) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scopeQuery) use ($hasInvoiceScope, $hasNameScope, $counterpartyId, $hasInvoiceColumn, $counterpartyNames): void {
            if ($hasInvoiceScope) {
                $scopeQuery->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery->where('counterparty_id', $counterpartyId));
            }

            if ($hasNameScope) {
                $nameScope = function (Builder $nameQuery) use ($hasInvoiceColumn, $counterpartyNames): void {
                    if ($hasInvoiceColumn) {
                        $nameQuery->whereNull('invoice_id');
                    }

                    $nameQuery->whereIn('counterparty_name', $counterpartyNames);
                };

                if ($hasInvoiceScope) {
                    $scopeQuery->orWhere($nameScope);
                } else {
                    $scopeQuery->where($nameScope);
                }
            }
        });
    }

    public static function canCreate(): bool
    {
        return ! static::isCounterpartyAuthenticated() && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return ! static::isCounterpartyAuthenticated() && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isCounterpartyAuthenticated() && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return ! static::isCounterpartyAuthenticated() && parent::canDeleteAny();
    }

    protected static function hasTable(): bool
    {
        if (static::$hasTableCache !== null) {
            return static::$hasTableCache;
        }

        try {
            static::$hasTableCache = SchemaFacade::hasTable('works');
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
            static::$hasColumnCache[$column] = SchemaFacade::hasColumn('works', $column);
        } catch (Throwable) {
            static::$hasColumnCache[$column] = false;
        }

        return static::$hasColumnCache[$column];
    }

    protected static function hasInvoicesTable(): bool
    {
        try {
            return SchemaFacade::hasTable('invoices');
        } catch (Throwable) {
            return false;
        }
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

    /**
     * @return array<int, string>
     */
    protected static function resolveCounterpartyNames(CounterpartyUser $counterpartyUser): array
    {
        $names = [
            $counterpartyUser->counterparty?->short_name,
            $counterpartyUser->counterparty?->name,
        ];

        $normalized = [];

        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $normalized[$name] = true;
        }

        return array_keys($normalized);
    }
}
