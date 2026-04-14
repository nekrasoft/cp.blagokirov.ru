<?php

namespace App\Filament\Widgets;

use App\Models\CounterpartyUser;
use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;

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

        if (! static::canCheckUnpaidInvoices()) {
            return false;
        }

        return static::getUnpaidInvoicesCount((int) $user->counterparty_id) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof CounterpartyUser || (int) $user->counterparty_id <= 0 || ! static::canCheckUnpaidInvoices()) {
            return [
                'unpaidInvoicesCount' => 0,
            ];
        }

        return [
            'unpaidInvoicesCount' => static::getUnpaidInvoicesCount((int) $user->counterparty_id),
        ];
    }

    protected static function getUnpaidInvoicesCount(int $counterpartyId): int
    {
        return Invoice::query()
            ->where('counterparty_id', $counterpartyId)
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('status', ['issued', 'pending'])
                    ->orWhereNull('status');
            })
            ->count();
    }

    protected static function canCheckUnpaidInvoices(): bool
    {
        try {
            return SchemaFacade::hasTable('invoices')
                && SchemaFacade::hasColumn('invoices', 'counterparty_id')
                && SchemaFacade::hasColumn('invoices', 'status');
        } catch (Throwable) {
            return false;
        }
    }
}

