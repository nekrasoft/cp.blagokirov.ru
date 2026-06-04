<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\AuthorizesAdminWrites;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Filament\Resources\DriverSalarySettingResource\Pages\CreateDriverSalarySetting;
use App\Filament\Resources\DriverSalarySettingResource\Pages\EditDriverSalarySetting;
use App\Filament\Resources\DriverSalarySettingResource\Pages\ListDriverSalarySettings;
use App\Models\CounterpartyUser;
use App\Models\DriverSalarySetting;
use App\Models\DriverWorkTime;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Validation\Rules\Unique;
use Throwable;
use UnitEnum;

class DriverSalarySettingResource extends Resource
{
    use AuthorizesAdminWrites;
    use PreservesNavigationSearch;

    protected static ?string $model = DriverSalarySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'ЗП водителей';

    protected static ?string $modelLabel = 'Настройка ЗП водителя';

    protected static ?string $pluralModelLabel = 'ЗП водителей';

    protected static string|UnitEnum|null $navigationGroup = 'Водители';

    protected static ?int $navigationSort = 30;

    protected static ?bool $hasTableCache = null;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('source')
                ->label('Источник')
                ->options([
                    'max' => 'MAX',
                ])
                ->default('max')
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('source_user_id', null);
                    $set('source_user_name', null);
                })
                ->searchable()
                ->required(),

            Select::make('source_user_id')
                ->label('ID водителя')
                ->required()
                ->live()
                ->searchable()
                ->options(fn (Get $get): array => static::driverOptions((string) $get('source')))
                ->getSearchResultsUsing(fn (Get $get, string $search): array => static::driverOptions((string) $get('source'), $search))
                ->getOptionLabelUsing(fn (Get $get, mixed $value): ?string => static::driverLabel((string) $get('source'), $value))
                ->afterStateUpdated(fn (Get $get, Set $set, ?string $state): mixed => $set('source_user_name', static::driverName((string) $get('source'), $state)))
                ->unique(
                    table: DriverSalarySetting::class,
                    column: 'source_user_id',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('source', (string) $get('source')),
                )
                ->selectablePlaceholder(false),

            TextInput::make('source_user_name')
                ->label('Водитель')
                ->maxLength(255),

            TextInput::make('hourly_rate')
                ->label('Ставка, руб/ч')
                ->numeric()
                ->inputMode('decimal')
                ->rule('min:0')
                ->required(),

            TextInput::make('overtime_threshold_hours')
                ->label('Порог переработки, ч')
                ->numeric()
                ->inputMode('decimal')
                ->rule('min:0')
                ->required(),

            TextInput::make('overtime_hourly_rate')
                ->label('Ставка переработки, руб/ч')
                ->numeric()
                ->inputMode('decimal')
                ->rule('min:0')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
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
            ->defaultSort('source_user_name')
            ->columns([
                TextColumn::make('source_user_name')
                    ->label('Водитель')
                    ->placeholder('Без имени')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('source_user_id')
                    ->label('ID водителя')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('hourly_rate')
                    ->label('Ставка')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('overtime_threshold_hours')
                    ->label('Порог, ч')
                    ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->sortable(),

                TextColumn::make('overtime_hourly_rate')
                    ->label('Переработка')
                    ->money('RUB')
                    ->sortable(),
            ])
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDriverSalarySettings::route('/'),
            'create' => CreateDriverSalarySetting::route('/create'),
            'edit' => EditDriverSalarySetting::route('/{record}/edit'),
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
            static::$hasTableCache = SchemaFacade::hasTable('driver_salary_settings');
        } catch (Throwable) {
            static::$hasTableCache = false;
        }

        return static::$hasTableCache;
    }

    /**
     * @return array<string, string>
     */
    protected static function driverOptions(string $source, ?string $search = null): array
    {
        if (! static::canQueryDriverWorkTime()) {
            return [];
        }

        $source = trim($source);
        $search = trim((string) $search);

        try {
            $query = DriverWorkTime::query()
                ->select(['source_user_id'])
                ->selectRaw("MAX(NULLIF(TRIM(source_user_name), '')) as source_user_name")
                ->where('source', $source)
                ->whereNotNull('source_user_id')
                ->where('source_user_id', '<>', '')
                ->groupBy('source_user_id')
                ->orderBy('source_user_id')
                ->limit(50);

            if ($search !== '') {
                $query->where('source_user_id', 'like', $search.'%');
            }

            return $query
                ->get()
                ->mapWithKeys(fn (DriverWorkTime $driver): array => [
                    (string) $driver->source_user_id => static::formatDriverOption(
                        (string) $driver->source_user_id,
                        (string) $driver->source_user_name,
                    ),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected static function driverLabel(string $source, ?string $driverId): ?string
    {
        $driverId = trim((string) $driverId);

        if ($driverId === '') {
            return null;
        }

        return static::formatDriverOption($driverId, static::driverName($source, $driverId));
    }

    protected static function driverName(string $source, ?string $driverId): string
    {
        $source = trim($source);
        $driverId = trim((string) $driverId);

        if ($driverId === '' || ! static::canQueryDriverWorkTime()) {
            return '';
        }

        try {
            return (string) DriverWorkTime::query()
                ->where('source', $source)
                ->where('source_user_id', $driverId)
                ->whereNotNull('source_user_name')
                ->where('source_user_name', '<>', '')
                ->orderByDesc('work_date')
                ->value('source_user_name');
        } catch (Throwable) {
            return '';
        }
    }

    protected static function formatDriverOption(string $driverId, string $driverName): string
    {
        $driverName = trim($driverName);

        return $driverName === '' ? $driverId : "{$driverId} ({$driverName})";
    }

    protected static function canQueryDriverWorkTime(): bool
    {
        try {
            return SchemaFacade::hasTable('driver_work_time')
                && SchemaFacade::hasColumn('driver_work_time', 'source')
                && SchemaFacade::hasColumn('driver_work_time', 'source_user_id')
                && SchemaFacade::hasColumn('driver_work_time', 'source_user_name')
                && SchemaFacade::hasColumn('driver_work_time', 'work_date');
        } catch (Throwable) {
            return false;
        }
    }

    protected static function isCounterpartyAuthenticated(): bool
    {
        return Filament::auth()->user() instanceof CounterpartyUser;
    }
}
