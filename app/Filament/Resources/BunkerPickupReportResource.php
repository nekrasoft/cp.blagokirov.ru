<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BunkerPickupReportResource\Pages\ListBunkerPickupReports;
use App\Filament\Resources\BunkerPickupReportResource\Pages\ViewBunkerPickupReport;
use App\Filament\Support\BunkerPickupReportScope;
use App\Models\BunkerPickupFile;
use App\Models\BunkerPickupReport;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
    protected static ?string $model = BunkerPickupReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static ?string $navigationLabel = 'Отчёты о вывозе';

    protected static ?string $modelLabel = 'Отчёт о вывозе';

    protected static ?string $pluralModelLabel = 'Отчёты о вывозе';

    protected static string|UnitEnum|null $navigationGroup = 'Карта бункеров';

    protected static ?int $navigationSort = 31;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Вывоз')
                ->schema([
                    TextEntry::make('completed_at')->label('Дата')->dateTime('d.m.Y H:i'),
                    TextEntry::make('contractor')->label('Контрагент'),
                    TextEntry::make('driver_name')->label('Водитель')->placeholder('Не указан'),
                    TextEntry::make('billing_units')->label('Количество к оплате'),
                    TextEntry::make('cleanup_status')
                        ->label('Уборка')
                        ->formatStateUsing(fn (string $state): string => static::cleanupLabel($state)),
                    TextEntry::make('cleanup_comment')->label('Комментарий по уборке')->placeholder('—'),
                    TextEntry::make('waybill_status')
                        ->label('Талон')
                        ->state(fn (BunkerPickupReport $record): string => static::waybillStatus($record))
                        ->badge()
                        ->color(fn (BunkerPickupReport $record): string => $record->waybill_required && $record->waybills->isEmpty() ? 'warning' : 'success'),
                    TextEntry::make('waybill_missing_reason')->label('Причина отсутствия талона')->placeholder('—'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Бункеры')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('bunker_number')->label('№ бункера'),
                            TextEntry::make('billing_units')->label('Количество'),
                            TextEntry::make('estimated_volume_m3')->label('Расчётный объём')->suffix(' м³'),
                        ])
                        ->columns(3),
                ])
                ->columnSpanFull(),
            Section::make('Фото и документы')
                ->schema([
                    RepeatableEntry::make('files')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('kind')
                                ->label('Тип')
                                ->formatStateUsing(fn (string $state): string => $state === 'container_waybill' ? 'Талон' : 'Фото площадки'),
                            TextEntry::make('file_name')
                                ->label('Файл')
                                ->url(fn (BunkerPickupFile $record): string => static::fileUrl($record), true),
                            TextEntry::make('file_size')
                                ->label('Размер')
                                ->formatStateUsing(fn (int $state): string => number_format($state / 1024 / 1024, 2, ',', ' ').' МБ'),
                        ])
                        ->columns(3),
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
                TextColumn::make('contractor')->label('Контрагент')->searchable()->sortable(),
                TextColumn::make('items_count')->label('Бункеров')->counts('items'),
                TextColumn::make('billing_units')->label('Количество')->sortable(),
                TextColumn::make('driver_name')->label('Водитель')->placeholder('—')->toggleable(),
                TextColumn::make('cleanup_status')
                    ->label('Уборка')
                    ->formatStateUsing(fn (string $state): string => static::cleanupLabel($state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'not_cleaned' ? 'danger' : 'success'),
                TextColumn::make('photos_count')->label('Фото')->counts('sitePhotos'),
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
            ->recordActions([ViewAction::make()])
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

    public static function cleanupLabel(string $status): string
    {
        return match ($status) {
            'cleaned' => 'Прибрана',
            'not_required' => 'Не требовалась',
            'not_cleaned' => 'Не прибрана',
            default => $status,
        };
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
