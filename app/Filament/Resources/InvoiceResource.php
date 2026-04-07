<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Invoice;
use BackedEnum;
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
use Throwable;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Счета';

    protected static ?string $modelLabel = 'Счёт';

    protected static ?string $pluralModelLabel = 'Счета';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?bool $hasTableCache = null;

    protected static array $hasColumnCache = [];

    protected static array $hasCounterpartyColumnCache = [];

    public static function form(Schema $schema): Schema
    {
        $components = [];

        if (static::hasColumn('invoice_number')) {
            $components[] = TextInput::make('invoice_number')
                ->label('Номер счёта')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true);
        }

        if (static::hasColumn('tbank_invoice_id')) {
            $components[] = TextInput::make('tbank_invoice_id')
                ->label('ID счёта в Т-Банк')
                ->maxLength(100);
        }

        if (static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $components[] = Select::make('counterparty_id')
                ->label('Контрагент')
                ->relationship(
                    name: 'counterparty',
                    titleAttribute: static::counterpartyTitleAttribute(),
                    modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy(static::counterpartyTitleAttribute()),
                )
                ->searchable()
                ->preload()
                ->required();
        }

        if (static::hasColumn('issued_at')) {
            $components[] = DateTimePicker::make('issued_at')
                ->label('Дата выставления')
                ->required()
                ->seconds(false);
        }

        if (static::hasColumn('due_date')) {
            $components[] = DatePicker::make('due_date')
                ->label('Срок оплаты');
        }

        if (static::hasColumn('status')) {
            $components[] = TextInput::make('status')
                ->label('Статус')
                ->maxLength(50);
        }

        if (static::hasColumn('pdf_url')) {
            $components[] = TextInput::make('pdf_url')
                ->label('Ссылка на PDF')
                ->url()
                ->maxLength(500);
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

        if (static::hasColumn('invoice_number')) {
            $columns[] = TextColumn::make('invoice_number')
                ->label('Номер')
                ->searchable()
                ->sortable()
                ->copyable();
        }

        if (static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $columns[] = TextColumn::make('counterparty.' . static::counterpartyTitleAttribute())
                ->label('Контрагент')
                ->searchable()
                ->sortable();
        }

        if (static::hasColumn('issued_at')) {
            $columns[] = TextColumn::make('issued_at')
                ->label('Выставлен')
                ->dateTime('d.m.Y H:i')
                ->sortable();
        }

        if (static::hasColumn('due_date')) {
            $columns[] = TextColumn::make('due_date')
                ->label('Срок оплаты')
                ->date('d.m.Y')
                ->sortable();
        }

        if (static::hasColumn('status')) {
            $columns[] = TextColumn::make('status')
                ->label('Статус')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'issued', 'paid' => 'success',
                    'failed', 'cancelled' => 'danger',
                    'draft', 'pending' => 'warning',
                    default => 'gray',
                })
                ->sortable();
        }

        if (static::hasColumn('pdf_url')) {
            $columns[] = TextColumn::make('pdf_url')
                ->label('PDF')
                ->url(fn (?string $state): ?string => $state ?: null, shouldOpenInNewTab: true)
                ->limit(50)
                ->toggleable();
        }

        if (static::hasWorksTable()) {
            $columns[] = TextColumn::make('works_count')
                ->label('Работ')
                ->counts('works')
                ->sortable();
        }

        if (static::hasColumn('created_at')) {
            $columns[] = TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable();
        }

        $filters = [];

        if (static::hasColumn('status')) {
            $filters[] = SelectFilter::make('status')
                ->label('Статус')
                ->options([
                    'draft' => 'draft',
                    'pending' => 'pending',
                    'issued' => 'issued',
                    'paid' => 'paid',
                    'failed' => 'failed',
                    'cancelled' => 'cancelled',
                ]);
        }

        if (static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $filters[] = SelectFilter::make('counterparty_id')
                ->label('Контрагент')
                ->relationship('counterparty', static::counterpartyTitleAttribute());
        }

        return $table
            ->defaultSort(static::hasColumn('issued_at') ? 'issued_at' : (static::hasColumn('id') ? 'id' : 'invoice_number'), 'desc')
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
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
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
            static::$hasTableCache = SchemaFacade::hasTable('invoices');
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
            static::$hasColumnCache[$column] = SchemaFacade::hasColumn('invoices', $column);
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

    protected static function hasWorksTable(): bool
    {
        try {
            return SchemaFacade::hasTable('works');
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
}

