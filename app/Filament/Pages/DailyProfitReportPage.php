<?php

namespace App\Filament\Pages;

use App\Filament\Support\DashboardMetrics;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DailyProfitReportPage extends Page
{
    protected string $view = 'filament.pages.daily-profit-report-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Прибыль';

    protected static ?string $title = 'Прибыль';

    protected static ?string $slug = 'profit-report';

    protected static string|UnitEnum|null $navigationGroup = 'Биллинг';

    protected static ?int $navigationSort = 35;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $groupBy = 'day';

    public function mount(): void
    {
        $defaultTo = CarbonImmutable::now()
            ->subDays(DashboardMetrics::DAILY_PROFIT_CLOSED_DELAY_DAYS)
            ->toDateString();
        $defaultFrom = CarbonImmutable::parse($defaultTo)->startOfMonth()->toDateString();

        $this->dateFrom = $this->dateQueryValue('date_from', $defaultFrom);
        $this->dateTo = $this->dateQueryValue('date_to', $defaultTo);
        $this->groupBy = request()->query('group_by') === 'month' ? 'month' : 'day';
    }

    public static function canAccess(): bool
    {
        return DashboardMetrics::canBuildDailyProfit();
    }

    protected function getHeaderActions(): array
    {
        $closedTo = CarbonImmutable::now()->subDays(DashboardMetrics::DAILY_PROFIT_CLOSED_DELAY_DAYS);
        $currentMonthFrom = $closedTo->startOfMonth()->toDateString();
        $last30DaysFrom = $closedTo->subDays(29)->toDateString();
        $monthsFrom = $closedTo->subMonths(5)->startOfMonth()->toDateString();
        $closedToDate = $closedTo->toDateString();

        return [
            Action::make('currentMonth')
                ->label('Текущий месяц')
                ->icon(Heroicon::OutlinedCalendar)
                ->color(fn (): string => $this->periodMatches($currentMonthFrom, $closedToDate, 'day') ? 'primary' : 'gray')
                ->url(fn (): string => static::getUrl([
                    'date_from' => $currentMonthFrom,
                    'date_to' => $closedToDate,
                    'group_by' => 'day',
                ])),

            Action::make('last30Days')
                ->label('30 дней')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color(fn (): string => $this->periodMatches($last30DaysFrom, $closedToDate, 'day') ? 'primary' : 'gray')
                ->url(fn (): string => static::getUrl([
                    'date_from' => $last30DaysFrom,
                    'date_to' => $closedToDate,
                    'group_by' => 'day',
                ])),

            Action::make('months')
                ->label('По месяцам')
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->color(fn (): string => $this->periodMatches($monthsFrom, $closedToDate, 'month') ? 'primary' : 'gray')
                ->url(fn (): string => static::getUrl([
                    'date_from' => $monthsFrom,
                    'date_to' => $closedToDate,
                    'group_by' => 'month',
                ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'report' => DashboardMetrics::dailyProfitReport($this->dateFrom, $this->dateTo, $this->groupBy),
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'groupBy' => $this->groupBy,
            'groupOptions' => [
                'day' => 'По дням',
                'month' => 'По месяцам',
            ],
        ];
    }

    private function dateQueryValue(string $key, string $fallback): string
    {
        $value = trim((string) request()->query($key));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    private function periodMatches(string $dateFrom, string $dateTo, string $groupBy): bool
    {
        return $this->dateFrom === $dateFrom
            && $this->dateTo === $dateTo
            && $this->groupBy === $groupBy;
    }
}
