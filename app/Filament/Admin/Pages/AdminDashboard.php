<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Dashboard\Widgets\AdminOverviewStats;
use App\Filament\Dashboard\Widgets\AttentionBunkersTable;
use App\Filament\Dashboard\Widgets\BunkerFillLevelChart;
use App\Filament\Dashboard\Widgets\DailyProfitChart;
use App\Filament\Dashboard\Widgets\FillRequestsTrendChart;
use App\Filament\Dashboard\Widgets\MonthlyWorkSummaryWidget;
use App\Filament\Dashboard\Widgets\RecentFillRequestsTable;
use App\Filament\Dashboard\Widgets\RevenueByMonthChart;
use App\Filament\Dashboard\Widgets\UnpaidInvoicesTable;
use App\Filament\Resources\BunkerFillRequestResource;
use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use App\Filament\Support\DashboardMetrics;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class AdminDashboard extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Обзор';

    protected static string|\UnitEnum|null $navigationGroup = 'Панель';

    protected static ?string $title = 'Обзор';

    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBunker')
                ->label('Новый бункер')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => BunkerResource::getUrl('create'))
                ->visible(fn (): bool => BunkerResource::canCreate()),

            Action::make('createInvoice')
                ->label('Новый счёт')
                ->icon(Heroicon::OutlinedDocumentPlus)
                ->color('gray')
                ->url(fn (): string => InvoiceResource::getUrl('create'))
                ->visible(fn (): bool => InvoiceResource::canCreate()),

            Action::make('attentionBunkers')
                ->label('Бункеры 70%+')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning')
                ->url(fn (): string => BunkerResource::getUrl('index', ['tab' => 'attention']))
                ->visible(fn (): bool => DashboardMetrics::hasColumns('bunkers', ['fill_level'])),

            Action::make('unpaidInvoices')
                ->label('Неоплаченные счета')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('danger')
                ->url(fn (): string => InvoiceResource::getUrl('index', ['tab' => 'unpaid']))
                ->visible(fn (): bool => DashboardMetrics::hasColumns('invoices', ['status'])),

            Action::make('recentRequests')
                ->label('Последние заявки')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('gray')
                ->url(fn (): string => BunkerFillRequestResource::getUrl('index', ['tab' => 'today']))
                ->visible(fn (): bool => DashboardMetrics::hasTable('bunker_fill_requests')),

            Action::make('unbilledWorks')
                ->label('Работы без счёта')
                ->icon(Heroicon::OutlinedLink)
                ->color('gray')
                ->url(fn (): string => WorkResource::getUrl('index', ['tab' => 'unbilled']))
                ->visible(fn (): bool => DashboardMetrics::hasColumns('works', ['invoice_id'])),
        ];
    }

    public function getWidgets(): array
    {
        return [
            AdminOverviewStats::class,
            MonthlyWorkSummaryWidget::class,
            FillRequestsTrendChart::class,
            BunkerFillLevelChart::class,
            RevenueByMonthChart::class,
            DailyProfitChart::class,
            AttentionBunkersTable::class,
            RecentFillRequestsTable::class,
            UnpaidInvoicesTable::class,
        ];
    }
}
