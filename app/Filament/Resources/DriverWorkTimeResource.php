<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\AuthorizesAdminWrites;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Filament\Resources\DriverWorkTimeResource\Pages\CreateDriverWorkTime;
use App\Filament\Resources\DriverWorkTimeResource\Pages\EditDriverWorkTime;
use App\Filament\Resources\DriverWorkTimeResource\Pages\ListDriverWorkTimes;
use App\Filament\Support\DriverWorkTimeSummary;
use App\Models\CounterpartyUser;
use App\Models\DriverWorkTime;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;
use UnitEnum;

class DriverWorkTimeResource extends Resource
{
    use AuthorizesAdminWrites;
    use PreservesNavigationSearch;

    protected static ?string $model = DriverWorkTime::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Время водителей';

    protected static ?string $modelLabel = 'Запись времени водителя';

    protected static ?string $pluralModelLabel = 'Время водителей';

    protected static string|UnitEnum|null $navigationGroup = 'Водители';

    protected static ?int $navigationSort = 10;

    protected static ?bool $hasTableCache = null;

    protected static array $hasColumnCache = [];

    public static function form(Schema $schema): Schema
    {
        $components = [];

        if (static::hasColumn('source')) {
            $components[] = Select::make('source')
                ->label('Источник')
                ->options([
                    'max' => 'MAX',
                ])
                ->default('max')
                ->searchable()
                ->required();
        }

        if (static::hasColumn('source_chat_id')) {
            $components[] = TextInput::make('source_chat_id')
                ->label('ID чата')
                ->maxLength(64);
        }

        if (static::hasColumn('source_user_id')) {
            $components[] = TextInput::make('source_user_id')
                ->label('ID водителя')
                ->required()
                ->maxLength(64);
        }

        if (static::hasColumn('source_user_name')) {
            $components[] = TextInput::make('source_user_name')
                ->label('Водитель')
                ->maxLength(255);
        }

        if (static::hasColumn('work_date')) {
            $components[] = DatePicker::make('work_date')
                ->label('Дата')
                ->required();
        }

        if (static::hasColumn('start_time')) {
            $components[] = TimePicker::make('start_time')
                ->label('Начало')
                ->seconds(false)
                ->required();
        }

        if (static::hasColumn('end_time')) {
            $components[] = TimePicker::make('end_time')
                ->label('Окончание')
                ->seconds(false)
                ->required();
        }

        if (static::hasColumn('duration_minutes')) {
            $components[] = TextInput::make('duration_minutes')
                ->label('Длительность, мин')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
                ->helperText('Рассчитывается автоматически при сохранении.');
        }

        if (static::hasColumn('raw_start_text')) {
            $components[] = TextInput::make('raw_start_text')
                ->label('Исходный ввод начала')
                ->maxLength(50);
        }

        if (static::hasColumn('raw_end_text')) {
            $components[] = TextInput::make('raw_end_text')
                ->label('Исходный ввод окончания')
                ->maxLength(50);
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $columns = [];

        if (static::hasColumn('id')) {
            $columns[] = TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('work_date')) {
            $columns[] = TextColumn::make('work_date')
                ->label('Дата')
                ->date('d.m.Y')
                ->sortable();
        }

        if (static::hasColumn('source_user_name')) {
            $columns[] = TextColumn::make('source_user_name')
                ->label('Водитель')
                ->placeholder('Без имени')
                ->searchable()
                ->sortable();
        }

        if (static::hasColumn('source_user_id')) {
            $columns[] = TextColumn::make('source_user_id')
                ->label('ID водителя')
                ->searchable()
                ->copyable()
                ->toggleable();
        }

        if (static::hasColumn('start_time')) {
            $columns[] = TextColumn::make('start_time')
                ->label('Начало')
                ->time('H:i')
                ->sortable();
        }

        if (static::hasColumn('end_time')) {
            $columns[] = TextColumn::make('end_time')
                ->label('Окончание')
                ->time('H:i')
                ->sortable();
        }

        if (static::hasColumn('duration_minutes')) {
            $columns[] = TextColumn::make('duration_minutes')
                ->label('Длительность')
                ->formatStateUsing(fn (?int $state): string => static::formatDuration($state))
                ->sortable();
        }

        if (static::hasColumn('source')) {
            $columns[] = TextColumn::make('source')
                ->label('Источник')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'max' => 'MAX',
                    default => strtoupper((string) $state),
                })
                ->sortable()
                ->toggleable();
        }

        if (static::hasColumn('source_chat_id')) {
            $columns[] = TextColumn::make('source_chat_id')
                ->label('ID чата')
                ->searchable()
                ->copyable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('raw_start_text')) {
            $columns[] = TextColumn::make('raw_start_text')
                ->label('Ввод начала')
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('raw_end_text')) {
            $columns[] = TextColumn::make('raw_end_text')
                ->label('Ввод окончания')
                ->toggleable(isToggledHiddenByDefault: true);
        }

        if (static::hasColumn('updated_at')) {
            $columns[] = TextColumn::make('updated_at')
                ->label('Обновлено')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        $filters = [];

        if (static::hasColumn('source')) {
            $filters[] = SelectFilter::make('source')
                ->label('Источник')
                ->options([
                    'max' => 'MAX',
                ]);
        }

        if (static::hasColumn('work_date')) {
            $filters[] = Filter::make('work_month')
                ->label('Месяц')
                ->form([
                    Select::make('month')
                        ->label('Месяц')
                        ->options(fn (): array => DriverWorkTimeSummary::monthOptions())
                        ->searchable(),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $month = trim((string) ($data['month'] ?? ''));

                    if ($month === '') {
                        return $query;
                    }

                    $month = DriverWorkTimeSummary::month($month);

                    return $query->whereBetween('work_date', [
                        $month->startOfMonth()->toDateString(),
                        $month->endOfMonth()->toDateString(),
                    ]);
                });

            $filters[] = Filter::make('work_date_period')
                ->label('Период')
                ->form([
                    DatePicker::make('from')
                        ->label('С даты'),
                    DatePicker::make('until')
                        ->label('По дату'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('work_date', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('work_date', '<=', $date),
                        );
                });
        }

        $recordActions = [];
        $toolbarActions = [];

        if (static::hasAdminWriteAccess()) {
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
            ->defaultSort(static::hasColumn('work_date') ? 'work_date' : 'id', 'desc')
            ->columns($columns)
            ->filters($filters)
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDriverWorkTimes::route('/'),
            'create' => CreateDriverWorkTime::route('/create'),
            'edit' => EditDriverWorkTime::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasTable() && ! static::isCounterpartyAuthenticated();
    }

    public static function canAccess(): bool
    {
        return static::hasTable()
            && ! static::isCounterpartyAuthenticated()
            && parent::canAccess();
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
            static::$hasTableCache = SchemaFacade::hasTable('driver_work_time');
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
            static::$hasColumnCache[$column] = SchemaFacade::hasColumn('driver_work_time', $column);
        } catch (Throwable) {
            static::$hasColumnCache[$column] = false;
        }

        return static::$hasColumnCache[$column];
    }

    protected static function isCounterpartyAuthenticated(): bool
    {
        return Filament::auth()->user() instanceof CounterpartyUser;
    }

    protected static function formatDuration(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);

        return sprintf('%d ч %02d мин', intdiv($minutes, 60), $minutes % 60);
    }
}
