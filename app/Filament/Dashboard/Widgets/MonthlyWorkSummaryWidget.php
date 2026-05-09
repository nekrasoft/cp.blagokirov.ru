<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Pages\MonthlyWorkSummaryPage;
use App\Filament\Support\DashboardMetrics;
use Filament\Widgets\Widget;

class MonthlyWorkSummaryWidget extends Widget
{
    protected string $view = 'filament.dashboard.widgets.monthly-work-summary-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return DashboardMetrics::canBuildMonthlyWorkSummary();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $summary = DashboardMetrics::monthlyWorkSummary();

        return [
            'summary' => $summary,
            'reportUrl' => MonthlyWorkSummaryPage::getUrl(['month' => $summary['month_key']]),
        ];
    }
}
