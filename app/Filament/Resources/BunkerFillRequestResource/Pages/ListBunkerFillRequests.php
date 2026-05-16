<?php

namespace App\Filament\Resources\BunkerFillRequestResource\Pages;

use App\Filament\Resources\BunkerFillRequestResource;
use App\Filament\Support\DashboardMetrics;
use Closure;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBunkerFillRequests extends ListRecords
{
    protected static string $resource = BunkerFillRequestResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Все')
                ->badge(fn (): int => $this->countRequests())
                ->badgeColor('gray'),
        ];

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'filled_at')) {
            $tabs['today'] = Tab::make('Сегодня')
                ->icon('heroicon-m-calendar-days')
                ->badge(fn (): int => $this->countRequests(fn (Builder $query): Builder => $query->whereDate('filled_at', now()->toDateString())))
                ->badgeColor('primary')
                ->query(fn (Builder $query): Builder => $query->whereDate('filled_at', now()->toDateString()));
        }

        if (DashboardMetrics::hasColumn('bunker_fill_requests', 'executed_at')) {
            $tabs['pending'] = Tab::make('Не исполнены')
                ->icon('heroicon-m-clock')
                ->badge(fn (): int => $this->countRequests(fn (Builder $query): Builder => $query->whereNull('executed_at')))
                ->badgeColor('warning')
                ->query(fn (Builder $query): Builder => $query->whereNull('executed_at'));

            $tabs['done'] = Tab::make('Исполнены')
                ->icon('heroicon-m-check-circle')
                ->badge(fn (): int => $this->countRequests(fn (Builder $query): Builder => $query->whereNotNull('executed_at')))
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('executed_at'));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    private function countRequests(?Closure $scope = null): int
    {
        $query = DashboardMetrics::fillRequestsQuery();

        if ($query && $scope) {
            $scope($query);
        }

        return DashboardMetrics::safeCount($query);
    }
}
