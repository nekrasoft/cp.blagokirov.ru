<?php

namespace App\Filament\Pages;

use App\Filament\Support\DashboardMetrics;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MonthlyWorkSummaryPage extends Page
{
    protected string $view = 'filament.pages.monthly-work-summary-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Итоги работ';

    protected static ?string $title = 'Итоги работ';

    protected static ?string $slug = 'work-summary';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?int $navigationSort = 30;

    public ?string $month = null;

    public function mount(): void
    {
        $this->month = DashboardMetrics::monthlyWorkSummaryMonth(request()->query('month'))->format('Y-m');
    }

    public static function canAccess(): bool
    {
        return DashboardMetrics::canBuildMonthlyWorkSummary();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('currentMonth')
                ->label('Текущий месяц')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(fn (): string => static::getUrl([
                    'month' => DashboardMetrics::monthlyWorkSummaryMonth()->format('Y-m'),
                ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'summary' => DashboardMetrics::monthlyWorkSummary($this->month),
            'monthOptions' => DashboardMetrics::monthlyWorkSummaryMonthOptions(),
            'selectedMonth' => DashboardMetrics::monthlyWorkSummaryMonth($this->month)->format('Y-m'),
        ];
    }
}
