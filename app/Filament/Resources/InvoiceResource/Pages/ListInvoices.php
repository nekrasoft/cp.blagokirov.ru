<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Support\DashboardMetrics;
use App\Filament\Widgets\UnpaidInvoicesWarning;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Все')
                ->badge(fn (): int => $this->countInvoices())
                ->badgeColor('gray'),
        ];

        if (DashboardMetrics::hasColumns('invoices', ['status'])) {
            $tabs['unpaid'] = Tab::make('К оплате')
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(fn (): int => $this->countInvoices($this->unpaidScope(...)))
                ->badgeColor('danger')
                ->query($this->unpaidScope(...));

            $tabs['paid'] = Tab::make('Оплачены')
                ->icon('heroicon-m-check-circle')
                ->badge(fn (): int => $this->countInvoices(fn (Builder $query): Builder => $query->where('status', 'paid')))
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query->where('status', 'paid'));

            $tabs['problem'] = Tab::make('Проблемные')
                ->icon('heroicon-m-x-circle')
                ->badge(fn (): int => $this->countInvoices(fn (Builder $query): Builder => $query->whereIn('status', ['failed', 'cancelled'])))
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => $query->whereIn('status', ['failed', 'cancelled']));
        }

        if (DashboardMetrics::hasColumns('invoices', ['due_date'])) {
            $tabs['overdue'] = Tab::make('Просрочены')
                ->icon('heroicon-m-clock')
                ->badge(fn (): int => $this->countInvoices($this->overdueScope(...)))
                ->badgeColor('warning')
                ->query($this->overdueScope(...));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        if (! InvoiceResource::canCreate()) {
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

    private function countInvoices(?Closure $scope = null): int
    {
        $query = DashboardMetrics::invoicesQuery();

        if ($query && $scope) {
            $scope($query);
        }

        return DashboardMetrics::safeCount($query);
    }

    private function unpaidScope(Builder $query): Builder
    {
        return $query->where(function (Builder $statusQuery): void {
            $statusQuery
                ->whereIn('status', ['issued', 'pending'])
                ->orWhereNull('status');
        });
    }

    private function overdueScope(Builder $query): Builder
    {
        $query->whereDate('due_date', '<', now()->toDateString());

        if (DashboardMetrics::hasColumn('invoices', 'status')) {
            $query->where(function (Builder $statusQuery): void {
                $statusQuery
                    ->where('status', '!=', 'paid')
                    ->orWhereNull('status');
            });
        }

        return $query;
    }
}
