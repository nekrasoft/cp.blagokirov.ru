<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\CounterpartyUser;
use App\Models\Invoice;
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
use Illuminate\Database\Eloquent\Model;
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

        if (static::hasColumn('paid_amount')) {
            $components[] = TextInput::make('paid_amount')
                ->label('Оплачено')
                ->numeric()
                ->inputMode('decimal');
        }

        if (static::hasColumn('paid_at')) {
            $components[] = DateTimePicker::make('paid_at')
                ->label('Оплачен')
                ->seconds(false);
        }

        if (static::hasColumn('pdf_url')) {
            $components[] = TextInput::make('pdf_url')
                ->label('Ссылка на PDF')
                ->url()
                ->maxLength(500);
        }

        if (static::hasColumn('bitrix_task_id')) {
            $components[] = TextInput::make('bitrix_task_id')
                ->label('ID задачи Bitrix24')
                ->numeric()
                ->rule('integer');
        }

        if (static::hasColumn('bitrix_deal_id')) {
            $components[] = TextInput::make('bitrix_deal_id')
                ->label('ID сделки Bitrix24')
                ->numeric()
                ->rule('integer');
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

        if (static::hasColumn('invoice_number')) {
            $invoiceNumberColumn = TextColumn::make('invoice_number')
                ->label('Номер')
                ->searchable()
                ->sortable()
                ->color(fn (Invoice $record): string => $record->pdf_url ? 'primary' : 'gray')
                ->icon(fn (Invoice $record): ?string => $record->pdf_url ? 'heroicon-m-arrow-top-right-on-square' : null)
                ->iconPosition('after')
                ->url(fn (Invoice $record): ?string => $record->pdf_url ?: null, shouldOpenInNewTab: true);

            if ($isCounterparty) {
                $invoiceNumberColumn->copyable();
            }

            $columns[] = $invoiceNumberColumn;
        }

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $columns[] = TextColumn::make('counterparty.' . static::counterpartyTitleAttribute())
                ->label('Контрагент')
                ->searchable()
                ->sortable()
                ->color(fn (?string $state): string => filled($state) ? 'primary' : 'gray')
                ->url(fn (?string $state): ?string => static::counterpartySearchUrl($state));
        }

        if (static::hasColumn('issued_at')) {
            $columns[] = TextColumn::make('issued_at')
                ->label('Выставлен')
                ->date('d.m.Y')
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
                    'paid' => 'success',
                    'issued' => 'info',
                    'failed', 'cancelled' => 'danger',
                    'draft', 'pending' => 'warning',
                    default => 'gray',
                })
                ->sortable();
        }

        if (static::hasColumn('paid_amount')) {
            $paidAmountColumn = TextColumn::make('paid_amount')
                ->label('Оплачено')
                ->formatStateUsing(
                    fn ($state): string => number_format((float) ($state ?? 0), 2, ',', ' ')
                )
                ->sortable();

            if (! $isCounterparty) {
                $paidAmountColumn->toggleable();
            }

            $columns[] = $paidAmountColumn;
        }

        if (static::hasColumn('paid_at')) {
            $paidAtColumn = TextColumn::make('paid_at')
                ->label('Оплачен')
                ->date('d.m.Y')
                ->sortable();

            if (! $isCounterparty) {
                $paidAtColumn->toggleable();
            }

            $columns[] = $paidAtColumn;
        }

        if (static::hasColumn('pdf_url')) {
            $pdfColumn = TextColumn::make('pdf_url')
                ->label('PDF')
                ->url(fn (?string $state): ?string => $state ?: null, shouldOpenInNewTab: true)
                ->limit(50);

            if (! $isCounterparty) {
                $pdfColumn->toggleable(isToggledHiddenByDefault: true);
            }

            $columns[] = $pdfColumn;
        }

        if (static::hasColumn('bitrix_task_id')) {
            $columns[] = TextColumn::make('bitrix_task_id')
                ->label('Задача')
                ->color(
                    fn (Invoice $record): string => static::buildBitrixTaskUrl($record->bitrix_task_id) ? 'primary' : 'gray'
                )
                ->icon(
                    fn (Invoice $record): ?string => static::buildBitrixTaskUrl($record->bitrix_task_id)
                        ? 'heroicon-m-arrow-top-right-on-square'
                        : null
                )
                ->iconPosition('after')
                ->url(
                    fn (Invoice $record): ?string => static::buildBitrixTaskUrl($record->bitrix_task_id),
                    shouldOpenInNewTab: true,
                )
                ->sortable()
                ->toggleable();
        }

        if (static::hasWorksTable()) {
            $columns[] = TextColumn::make('works_count')
                ->label('Работ')
                ->counts('works')
                ->sortable();
        }

        if (static::hasColumn('created_at')) {
            $createdAtColumn = TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
                ->sortable();

            if (! $isCounterparty) {
                $createdAtColumn->toggleable(isToggledHiddenByDefault: true);
            }

            $columns[] = $createdAtColumn;
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

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $filters[] = SelectFilter::make('counterparty_id')
                ->label('Контрагент')
                ->relationship('counterparty', static::counterpartyTitleAttribute());
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
            ->defaultSort(static::hasColumn('issued_at') ? 'issued_at' : (static::hasColumn('id') ? 'id' : 'invoice_number'), 'desc')
            ->columns($columns)
            ->filters($filters)
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions);
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
        return static::hasTable()
            && ! static::isCounterpartyAuthenticated()
            && parent::canAccess();
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

        return $query->where('counterparty_id', $counterpartyUser->counterparty_id);
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

    protected static function buildBitrixTaskUrl(?int $taskId): ?string
    {
        if (! $taskId) {
            return null;
        }

        $baseUrl = static::bitrixBaseUrl();

        if ($baseUrl === '') {
            return null;
        }

        return sprintf('%s/workgroups/group/174/tasks/task/view/%d/', $baseUrl, $taskId);
    }

    protected static function buildBitrixDealUrl(?int $dealId): ?string
    {
        if (! $dealId) {
            return null;
        }

        $baseUrl = static::bitrixBaseUrl();

        if ($baseUrl === '') {
            return null;
        }

        return sprintf('%s/crm/deal/details/%d/', $baseUrl, $dealId);
    }

    protected static function bitrixBaseUrl(): string
    {
        return rtrim(trim((string) config('services.bitrix24.base_url', '')), '/');
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
