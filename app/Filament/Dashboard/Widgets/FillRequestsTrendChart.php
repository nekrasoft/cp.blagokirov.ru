<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class FillRequestsTrendChart extends ChartWidget
{
    protected ?string $heading = 'Заявки на вывоз';

    protected ?string $description = 'Последние 14 дней';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $trend = DashboardMetrics::fillRequestsTrend(14);

        return [
            'datasets' => [
                [
                    'label' => 'Заявки',
                    'data' => $trend['data'],
                    'borderColor' => '#465fff',
                    'backgroundColor' => 'rgba(70, 95, 255, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return DashboardMetrics::hasColumns('bunker_fill_requests', ['filled_at']);
    }
}
