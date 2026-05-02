<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\ChartWidget;

class BunkerFillLevelChart extends ChartWidget
{
    protected ?string $heading = 'Заполненность бункеров';

    protected ?string $description = 'Распределение по уровням';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $buckets = DashboardMetrics::bunkerFillBuckets();

        return [
            'datasets' => [
                [
                    'label' => 'Бункеры',
                    'data' => $buckets['data'],
                    'backgroundColor' => [
                        '#12b76a',
                        '#0ba5ec',
                        '#f79009',
                        '#f04438',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $buckets['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return DashboardMetrics::hasColumns('bunkers', ['fill_level']);
    }
}
