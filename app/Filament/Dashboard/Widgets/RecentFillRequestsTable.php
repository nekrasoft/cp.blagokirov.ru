<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Models\BunkerFillRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentFillRequestsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return DashboardMetrics::hasTable('bunker_fill_requests');
    }

    public function table(Table $table): Table
    {
        $sortColumn = DashboardMetrics::hasColumn('bunker_fill_requests', 'filled_at') ? 'filled_at' : 'id';
        $query = DashboardMetrics::fillRequestsQuery()
            ?->when(DashboardMetrics::hasColumn('bunker_fill_requests', 'counterparty_id'), fn (Builder $query): Builder => $query->with('counterparty'))
            ->orderByDesc($sortColumn)
            ->limit(8);

        return $table
            ->heading('Последние заявки')
            ->description('Новые отметки о заполнении бункеров.')
            ->query(fn (): Builder => $query ?? BunkerFillRequest::query()->whereRaw('1 = 0'))
            ->columns($this->columns())
            ->paginated(false)
            ->emptyStateHeading('Заявок пока нет')
            ->emptyStateDescription('Когда появятся заявки на заполнение, они будут показаны здесь.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    /**
     * @return array<int, TextColumn>
     */
    private function columns(): array
    {
        $columns = [];

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'filled_at')) {
            $columns[] = TextColumn::make('filled_at')
                ->label('Дата')
                ->dateTime('d.m.Y H:i')
                ->sortable();
        }

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'bunker_number')) {
            $columns[] = TextColumn::make('bunker_number')
                ->label('№ бункера')
                ->sortable();
        }

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'fill_level')) {
            $columns[] = TextColumn::make('fill_level')
                ->label('Заполненность')
                ->formatStateUsing(fn ($state): string => (int) ($state ?? 0) . '%')
                ->badge()
                ->color(fn ($state): string => match (true) {
                    (int) $state >= 100 => 'danger',
                    (int) $state >= 70 => 'warning',
                    default => 'success',
                });
        }

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'district')) {
            $columns[] = TextColumn::make('district')
                ->label('Район')
                ->toggleable();
        }

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'address')) {
            $columns[] = TextColumn::make('address')
                ->label('Адрес')
                ->wrap();
        }

        if (! DashboardMetrics::currentCounterpartyUser() && DashboardMetrics::hasTable('counterparties') && DashboardMetrics::hasColumn('bunker_fill_requests', 'counterparty_id')) {
            $columns[] = TextColumn::make(DashboardMetrics::hasColumn('counterparties', 'short_name') ? 'counterparty.short_name' : 'counterparty.name')
                ->label('Контрагент')
                ->toggleable();
        }

        return $columns;
    }
}
