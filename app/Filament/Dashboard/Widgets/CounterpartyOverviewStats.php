<?php

namespace App\Filament\Dashboard\Widgets;

use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use App\Filament\Support\DashboardMetrics;
use App\Models\CounterpartyUser;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CounterpartyOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Ваши показатели';

    protected ?string $description = 'Работы, счета и бункеры по вашему договору.';

    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return DashboardMetrics::currentCounterpartyUser() instanceof CounterpartyUser;
    }

    protected function getStats(): array
    {
        $counterpartyUser = DashboardMetrics::currentCounterpartyUser();
        $revenueTrend = DashboardMetrics::revenueByMonth(6, $counterpartyUser)['data'];
        $bunkerBuckets = DashboardMetrics::bunkerFillBuckets($counterpartyUser)['data'];

        $bunkersCount = DashboardMetrics::safeCount(DashboardMetrics::bunkersQuery($counterpartyUser));
        $attentionBunkersCount = DashboardMetrics::safeCount(
            DashboardMetrics::bunkersQuery($counterpartyUser)?->where('fill_level', '>=', 70),
        );
        $worksCount = DashboardMetrics::safeCount(DashboardMetrics::worksQuery($counterpartyUser));
        $worksRevenue = DashboardMetrics::safeSum(DashboardMetrics::worksQuery($counterpartyUser), 'revenue');
        $unpaidInvoicesCount = DashboardMetrics::safeCount(DashboardMetrics::unpaidInvoicesQuery($counterpartyUser));
        $unpaidRevenue = DashboardMetrics::unpaidWorksRevenue($counterpartyUser);

        return [
            Stat::make('Выполнено работ', DashboardMetrics::formatInteger($worksCount))
                ->description('На сумму '.DashboardMetrics::formatMoney($worksRevenue))
                ->descriptionIcon(Heroicon::OutlinedBriefcase)
                ->color('primary')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->chart($revenueTrend)
                ->url(DashboardMetrics::hasTable('works') ? WorkResource::getUrl('index') : null),

            Stat::make('Неоплаченные счета', DashboardMetrics::formatInteger($unpaidInvoicesCount))
                ->description('Работ на сумму '.DashboardMetrics::formatMoney($unpaidRevenue))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color($unpaidInvoicesCount > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(DashboardMetrics::hasTable('invoices') ? InvoiceResource::getUrl('index', ['tab' => 'unpaid'], panel: 'counterparty') : null),

            Stat::make('Бункеры', DashboardMetrics::formatInteger($bunkersCount))
                ->description($attentionBunkersCount.' требуют внимания')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($attentionBunkersCount > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedMapPin)
                ->chart($bunkerBuckets)
                ->url(DashboardMetrics::hasTable('bunkers') ? BunkerResource::getUrl('index') : null),
        ];
    }
}
