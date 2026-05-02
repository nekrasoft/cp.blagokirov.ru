<?php

namespace App\Filament\Support;

use App\Models\Bunker;
use App\Models\BunkerFillRequest;
use App\Models\CounterpartyUser;
use App\Models\Invoice;
use App\Models\Work;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;

final class DashboardMetrics
{
    /** @var array<string, bool> */
    private static array $tableCache = [];

    /** @var array<string, bool> */
    private static array $columnCache = [];

    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tableCache)) {
            return self::$tableCache[$table];
        }

        try {
            return self::$tableCache[$table] = SchemaFacade::hasTable($table);
        } catch (Throwable) {
            return self::$tableCache[$table] = false;
        }
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            return self::$columnCache[$key] = SchemaFacade::hasColumn($table, $column);
        } catch (Throwable) {
            return self::$columnCache[$key] = false;
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    public static function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! self::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    public static function currentCounterpartyUser(): ?CounterpartyUser
    {
        $user = Filament::auth()->user();

        return $user instanceof CounterpartyUser ? $user : null;
    }

    public static function currentCounterpartyId(): ?int
    {
        $counterpartyUser = self::currentCounterpartyUser();
        $counterpartyId = (int) ($counterpartyUser?->counterparty_id ?? 0);

        return $counterpartyId > 0 ? $counterpartyId : null;
    }

    public static function bunkersQuery(?CounterpartyUser $counterpartyUser = null): ?Builder
    {
        if (! self::hasTable('bunkers')) {
            return null;
        }

        $query = Bunker::query();
        $counterpartyUser ??= self::currentCounterpartyUser();

        if (! $counterpartyUser) {
            return $query;
        }

        if (! self::hasColumn('bunkers', 'counterparty_id')) {
            return $query->whereRaw('1 = 0');
        }

        $counterpartyId = (int) $counterpartyUser->counterparty_id;

        if ($counterpartyId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('counterparty_id', $counterpartyId);

        if (self::hasColumn('bunkers', 'district')) {
            $districts = self::districtScopeValues($counterpartyUser);

            if ($districts !== []) {
                $query->whereIn('district', $districts);
            }
        }

        return $query;
    }

    public static function fillRequestsQuery(?CounterpartyUser $counterpartyUser = null): ?Builder
    {
        if (! self::hasTable('bunker_fill_requests')) {
            return null;
        }

        $query = BunkerFillRequest::query();
        $counterpartyUser ??= self::currentCounterpartyUser();

        if (! $counterpartyUser) {
            return $query;
        }

        if (! self::hasColumn('bunker_fill_requests', 'counterparty_id')) {
            return $query->whereRaw('1 = 0');
        }

        $counterpartyId = (int) $counterpartyUser->counterparty_id;

        return $counterpartyId > 0
            ? $query->where('counterparty_id', $counterpartyId)
            : $query->whereRaw('1 = 0');
    }

    public static function invoicesQuery(?CounterpartyUser $counterpartyUser = null): ?Builder
    {
        if (! self::hasTable('invoices')) {
            return null;
        }

        $query = Invoice::query();
        $counterpartyUser ??= self::currentCounterpartyUser();

        if (! $counterpartyUser) {
            return $query;
        }

        if (! self::hasColumn('invoices', 'counterparty_id')) {
            return $query->whereRaw('1 = 0');
        }

        $counterpartyId = (int) $counterpartyUser->counterparty_id;

        return $counterpartyId > 0
            ? $query->where('counterparty_id', $counterpartyId)
            : $query->whereRaw('1 = 0');
    }

    public static function worksQuery(?CounterpartyUser $counterpartyUser = null): ?Builder
    {
        if (! self::hasTable('works')) {
            return null;
        }

        $query = Work::query();
        $counterpartyUser ??= self::currentCounterpartyUser();

        if (! $counterpartyUser) {
            return $query;
        }

        $counterpartyId = (int) $counterpartyUser->counterparty_id;

        if ($counterpartyId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $hasInvoiceScope = self::hasColumns('works', ['invoice_id'])
            && self::hasTable('invoices')
            && self::hasColumn('invoices', 'counterparty_id');
        $counterpartyNames = self::counterpartyNames($counterpartyUser);
        $hasNameScope = self::hasColumn('works', 'counterparty_name') && ($counterpartyNames !== []);

        if (! $hasInvoiceScope && ! $hasNameScope) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scopeQuery) use ($hasInvoiceScope, $hasNameScope, $counterpartyId, $counterpartyNames): void {
            if ($hasInvoiceScope) {
                $scopeQuery->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery->where('counterparty_id', $counterpartyId));
            }

            if ($hasNameScope) {
                $nameScope = function (Builder $nameQuery) use ($counterpartyNames): void {
                    if (self::hasColumn('works', 'invoice_id')) {
                        $nameQuery->whereNull('invoice_id');
                    }

                    $nameQuery->whereIn('counterparty_name', $counterpartyNames);
                };

                if ($hasInvoiceScope) {
                    $scopeQuery->orWhere($nameScope);
                } else {
                    $scopeQuery->where($nameScope);
                }
            }
        });
    }

    public static function safeCount(?Builder $query): int
    {
        if (! $query) {
            return 0;
        }

        try {
            return (int) $query->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public static function safeSum(?Builder $query, string $column): float
    {
        if (! $query) {
            return 0.0;
        }

        try {
            return (float) $query->sum($column);
        } catch (Throwable) {
            return 0.0;
        }
    }

    public static function unpaidInvoicesQuery(?CounterpartyUser $counterpartyUser = null): ?Builder
    {
        $query = self::invoicesQuery($counterpartyUser);

        if (! $query || ! self::hasColumn('invoices', 'status')) {
            return $query;
        }

        return $query->where(function (Builder $statusQuery): void {
            $statusQuery
                ->whereIn('status', ['issued', 'pending'])
                ->orWhereNull('status');
        });
    }

    public static function unbilledWorksQuery(?CounterpartyUser $counterpartyUser = null): ?Builder
    {
        $query = self::worksQuery($counterpartyUser);

        if (! $query || ! self::hasColumn('works', 'invoice_id')) {
            return $query?->whereRaw('1 = 0');
        }

        return $query->whereNull('invoice_id');
    }

    public static function unpaidWorksRevenue(?CounterpartyUser $counterpartyUser = null): float
    {
        if (! self::hasColumns('works', ['invoice_id', 'revenue']) || ! self::hasColumn('invoices', 'status')) {
            return 0.0;
        }

        $query = self::worksQuery($counterpartyUser);

        if (! $query) {
            return 0.0;
        }

        $query->whereHas('invoice', function (Builder $invoiceQuery): void {
            $invoiceQuery
                ->whereIn('status', ['issued', 'pending'])
                ->orWhereNull('status');
        });

        return self::safeSum($query, 'revenue');
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function fillRequestsTrend(int $days = 14, ?CounterpartyUser $counterpartyUser = null): array
    {
        return self::dateCountTrend(
            query: self::fillRequestsQuery($counterpartyUser),
            table: 'bunker_fill_requests',
            column: 'filled_at',
            days: $days,
        );
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public static function revenueByMonth(int $months = 6, ?CounterpartyUser $counterpartyUser = null): array
    {
        $labels = [];
        $buckets = [];
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);
        $end = CarbonImmutable::now()->endOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $date = $start->addMonths($i);
            $key = $date->format('Y-m');
            $labels[] = $date->format('m.Y');
            $buckets[$key] = 0.0;
        }

        if (! self::hasColumns('works', ['date', 'revenue'])) {
            return ['labels' => $labels, 'data' => array_values($buckets)];
        }

        $query = self::worksQuery($counterpartyUser);

        if (! $query) {
            return ['labels' => $labels, 'data' => array_values($buckets)];
        }

        try {
            $query
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get(['date', 'revenue'])
                ->each(function (Work $work) use (&$buckets): void {
                    $date = $work->date;

                    if (! $date) {
                        return;
                    }

                    $key = CarbonImmutable::parse($date)->format('Y-m');

                    if (array_key_exists($key, $buckets)) {
                        $buckets[$key] += (float) $work->revenue;
                    }
                });
        } catch (Throwable) {
            //
        }

        return ['labels' => $labels, 'data' => array_values($buckets)];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function bunkerFillBuckets(?CounterpartyUser $counterpartyUser = null): array
    {
        $labels = ['0-49%', '50-69%', '70-99%', '100%'];
        $buckets = [0, 0, 0, 0];

        if (! self::hasColumn('bunkers', 'fill_level')) {
            return ['labels' => $labels, 'data' => $buckets];
        }

        $query = self::bunkersQuery($counterpartyUser);

        if (! $query) {
            return ['labels' => $labels, 'data' => $buckets];
        }

        try {
            $query->get(['fill_level'])->each(function (Bunker $bunker) use (&$buckets): void {
                $fillLevel = (int) ($bunker->fill_level ?? 0);

                if ($fillLevel >= 100) {
                    $buckets[3]++;
                } elseif ($fillLevel >= 70) {
                    $buckets[2]++;
                } elseif ($fillLevel >= 50) {
                    $buckets[1]++;
                } else {
                    $buckets[0]++;
                }
            });
        } catch (Throwable) {
            //
        }

        return ['labels' => $labels, 'data' => $buckets];
    }

    public static function formatInteger(int|float $value): string
    {
        return number_format((float) $value, 0, ',', ' ');
    }

    public static function formatMoney(int|float $value): string
    {
        return number_format((float) $value, 0, ',', ' ') . ' ₽';
    }

    /**
     * @return array<int, string>
     */
    public static function counterpartyNames(CounterpartyUser $counterpartyUser): array
    {
        $names = [
            $counterpartyUser->counterparty?->short_name,
            $counterpartyUser->counterparty?->name,
        ];

        $normalized = [];

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name !== '') {
                $normalized[$name] = true;
            }
        }

        return array_keys($normalized);
    }

    /**
     * @return array<int, string>
     */
    public static function districtScopeValues(CounterpartyUser $counterpartyUser): array
    {
        $scope = trim((string) $counterpartyUser->district_scope);

        if ($scope === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('trim', preg_split('/[;,\n]+/u', $scope) ?: []),
            fn (string $district): bool => $district !== '',
        )));
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private static function dateCountTrend(?Builder $query, string $table, string $column, int $days): array
    {
        $labels = [];
        $buckets = [];
        $start = CarbonImmutable::now()->startOfDay()->subDays($days - 1);
        $end = CarbonImmutable::now()->endOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->format('d.m');
            $buckets[$key] = 0;
        }

        if (! $query || ! self::hasColumn($table, $column)) {
            return ['labels' => $labels, 'data' => array_values($buckets)];
        }

        try {
            $query
                ->whereBetween($column, [$start->toDateTimeString(), $end->toDateTimeString()])
                ->get([$column])
                ->each(function ($record) use (&$buckets, $column): void {
                    $value = $record->{$column};

                    if (! $value) {
                        return;
                    }

                    $key = CarbonImmutable::parse($value)->toDateString();

                    if (array_key_exists($key, $buckets)) {
                        $buckets[$key]++;
                    }
                });
        } catch (Throwable) {
            //
        }

        return ['labels' => $labels, 'data' => array_values($buckets)];
    }
}
