<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Resources\BunkerFillRequestResource;
use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use App\Filament\Support\DashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

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
        $requestsTrend = DashboardMetrics::fillRequestsTrend(7)['data'];
        $revenueTrend = DashboardMetrics::revenueByMonth(6)['data'];
        $bunkerBuckets = DashboardMetrics::bunkerFillBuckets()['data'];

        $bunkersCount = DashboardMetrics::safeCount(DashboardMetrics::bunkersQuery());
        $attentionBunkersCount = DashboardMetrics::safeCount(
            DashboardMetrics::bunkersQuery()?->where('fill_level', '>=', 70),
        );
        $fullBunkersCount = DashboardMetrics::safeCount(
            DashboardMetrics::bunkersQuery()?->where('fill_level', '>=', 100),
        );
        $requestsTodayCount = DashboardMetrics::safeCount(
            DashboardMetrics::fillRequestsQuery()?->whereDate('filled_at', now()->toDateString()),
        );
        $unpaidInvoicesCount = DashboardMetrics::safeCount(DashboardMetrics::unpaidInvoicesQuery());
        $unbilledWorksCount = DashboardMetrics::safeCount(DashboardMetrics::unbilledWorksQuery());
        $unpaidRevenue = DashboardMetrics::unpaidWorksRevenue();

        return [
            Stat::make('Бункеры', DashboardMetrics::formatInteger($bunkersCount))
                ->description($attentionBunkersCount . ' требуют внимания, ' . $fullBunkersCount . ' заполнены на 100%')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($attentionBunkersCount > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedMapPin)
                ->chart($bunkerBuckets)
                ->url(DashboardMetrics::hasTable('bunkers') ? BunkerResource::getUrl('index') : null),

            Stat::make('Заявки сегодня', DashboardMetrics::formatInteger($requestsTodayCount))
                ->description('Динамика за последние 7 дней')
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->color($requestsTodayCount > 0 ? 'primary' : 'gray')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->chart($requestsTrend)
                ->url(DashboardMetrics::hasTable('bunker_fill_requests') ? BunkerFillRequestResource::getUrl('index') : null),

            Stat::make('Неоплаченные счета', DashboardMetrics::formatInteger($unpaidInvoicesCount))
                ->description('Работ на сумму ' . DashboardMetrics::formatMoney($unpaidRevenue))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color($unpaidInvoicesCount > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedDocumentText)
                ->chart($revenueTrend)
                ->url(DashboardMetrics::hasTable('invoices') ? InvoiceResource::getUrl('index', ['tab' => 'unpaid']) : null),

            Stat::make('Работы без счета', DashboardMetrics::formatInteger($unbilledWorksCount))
                ->description('Нужно проверить привязку к счетам')
                ->descriptionIcon(Heroicon::OutlinedLink)
                ->color($unbilledWorksCount > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DashboardMetrics::hasTable('works') ? WorkResource::getUrl('index', ['tab' => 'unbilled']) : null),

            Stat::make('Выручка за 6 мес.', DashboardMetrics::formatMoney(array_sum($revenueTrend)))
                ->description('По загруженным работам')
                ->descriptionIcon(Heroicon::OutlinedChartBar)
                ->color('primary')
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->chart($revenueTrend)
                ->url(DashboardMetrics::hasTable('works') ? WorkResource::getUrl('index') : null),

            Stat::make('Активных контрагентов', DashboardMetrics::formatInteger($this->activeCounterpartiesCount()))
                ->description('Контрагенты с бункерами, заявками или счетами')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->icon(Heroicon::OutlinedBuildingOffice2),
        ];
    }

    private function activeCounterpartiesCount(): int
    {
        $counterpartyIds = collect();

        foreach ([
            ['bunkers', DashboardMetrics::bunkersQuery()],
            ['bunker_fill_requests', DashboardMetrics::fillRequestsQuery()],
            ['invoices', DashboardMetrics::invoicesQuery()],
        ] as [$table, $query]) {
            if (! $query instanceof Builder || ! DashboardMetrics::hasColumn($table, 'counterparty_id')) {
                continue;
            }

            try {
                $counterpartyIds = $counterpartyIds->merge(
                    $query
                        ->whereNotNull('counterparty_id')
                        ->distinct()
                        ->pluck('counterparty_id'),
                );
            } catch (\Throwable) {
                //
            }
        }

        return $counterpartyIds
            ->filter(fn ($id): bool => (int) $id > 0)
            ->unique()
            ->count();
    }
}
