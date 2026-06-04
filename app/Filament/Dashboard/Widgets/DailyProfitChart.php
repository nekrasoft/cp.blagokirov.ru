<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class DailyProfitChart extends ChartWidget
{
    protected ?string $heading = 'Прибыль по дням';

    protected ?string $description = 'Закрытые дни: выручка минус ГСМ и полигоны';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $profit = DashboardMetrics::dailyProfitByDay(14);

        return [
            'datasets' => [
                [
                    'label' => 'Прибыль',
                    'data' => $profit['profit'],
                    'type' => 'line',
                    'borderColor' => '#12b76a',
                    'backgroundColor' => 'rgba(18, 183, 106, 0.12)',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Выручка',
                    'data' => $profit['revenue'],
                    'backgroundColor' => '#465fff',
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Расходы',
                    'data' => $profit['total_expense'],
                    'backgroundColor' => '#f79009',
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $profit['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return DashboardMetrics::canBuildDailyProfit();
    }
}
