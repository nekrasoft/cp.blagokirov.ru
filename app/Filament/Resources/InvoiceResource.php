<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\AuthorizesAdminWrites;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\CounterpartyUser;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;
use UnitEnum;

class InvoiceResource extends Resource
{
    use AuthorizesAdminWrites;
    use PreservesNavigationSearch;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Счета';

    protected static ?string $modelLabel = 'Счёт';

    protected static ?string $pluralModelLabel = 'Счета';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?bool $hasTableCache = null;

    protected static array $hasColumnCache = [];

    protected static array $hasCounterpartyColumnCache = [];

    protected static ?bool $hasInvoiceItemsTableCache = null;

    protected static array $hasInvoiceItemsColumnCache = [];

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

        if (static::canEditInvoiceItems()) {
            $components[] = Placeholder::make('items_total_preview')
                ->label('Итоговая сумма счёта')
                ->content(fn (Get $get): string => static::formatMoney(static::invoiceItemsStateTotal($get('items'))))
                ->visible(fn (?Invoice $record): bool => (bool) $record?->exists);

            $components[] = Repeater::make('items')
                ->label('Позиции счёта')
                ->relationship(
                    name: 'items',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('id'),
                )
                ->table([
                    TableColumn::make('Наименование'),
                    TableColumn::make('Цена')->markAsRequired()->width('140px'),
                    TableColumn::make('Кол-во')->markAsRequired()->width('120px'),
                    TableColumn::make('Ед.')->width('90px'),
                    TableColumn::make('НДС')->width('90px'),
                ])
                ->schema([
                    TextInput::make('name')
                        ->hiddenLabel()
                        ->required()
                        ->maxLength(1000),

                    TextInput::make('price')
                        ->hiddenLabel()
                        ->numeric()
                        ->inputMode('decimal')
                        ->rule('min:0')
                        ->required()
                        ->live(debounce: 500),

                    TextInput::make('amount')
                        ->hiddenLabel()
                        ->numeric()
                        ->inputMode('decimal')
                        ->rule('min:0')
                        ->required()
                        ->live(debounce: 500),

                    TextInput::make('unit')
                        ->hiddenLabel()
                        ->required()
                        ->maxLength(50),

                    TextInput::make('vat')
                        ->hiddenLabel()
                        ->maxLength(10),
                ])
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->visible(fn (?Invoice $record): bool => (bool) $record?->exists);
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $isCounterparty = static::isCounterpartyAuthenticated();
        $columns = [];

        if (! $isCounterparty && static::hasColumn('id')) {
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

            $columns[] = $invoiceNumberColumn;
        }

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $columns[] = TextColumn::make('counterparty.'.static::counterpartyTitleAttribute())
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
                ->color(fn (Invoice $record): string => static::isOverdue($record) ? 'danger' : 'gray')
                ->icon(fn (Invoice $record): ?string => static::isOverdue($record) ? 'heroicon-m-exclamation-triangle' : null)
                ->sortable();
        }

        if (static::canUseInvoiceItemsTotal() || static::hasColumn('paid_amount')) {
            $amountColumn = TextColumn::make(static::canUseInvoiceItemsTotal() ? 'items_total' : 'paid_amount')
                ->label('Сумма')
                ->state(fn (Invoice $record): float => static::invoiceTotalAmount($record))
                ->formatStateUsing(
                    fn ($state): string => static::formatMoney((float) ($state ?? 0))
                )
                ->sortable();

            if (! $isCounterparty) {
                $amountColumn->toggleable();
            }

            $columns[] = $amountColumn;
        }

        if (static::hasColumn('status')) {
            $columns[] = TextColumn::make('status')
                ->label('Статус')
                ->badge()
                ->formatStateUsing(fn (?string $state, Invoice $record): string => static::invoiceStatusLabel($state, $record))
                ->color(fn (?string $state): string => match ($state) {
                    'paid' => 'success',
                    'issued' => 'warning',
                    'failed', 'cancelled' => 'danger',
                    'draft' => 'gray',
                    'pending' => 'info',
                    default => 'gray',
                })
                ->sortable();
        }

