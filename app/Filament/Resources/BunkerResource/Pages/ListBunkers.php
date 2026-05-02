<?php

namespace App\Filament\Resources\BunkerResource\Pages;

use App\Filament\Resources\BunkerResource;
use App\Filament\Support\DashboardMetrics;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBunkers extends ListRecords
{
    protected static string $resource = BunkerResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Все')
                ->badge(fn (): int => DashboardMetrics::safeCount(DashboardMetrics::bunkersQuery()))
                ->badgeColor('gray'),
        ];

        if (! DashboardMetrics::hasColumns('bunkers', ['fill_level'])) {
            return $tabs;
        }

        return [
            ...$tabs,
            'attention' => Tab::make('70%+')
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(fn (): int => DashboardMetrics::safeCount(
                    DashboardMetrics::bunkersQuery()?->where('fill_level', '>=', 70),
                ))
                ->badgeColor('warning')
                ->query(fn (Builder $query): Builder => $query->where('fill_level', '>=', 70)),
            'critical' => Tab::make('100%')
                ->icon('heroicon-m-fire')
                ->badge(fn (): int => DashboardMetrics::safeCount(
                    DashboardMetrics::bunkersQuery()?->where('fill_level', '>=', 100),
                ))
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => $query->where('fill_level', '>=', 100)),
            'ok' => Tab::make('До 70%')
                ->icon('heroicon-m-check-circle')
                ->badge(fn (): int => DashboardMetrics::safeCount(
                    DashboardMetrics::bunkersQuery()?->where('fill_level', '<', 70),
                ))
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query->where('fill_level', '<', 70)),
        ];
    }

    protected function getHeaderActions(): array
    {
        if (! BunkerResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
