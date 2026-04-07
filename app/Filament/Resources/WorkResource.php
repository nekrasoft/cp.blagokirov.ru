<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkResource\Pages\CreateWork;
use App\Filament\Resources\WorkResource\Pages\EditWork;
use App\Filament\Resources\WorkResource\Pages\ListWorks;
use App\Models\Work;
use BackedEnum;
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
                ->label('Выручка')
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

        if (static::hasColumn('counterparty_name')) {
            $columns[] = TextColumn::make('counterparty_name')
                ->label('Контрагент')
                ->searchable()
                ->sortable();
        }

        if (static::hasColumn('operation')) {
            $columns[] = TextColumn::make('operation')
                ->label('Операция')
                ->searchable()
                ->sortable()
                ->toggleable();
        }

        if (static::hasColumn('object_count')) {
            $columns[] = TextColumn::make('object_count')
                ->label('Кол-во')
                ->sortable();
        }

        if (static::hasColumn('revenue')) {
            $columns[] = TextColumn::make('revenue')
                ->label('Выручка')
                ->numeric(decimalPlaces: 2)
                ->sortable();
        }

        if (static::hasColumn('invoice_id') && static::hasInvoicesTable()) {
            $columns[] = TextColumn::make('invoice.invoice_number')
                ->label('Счёт')
                ->searchable()
                ->sortable()
                ->toggleable();
        }

        if (static::hasColumn('sheet_row_hash')) {
            $columns[] = TextColumn::make('sheet_row_hash')
                ->label('Хеш строки')
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('created_at')) {
            $columns[] = TextColumn::make('created_at')
                ->label('Создано')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable();
        }

        $filters = [];

        if (static::hasColumn('invoice_id')) {
            $filters[] = TernaryFilter::make('invoice_id')
                ->label('Привязка к счёту')
                ->nullable()
                ->trueLabel('Есть счёт')
                ->falseLabel('Без счёта');
        }

        return $table
            ->defaultSort(static::hasColumn('date') ? 'date' : (static::hasColumn('id') ? 'id' : 'counterparty_name'), 'desc')
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
}

