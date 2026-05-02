<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Models\Bunker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AttentionBunkersTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return DashboardMetrics::hasColumns('bunkers', ['fill_level']);
    }

    public function table(Table $table): Table
    {
        $query = DashboardMetrics::bunkersQuery()
            ?->where('fill_level', '>=', 70)
            ->when(DashboardMetrics::hasColumn('bunkers', 'counterparty_id'), fn (Builder $query): Builder => $query->with('counterparty'))
            ->orderByDesc('fill_level')
            ->limit(8);

        return $table
            ->heading('Бункеры требуют внимания')
            ->description('Площадки с заполненностью от 70%.')
            ->query(fn (): Builder => $query ?? Bunker::query()->whereRaw('1 = 0'))
            ->columns($this->columns())
            ->paginated(false)
            ->emptyStateHeading('Критичных бункеров нет')
            ->emptyStateDescription('Сейчас нет бункеров с заполненностью выше 70%.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * @return array<int, TextColumn>
     */
    private function columns(): array
    {
        $columns = [];

        if (DashboardMetrics::hasColumn('bunkers', 'number')) {
            $columns[] = TextColumn::make('number')
                ->label('№')
                ->sortable();
        }

        $columns[] = TextColumn::make('fill_level')
            ->label('Заполненность')
            ->formatStateUsing(fn ($state): string => (int) ($state ?? 0) . '%')
            ->badge()
            ->color(fn ($state): string => ((int) $state >= 100) ? 'danger' : 'warning')
            ->sortable();

        if (DashboardMetrics::hasColumn('bunkers', 'district')) {
            $columns[] = TextColumn::make('district')
                ->label('Район')
                ->searchable();
        }

        if (DashboardMetrics::hasColumn('bunkers', 'address')) {
            $columns[] = TextColumn::make('address')
                ->label('Адрес')
                ->wrap()
                ->searchable();
        }

        if (! DashboardMetrics::currentCounterpartyUser() && DashboardMetrics::hasTable('counterparties') && DashboardMetrics::hasColumn('bunkers', 'counterparty_id')) {
            $columns[] = TextColumn::make(DashboardMetrics::hasColumn('counterparties', 'short_name') ? 'counterparty.short_name' : 'counterparty.name')
                ->label('Контрагент')
                ->toggleable();
        }

        if (DashboardMetrics::hasColumn('bunkers', 'last_filled_at')) {
            $columns[] = TextColumn::make('last_filled_at')
                ->label('Обновлено')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable();
        }

        return $columns;
    }
}
