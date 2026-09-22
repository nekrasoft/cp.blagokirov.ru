<?php

namespace App\Filament\Support;

use App\Models\CounterpartyUser;
use Illuminate\Database\Eloquent\Builder;

class BunkerPickupReportScope
{
    public static function apply(Builder $query, CounterpartyUser $user): Builder
    {
        $counterpartyId = (int) $user->counterparty_id;
        if ($counterpartyId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('counterparty_id', $counterpartyId);
        $districts = DashboardMetrics::districtScopeValues($user);
        if ($districts === []) {
            return $query;
        }

        return $query->whereHas('items.request', function (Builder $requestQuery) use ($districts): void {
            $requestQuery->where(function (Builder $districtQuery) use ($districts): void {
                foreach ($districts as $district) {
                    $districtQuery->orWhereRaw('LOWER(district) LIKE ?', ['%'.mb_strtolower($district).'%']);
                }
            });
        });
    }
}
