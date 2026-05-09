<?php

namespace App\Filament\Counterparty\Dashboard;

use App\Filament\Dashboard\Widgets\AttentionBunkersTable;
use App\Filament\Dashboard\Widgets\BunkerFillLevelChart;
use App\Filament\Dashboard\Widgets\CounterpartyOverviewStats;
use App\Filament\Dashboard\Widgets\CounterpartyRecentWorksTable;
use App\Filament\Dashboard\Widgets\CounterpartyUnpaidInvoicesTable;
use App\Filament\Dashboard\Widgets\FillRequestsTrendChart;
use App\Filament\Dashboard\Widgets\RevenueByMonthChart;
use App\Filament\Resources\BunkerFillRequestResource;
use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use App\Filament\Support\DashboardMetrics;
use App\Filament\Widgets\UnpaidInvoicesWarning;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class CounterpartyDashboard extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Обзор';

    protected static ?string $title = 'Обзор';

    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('works')
                ->label('Работы')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(fn (): string => WorkResource::getUrl('index', panel: 'counterparty'))
                ->visible(fn (): bool => DashboardMetrics::hasTable('works')),

            Action::make('unpaidInvoices')
                ->label('Счета к оплате')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('danger')
                ->url(fn (): string => InvoiceResource::getUrl('index', ['tab' => 'unpaid'], panel: 'counterparty'))
                ->visible(fn (): bool => DashboardMetrics::hasColumns('invoices', ['status'])),

            Action::make('bunkers')
                ->label('Бункеры')
                ->icon(Heroicon::OutlinedMapPin)
                ->color('gray')
                ->url(fn (): string => BunkerResource::getUrl('index', ['tab' => 'attention'], panel: 'counterparty'))
                ->visible(fn (): bool => DashboardMetrics::hasTable('bunkers')),

            Action::make('requests')
                ->label('История заявок')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('gray')
                ->url(fn (): string => BunkerFillRequestResource::getUrl('index', ['tab' => 'today'], panel: 'counterparty'))
                ->visible(fn (): bool => DashboardMetrics::hasTable('bunker_fill_requests')),

            Action::make('map')
                ->label('Карта')
                ->icon(Heroicon::OutlinedMap)
                ->color('gray')
                ->url(fn (): string => route('billing.sso.map'), shouldOpenInNewTab: true),
        ];
    }

    public function getWidgets(): array
    {
        return [
            UnpaidInvoicesWarning::class,
            CounterpartyOverviewStats::class,
            FillRequestsTrendChart::class,
            BunkerFillLevelChart::class,
            RevenueByMonthChart::class,
            AttentionBunkersTable::class,
            CounterpartyRecentWorksTable::class,
            CounterpartyUnpaidInvoicesTable::class,
        ];
    }
}
