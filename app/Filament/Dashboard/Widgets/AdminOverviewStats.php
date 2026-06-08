<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Pages\DailyProfitReportPage;
use App\Filament\Resources\BunkerFillRequestResource;
use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use App\Filament\Support\DashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Состояние системы';

    protected ?string $description = 'Ключевые показатели по бункерам, заявкам и биллингу.';

    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenueTrend = DashboardMetrics::revenueByMonth(6)['data'];
        $bunkerBuckets = DashboardMetrics::bunkerFillBuckets()['data'];
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $closedTo = $now->copy()
            ->subDays(DashboardMetrics::DAILY_PROFIT_CLOSED_DELAY_DAYS)
            ->startOfDay();
        $currentMonthStart = $now->copy()->startOfMonth();
        $hasClosedCurrentMonthDays = ! $closedTo->lessThan($currentMonthStart);
        $currentMonthProfitTotals = $hasClosedCurrentMonthDays
            ? DashboardMetrics::dailyProfitReport($currentMonthStart, $closedTo, 'month')['totals']
            : ['profit' => 0.0, 'avg_profit_per_work_day' => 0.0];
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();
        $previousMonthProfitTotals = DashboardMetrics::dailyProfitReport($previousMonthStart, $previousMonthEnd, 'month')['totals'];

        $bunkersCount = DashboardMetrics::safeCount(DashboardMetrics::bunkersQuery());
        $attentionBunkersCount = DashboardMetrics::safeCount(
            DashboardMetrics::bunkersQuery()?->where('fill_level', '>=', 70),
        );
        $fullBunkersCount = DashboardMetrics::safeCount(
            DashboardMetrics::bunkersQuery()?->where('fill_level', '>=', 100),
        );
        $requestsTodayCount = DashboardMetrics::safeCount(
            DashboardMetrics::fillRequestsQuery()?->whereBetween('filled_at', [
                $todayStart->toDateTimeString(),
                $todayEnd->toDateTimeString(),
            ]),
        );
        $requestsAveragePerWorkDay = DashboardMetrics::fillRequestsAveragePerWorkDay(7);
        $machineLoad = DashboardMetrics::driverWorkTimeMachineLoadForCurrentMonth();
        $machineLoadTrend = DashboardMetrics::driverWorkTimeMachineLoadTrend(7)['data'];
        $unpaidInvoicesCount = DashboardMetrics::safeCount(DashboardMetrics::unpaidInvoicesQuery());
        $unbilledWorksCount = DashboardMetrics::safeCount(DashboardMetrics::unbilledWorksQuery());
        $unpaidRevenue = DashboardMetrics::unpaidWorksRevenue();

        return [
            Stat::make('Заявки сегодня', DashboardMetrics::formatInteger($requestsTodayCount))
                ->description('Среднее за рабочий день: '.number_format($requestsAveragePerWorkDay, 1, ',', ' '))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color($requestsTodayCount > 0 ? 'primary' : 'gray')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(DashboardMetrics::hasTable('bunker_fill_requests') ? BunkerFillRequestResource::getUrl('index') : null),

            Stat::make('Загруженность машины', number_format($machineLoad['average_hours_per_machine'], 1, ',', ' ').' ч')
                ->description('За текущий месяц, рабочих дней: '.$machineLoad['work_days'])
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color($machineLoad['average_hours_per_machine'] > 0 ? 'primary' : 'gray')
                ->icon(Heroicon::OutlinedCalculator)
                ->chart($machineLoadTrend),

            Stat::make('Прибыль текущего месяца', DashboardMetrics::formatMoney($currentMonthProfitTotals['profit']))
                ->description('Средняя прибыль в день: '.DashboardMetrics::formatMoney($currentMonthProfitTotals['avg_profit_per_work_day']))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color($currentMonthProfitTotals['profit'] >= 0 ? 'success' : 'danger')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(DashboardMetrics::canBuildDailyProfit() && $hasClosedCurrentMonthDays ? DailyProfitReportPage::getUrl([
                    'date_from' => $currentMonthStart->toDateString(),
                    'date_to' => $closedTo->toDateString(),
                    'group_by' => 'month',
                ]) : null),

            Stat::make('Прибыль прошлого месяца', DashboardMetrics::formatMoney($previousMonthProfitTotals['profit']))
                ->description('Средняя прибыль в день: '.DashboardMetrics::formatMoney($previousMonthProfitTotals['avg_profit_per_work_day']))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color($previousMonthProfitTotals['profit'] >= 0 ? 'success' : 'danger')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(DashboardMetrics::canBuildDailyProfit() ? DailyProfitReportPage::getUrl([
                    'date_from' => $previousMonthStart->toDateString(),
                    'date_to' => $previousMonthEnd->toDateString(),
                    'group_by' => 'month',
                ]) : null),

            Stat::make('Неоплаченные счета', DashboardMetrics::formatInteger($unpaidInvoicesCount))
                ->description('Работ на сумму '.DashboardMetrics::formatMoney($unpaidRevenue))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color($unpaidInvoicesCount > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedDocumentText)
                ->chart($revenueTrend)
                ->url(DashboardMetrics::hasTable('invoices') ? InvoiceResource::getUrl('index', ['tab' => 'unpaid']) : null),

            // Stat::make('Выручка за 6 мес.', DashboardMetrics::formatMoney(array_sum($revenueTrend)))
            //     ->description('По загруженным работам')
            //     ->descriptionIcon(Heroicon::OutlinedChartBar)
            //     ->color('primary')
            //     ->icon(Heroicon::OutlinedPresentationChartLine)
            //     ->chart($revenueTrend)
            //     ->url(DashboardMetrics::hasTable('works') ? WorkResource::getUrl('index') : null),

            Stat::make('Бункеры', DashboardMetrics::formatInteger($bunkersCount))
                ->description($attentionBunkersCount.' требуют внимания, '.$fullBunkersCount.' заполнены на 100%')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($attentionBunkersCount > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedMapPin)
                ->chart($bunkerBuckets)
                ->url(DashboardMetrics::hasTable('bunkers') ? BunkerResource::getUrl('index') : null),

            Stat::make('Работы без счета', DashboardMetrics::formatInteger($unbilledWorksCount))
                ->description('Нужно проверить привязку к счетам')
                ->descriptionIcon(Heroicon::OutlinedLink)
                ->color($unbilledWorksCount > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DashboardMetrics::hasTable('works') ? WorkResource::getUrl('index', ['tab' => 'unbilled']) : null),

        ];
    }
}
