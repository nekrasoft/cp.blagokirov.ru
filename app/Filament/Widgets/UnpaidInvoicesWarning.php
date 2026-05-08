<?php

namespace App\Filament\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Models\CounterpartyUser;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class UnpaidInvoicesWarning extends Widget
{
    protected string $view = 'filament.widgets.unpaid-invoices-warning';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof CounterpartyUser || (int) $user->counterparty_id <= 0) {
            return false;
        }

        return static::getUnpaidInvoicesCount($user) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof CounterpartyUser || (int) $user->counterparty_id <= 0) {
            return [
                'unpaidInvoicesCount' => 0,
            ];
        }

        return [
            'unpaidInvoicesCount' => static::getUnpaidInvoicesCount($user),
        ];
    }

    protected static function getUnpaidInvoicesCount(CounterpartyUser $counterpartyUser): int
    {
        return DashboardMetrics::safeCount(DashboardMetrics::unpaidInvoicesQuery($counterpartyUser));
    }
}
