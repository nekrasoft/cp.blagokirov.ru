<?php

namespace App\Filament\Resources\WorkResource\Pages;

use App\Filament\Resources\WorkResource;
use App\Filament\Support\DashboardMetrics;
use App\Filament\Widgets\UnpaidInvoicesWarning;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWorks extends ListRecords
{
    protected static string $resource = WorkResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Все')
                ->badge(fn (): int => $this->countWorks())
                ->badgeColor('gray'),
        ];

        if (! DashboardMetrics::hasColumn('works', 'invoice_id')) {
            return $tabs;
        }

        $tabs['unbilled'] = Tab::make('Без счёта')
            ->icon('heroicon-m-link-slash')
            ->badge(fn (): int => $this->countWorks(fn (Builder $query): Builder => $query->whereNull('invoice_id')))
            ->badgeColor('warning')
            ->query(fn (Builder $query): Builder => $query->whereNull('invoice_id'));

        $tabs['billed'] = Tab::make('Со счётом')
            ->icon('heroicon-m-document-text')
            ->badge(fn (): int => $this->countWorks(fn (Builder $query): Builder => $query->whereNotNull('invoice_id')))
            ->badgeColor('success')
            ->query(fn (Builder $query): Builder => $query->whereNotNull('invoice_id'));

        if (DashboardMetrics::hasTable('invoices') && DashboardMetrics::hasColumn('invoices', 'status')) {
            $tabs['unpaid'] = Tab::make('Не оплачены')
                ->icon('heroicon-m-banknotes')
                ->badge(fn (): int => $this->countWorks($this->unpaidInvoiceScope(...)))
                ->badgeColor('danger')
                ->query($this->unpaidInvoiceScope(...));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        if (! WorkResource::canCreate()) {
            return [];
        }

        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UnpaidInvoicesWarning::class,
        ];
    }

    private function countWorks(?Closure $scope = null): int
    {
        $query = DashboardMetrics::worksQuery();

        if ($query && $scope) {
            $scope($query);
        }

        return DashboardMetrics::safeCount($query);
    }

    private function unpaidInvoiceScope(Builder $query): Builder
    {
        return $query->whereHas('invoice', function (Builder $invoiceQuery): void {
            $invoiceQuery
                ->whereIn('status', ['issued', 'pending'])
                ->orWhereNull('status');
        });
    }
}
