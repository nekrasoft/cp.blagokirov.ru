<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BunkerFillRequestResource\Pages\ListBunkerFillRequests;
use App\Filament\Resources\Concerns\AuthorizesAdminWrites;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Filament\Support\DashboardMetrics;
use App\Models\BunkerFillRequest;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
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
    use AuthorizesAdminWrites;
    use PreservesNavigationSearch;

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

        if (! $isCounterparty && static::hasColumn('id')) {
            $columns[] = TextColumn::make('id')
                ->label('ID')
                ->sortable();
        }

        if (static::hasColumn('filled_at')) {
            $columns[] = TextColumn::make('filled_at')
                ->label('Дата заявки')
                ->dateTime('d.m.Y H:i')
                ->sortable();
        }

        if (static::hasColumn('bunker_number')) {
            $columns[] = TextColumn::make('bunker_number')
                ->label('№ бункера')
                ->searchable()
                ->sortable();
        }

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $columns[] = TextColumn::make('counterparty.'.static::counterpartyTitleAttribute())
                ->label('Контрагент')
                ->searchable()
                ->sortable()
                ->color(fn (?string $state): string => filled($state) ? 'primary' : 'gray')
                ->url(fn (?string $state): ?string => static::counterpartySearchUrl($state));
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

        if (! $isCounterparty && static::hasColumn('filled_by')) {
            $columns[] = TextColumn::make('filled_by')
                ->label('Кто создал')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('executed_at')) {
            $columns[] = TextColumn::make('executed_at')
                ->label('Исполнение')
                ->badge()
                ->state(fn (BunkerFillRequest $record): string => static::statusLabel($record))
                ->color(fn (BunkerFillRequest $record): string => $record->cancelled_at ? 'danger' : ($record->executed_at ? 'success' : 'warning'))
                ->sortable();
        }

        if (static::hasColumn('cancellation_comment')) {
            $columns[] = TextColumn::make('cancellation_comment')
                ->label('Причина отмены')
                ->state(fn (BunkerFillRequest $record): ?string => $record->cancellation_comment
                    ?: static::cancellationReasonOptions()[$record->cancellation_reason_code] ?? null)
                ->placeholder('—')
                ->wrap()
                ->toggleable();
        }

        $filters = [];

        if (! $isCounterparty && static::hasColumn('counterparty_id') && static::hasCounterpartiesTable()) {
            $filters[] = SelectFilter::make('counterparty_id')
                ->label('Контрагент')
                ->relationship('counterparty', static::counterpartyTitleAttribute());
        }

        $recordActions = [];
        if (! $isCounterparty && static::hasAdminWriteAccess() && static::hasColumn('cancelled_at')) {
            $recordActions[] = Action::make('cancelRequest')
                ->label('Отменить')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (BunkerFillRequest $record): bool => ! $record->executed_at && ! $record->cancelled_at)
                ->requiresConfirmation()
                ->form([
                    Select::make('reason_code')
                        ->label('Причина')
                        ->options(static::cancellationReasonOptions())
                        ->required()
                        ->live(),
                    Textarea::make('comment')
                        ->label('Комментарий')
                        ->maxLength(500)
                        ->required(fn (Get $get): bool => $get('reason_code') === 'other')
                        ->visible(fn (Get $get): bool => $get('reason_code') === 'other'),
                ])
                ->action(function (BunkerFillRequest $record, array $data): void {
                    $reasonCode = (string) $data['reason_code'];
                    $comment = trim((string) ($data['comment'] ?? ''));
                    BunkerFillRequest::query()
                        ->whereKey($record->getKey())
                        ->whereNull('executed_at')
                        ->whereNull('cancelled_at')
                        ->update([
                            'cancelled_at' => now(),
                            'cancellation_reason_code' => $reasonCode,
                            'cancellation_comment' => $comment !== ''
                                ? $comment
                                : static::cancellationReasonOptions()[$reasonCode],
                            'cancelled_by' => (string) Filament::auth()->user()?->getAuthIdentifier(),
                        ]);
                });
        }

        return $table
            ->defaultSort(static::hasColumn('filled_at') ? 'filled_at' : (static::hasColumn('id') ? 'id' : 'bunker_id'), 'desc')
            ->columns($columns)
            ->filters($filters)
            ->recordActions($recordActions)
            ->toolbarActions([])
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateHeading('Заявок пока нет')
            ->emptyStateDescription('Когда бункеры отметят как заполненные, заявки появятся в этой истории.');
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

        $query->where('counterparty_id', $counterpartyId);

        return DashboardMetrics::applyDistrictScopeToFillRequestsQuery($query, $counterpartyUser);
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

    public static function statusLabel(BunkerFillRequest $record): string
    {
        if ($record->cancelled_at) {
            return 'Отменена '.$record->cancelled_at->format('d.m.Y');
        }

        return $record->executed_at
            ? 'Исполнена '.$record->executed_at->format('d.m.Y')
            : 'Не исполнена';
    }

    public static function cancellationReasonOptions(): array
    {
        return [
            'customer_cancelled' => 'Клиент отменил',
            'no_access' => 'Нет доступа или подъезда',
            'not_ready' => 'Бункер не готов',
            'vehicle_breakdown' => 'Поломка техники',
            'weather' => 'Погодные условия',
            'other' => 'Другое',
        ];
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
