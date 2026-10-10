<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BunkerPickupReportResource\Pages\ListBunkerPickupReports;
use App\Filament\Resources\BunkerPickupReportResource\Pages\ViewBunkerPickupReport;
use App\Filament\Resources\Concerns\PreservesNavigationSearch;
use App\Filament\Support\BunkerPickupReportScope;
use App\Models\BunkerPickupFile;
use App\Models\BunkerPickupReport;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

class BunkerPickupReportResource extends Resource
{
    use PreservesNavigationSearch;

    protected static ?string $model = BunkerPickupReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static ?string $navigationLabel = 'Отчёты о вывозе';

    protected static ?string $modelLabel = 'Отчёт о вывозе';

    protected static ?string $pluralModelLabel = 'Отчёты о вывозе';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|UnitEnum|null $navigationGroup = 'Вывоз мусора';

    protected static ?int $navigationSort = 31;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Вывоз')
                ->schema([
                    TextEntry::make('completed_at')->label('Дата')->dateTime('d.m.Y H:i'),
                    TextEntry::make('contractor')->label('Контрагент'),
                    TextEntry::make('driver_name')->label('Водитель')->placeholder('Не указан'),
                    TextEntry::make('billing_units')->label('Количество к оплате')->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ')),
                    TextEntry::make('cleanup_status')
                        ->label('Уборка')
                        ->formatStateUsing(fn (string $state): string => static::cleanupLabel($state)),
                    TextEntry::make('cleanup_comment')->label('Комментарий по уборке')->placeholder('—'),
                    TextEntry::make('waybill_status')
                        ->label('Талон')
                        ->state(fn (BunkerPickupReport $record): string => static::waybillStatus($record))
                        ->badge()
                        ->color(fn (BunkerPickupReport $record): string => $record->waybill_required && $record->waybills->isEmpty() ? 'warning' : 'success'),
                    TextEntry::make('sheets_status')->label('Запись в Google Sheets')
                        ->visible(fn (): bool => ! (Filament::auth()->user() instanceof CounterpartyUser))
                        ->formatStateUsing(fn (?string $state): string => static::sheetsStatusLabel($state))->placeholder('Нет данных'),
                    TextEntry::make('sheets_error')->label('Доставка в таблицу')->placeholder('—')
                        ->visible(fn (): bool => ! (Filament::auth()->user() instanceof CounterpartyUser)),
                    TextEntry::make('waybill_missing_reason')->label('Причина отсутствия талона')->placeholder('—'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Бункеры')
                ->schema([
                    ViewEntry::make('items')
                        ->hiddenLabel()
                        ->view('filament.infolists.components.bunker-pickup-items')
                        ->viewData(fn (BunkerPickupReport $record): array => [
                            'items' => $record->items->map(fn ($item): array => [
                                'number' => $item->bunker_number,
                                'mapUrl' => rtrim(config('services.cross_service_sso.map_service_url'), '/').'/?bunker='.rawurlencode($item->bunker_id),
                                'quantity' => $item->billing_units,
                                'volume' => $item->estimated_volume_m3,
                            ])->all(),
                        ]),
                ])
                ->columnSpanFull(),
            Section::make('Фото и документы')
                ->schema([
                    ViewEntry::make('files')
                        ->hiddenLabel()
                        ->view('filament.infolists.components.bunker-pickup-files')
                        ->viewData(function (BunkerPickupReport $record): array {
                            $photoIndex = 0;
                            $files = $record->files->map(function (BunkerPickupFile $file) use (&$photoIndex): array {
                                $isPhoto = $file->kind === 'site_photo';

                                return [
                                    'kind' => $file->kind,
                                    'name' => $file->file_name,
                                    'size' => $file->file_size,
                                    'url' => static::fileUrl($file),
                                    'photoIndex' => $isPhoto ? $photoIndex++ : null,
                                ];
                            })->all();

                            return [
                                'reportId' => $record->getKey(),
                                'files' => $files,
                                'photos' => array_values(array_filter($files, fn (array $file): bool => $file['kind'] === 'site_photo')),
                            ];
                        }),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('completed_at', 'desc')
            ->columns([
                TextColumn::make('completed_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('contractor')->label('Контрагент')->searchable()->sortable()
                    ->visible(fn (): bool => ! (Filament::auth()->user() instanceof CounterpartyUser))
                    ->color(fn (?string $state): string => filled($state) ? 'primary' : 'gray')
                    ->url(fn (?string $state): ?string => static::counterpartySearchUrl($state)),
                TextColumn::make('items_count')->label('Бункеров')->counts('items'),
                TextColumn::make('sheets_status')->label('Запись в Google Sheets')->badge()
                    ->visible(fn (): bool => ! (Filament::auth()->user() instanceof CounterpartyUser))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Нет данных')
                    ->formatStateUsing(fn (?string $state): string => static::sheetsStatusLabel($state))
                    ->color(fn (?string $state): string => $state === 'sent' ? 'success' : 'warning'),
                TextColumn::make('billing_units')->label('Количество')->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' '))->sortable(),
                TextColumn::make('driver_name')->label('Водитель')->placeholder('—')->toggleable(),
                TextColumn::make('cleanup_status')
                    ->label('Уборка')
                    ->formatStateUsing(fn (string $state): string => static::cleanupLabel($state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'not_cleaned' ? 'danger' : 'success'),
                TextColumn::make('site_photos_count')->label('Фото')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? (string) $state : '—')
                    ->toggleable(),
                TextColumn::make('waybill_state')
                    ->label('Талон')
                    ->state(fn (BunkerPickupReport $record): string => static::waybillStatus($record))
                    ->badge()
                    ->color(fn (BunkerPickupReport $record): string => $record->waybill_required && $record->waybills_count === 0 ? 'warning' : 'success'),
            ])
            ->filters([
                TernaryFilter::make('missing_waybill')
                    ->label('Нет обязательного талона')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->where('waybill_required', true)
                            ->whereDoesntHave('waybills'),
                        false: fn (Builder $query): Builder => $query
                            ->where(fn (Builder $query): Builder => $query
                                ->where('waybill_required', false)
                                ->orWhereHas('waybills')),
                    ),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('Отчётов пока нет');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withCount(['items', 'sitePhotos', 'waybills']);
        $user = Filament::auth()->user();

        return $user instanceof CounterpartyUser
            ? BunkerPickupReportScope::apply($query, $user)
            : $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBunkerPickupReports::route('/'),
            'view' => ViewBunkerPickupReport::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        try {
            return SchemaFacade::hasTable('bunker_pickup_reports');
        } catch (Throwable) {
            return false;
        }
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation() && parent::canAccess();
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

    public static function sheetsStatusLabel(?string $status): string
    {
        return match ($status) {
            null => 'Нет данных',
            'pending' => 'В очереди',
            'sending' => 'Отправляется',
            'retry' => 'Ожидает повторной доставки',
            'sent' => 'Доставлен',
            default => '—',
        };
    }

    public static function cleanupLabel(string $status): string
    {
        return match ($status) {
            'cleaned' => 'Прибрана',
            'not_required' => 'Не требовалась',
            'not_cleaned' => 'Не прибрана',
            default => $status,
        };
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

    private static function waybillStatus(BunkerPickupReport $record): string
    {
        if ($record->waybills->isNotEmpty() || ($record->waybills_count ?? 0) > 0) {
            return 'Приложен';
        }

        return $record->waybill_required ? 'Ожидается' : 'Не требуется';
    }

    private static function fileUrl(BunkerPickupFile $file): string
    {
        return Filament::getCurrentPanel()?->getId() === 'counterparty'
            ? route('billing.pickup-files.show', ['file' => $file->getKey()])
            : route('admin.pickup-files.show', ['file' => $file->getKey()]);
    }
}