        if (static::hasColumn('pdf_url')) {
            $pdfColumn = TextColumn::make('pdf_url')
                ->label('PDF')
                ->url(fn (Invoice $record): ?string => $record->pdf_url ?: null, shouldOpenInNewTab: true)
                ->formatStateUsing(
                    fn (?string $state, Invoice $record): ?string => filled($state)
                        ? 'Открыть счет №'.($record->invoice_number ?: $record->id)
                        : null
                )
                ->color(fn (Invoice $record): string => filled($record->pdf_url) ? 'primary' : 'gray')
                ->icon(fn (Invoice $record): ?string => filled($record->pdf_url) ? 'heroicon-m-arrow-top-right-on-square' : null)
                ->iconPosition('after');

            if (! $isCounterparty) {
                $pdfColumn->toggleable(isToggledHiddenByDefault: true);
            }

            $columns[] = $pdfColumn;
        }

        if (! $isCounterparty && static::hasColumn('bitrix_task_id')) {
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

        if (! $isCounterparty && static::hasColumn('created_at')) {
            $createdAtColumn = TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
                ->sortable();

            $createdAtColumn->toggleable(isToggledHiddenByDefault: true);

            $columns[] = $createdAtColumn;
        }

        $filters = [];

        if (static::hasColumn('status')) {
            $filters[] = SelectFilter::make('status')
                ->label('Статус')
                ->options([
                    'draft' => 'Черновик',
                    'pending' => 'В обработке',
                    'issued' => 'К оплате',
                    'paid' => 'Оплачен',
                    'failed' => 'Ошибка',
                    'cancelled' => 'Отменён',
                ]);
        }

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $filters[] = SelectFilter::make('counterparty_id')
                ->label('Контрагент')
                ->relationship('counterparty', static::counterpartyTitleAttribute());
        }

        $recordActions = [];
        $toolbarActions = [];

        if (! $isCounterparty && static::hasAdminWriteAccess()) {
            $recordActions = [
                EditAction::make(),
                DeleteAction::make(),
            ];

            $bulkActions = [];

            if (static::canMarkAsPaid()) {
                $bulkActions[] = BulkAction::make('markAsPaid')
                    ->label('Отметить оплаченными')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Отметить выбранные счета оплаченными?')
                    ->modalDescription('Сумма оплаты будет равна сумме привязанных работ, дата оплаты - сегодня.')
                    ->successNotificationTitle('Счета отмечены оплаченными')
                    ->action(function (Collection $records): void {
                        static::markAsPaid($records);
                    });
            }

            $bulkActions[] = DeleteBulkAction::make();

            $toolbarActions = [
                BulkActionGroup::make($bulkActions),
            ];
        }

