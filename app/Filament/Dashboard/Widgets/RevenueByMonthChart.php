<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class RevenueByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Работы по месяцам';

    protected ?string $description = 'Сумма работ за последние 6 месяцев';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $revenue = DashboardMetrics::revenueByMonth(6);

        return [
            'datasets' => [
                [
                    'label' => 'Сумма работ',
                    'data' => $revenue['data'],
                    'backgroundColor' => '#465fff',
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $revenue['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return DashboardMetrics::hasColumns('works', ['date', 'revenue']);
    }
}