        return $table
            ->defaultSort(static::hasColumn('issued_at') ? 'issued_at' : (static::hasColumn('id') ? 'id' : 'invoice_number'), 'desc')
            ->columns($columns)
            ->filters($filters)
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions)
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('Счетов пока нет')
            ->emptyStateDescription('Когда счета появятся, здесь будут видны статус оплаты, срок и ссылки на PDF.');
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
            && parent::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::canUseInvoiceItemsTotal()) {
            $query->withSum('items as items_total', DB::raw('price * amount'));
        }

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
        return static::hasAdminWriteAccess()
            && ! static::isCounterpartyAuthenticated()
            && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return static::hasAdminWriteAccess()
            && ! static::isCounterpartyAuthenticated()
            && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::hasAdminWriteAccess()
            && ! static::isCounterpartyAuthenticated()
            && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::hasAdminWriteAccess()
            && ! static::isCounterpartyAuthenticated()
            && parent::canDeleteAny();
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

    protected static function hasWorksColumn(string $column): bool
    {
        try {
            return SchemaFacade::hasColumn('works', $column);
        } catch (Throwable) {
            return false;
        }
    }

    protected static function hasInvoiceItemsTable(): bool
    {
        if (static::$hasInvoiceItemsTableCache !== null) {
            return static::$hasInvoiceItemsTableCache;
        }

        try {
            static::$hasInvoiceItemsTableCache = SchemaFacade::hasTable('invoice_items');
        } catch (Throwable) {
            static::$hasInvoiceItemsTableCache = false;
        }

        return static::$hasInvoiceItemsTableCache;
    }

    protected static function hasInvoiceItemsColumn(string $column): bool
    {
        if (array_key_exists($column, static::$hasInvoiceItemsColumnCache)) {
            return static::$hasInvoiceItemsColumnCache[$column];
        }

        try {
            static::$hasInvoiceItemsColumnCache[$column] = SchemaFacade::hasColumn('invoice_items', $column);
        } catch (Throwable) {
            static::$hasInvoiceItemsColumnCache[$column] = false;
        }

        return static::$hasInvoiceItemsColumnCache[$column];
    }

    protected static function canUseInvoiceItemsTotal(): bool
    {
        return static::hasInvoiceItemsTable()
            && static::hasInvoiceItemsColumn('invoice_id')
            && static::hasInvoiceItemsColumn('price')
            && static::hasInvoiceItemsColumn('amount');
    }

    protected static function canEditInvoiceItems(): bool
    {
        return static::canUseInvoiceItemsTotal()
            && static::hasInvoiceItemsColumn('name')
            && static::hasInvoiceItemsColumn('unit')
            && static::hasInvoiceItemsColumn('vat');
    }

    protected static function invoiceTotalAmount(Invoice $record): float
    {
        return (float) ($record->getAttribute('items_total') ?? $record->paid_amount ?? 0);
    }

    protected static function invoiceItemsStateTotal(mixed $itemsState): float
    {
        if (! is_array($itemsState)) {
            return 0.0;
        }

        return array_reduce(
            $itemsState,
            fn (float $total, mixed $item): float => $total + (
                is_array($item)
                    ? static::decimalStateValue($item['price'] ?? null) * static::decimalStateValue($item['amount'] ?? null)
                    : 0.0
            ),
            0.0,
        );
    }

    protected static function decimalStateValue(mixed $value): float
    {
        $value = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected static function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' ₽';
    }

    protected static function invoiceStatusLabel(?string $state, Invoice $record): string
    {
        if ($state === 'paid') {
            return $record->paid_at
                ? 'Оплачен '.$record->paid_at->format('d.m.Y')
                : 'Оплачен';
        }

        return match ($state) {
            'draft' => 'Черновик',
            'pending' => 'В обработке',
            'issued' => 'К оплате',
            'failed' => 'Ошибка',
            'cancelled' => 'Отменён',
            default => 'Без статуса',
        };
    }

    protected static function canMarkAsPaid(): bool
    {
        return static::hasColumn('status')
            && static::hasColumn('paid_amount')
            && static::hasColumn('paid_at')
            && static::hasWorksTable()
            && static::hasWorksColumn('invoice_id')
            && static::hasWorksColumn('revenue');
    }

    /**
     * @param  Collection<int, Invoice>  $records
     */
    protected static function markAsPaid(Collection $records): void
    {
        $invoiceIds = $records
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $paidAmounts = DB::table('works')
            ->select('invoice_id', DB::raw('COALESCE(SUM(revenue), 0) as paid_amount'))
            ->whereIn('invoice_id', $invoiceIds->all())
            ->groupBy('invoice_id')
            ->pluck('paid_amount', 'invoice_id');

        $paidAmountSql = $invoiceIds
            ->map(function (int $invoiceId) use ($paidAmounts): string {
                $paidAmount = number_format((float) ($paidAmounts[$invoiceId] ?? 0), 2, '.', '');

                return "WHEN {$invoiceId} THEN {$paidAmount}";
            })
            ->implode(' ');

        Invoice::query()
            ->whereKey($invoiceIds->all())
            ->update([
                'status' => 'paid',
                'paid_amount' => DB::raw("CASE id {$paidAmountSql} ELSE paid_amount END"),
                'paid_at' => now(),
            ]);
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

    protected static function isOverdue(Invoice $record): bool
    {
        if (! static::hasColumn('due_date') || ! $record->due_date) {
            return false;
        }

        if (static::hasColumn('status') && $record->status === 'paid') {
            return false;
        }

        return $record->due_date->isPast() && ! $record->due_date->isToday();
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
