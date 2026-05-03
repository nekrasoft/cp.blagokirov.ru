<?php

namespace App\Services;

use App\Models\Bunker;
use App\Models\BunkerFillRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BunkerFillLevelForecastService
{
    public const AUTO_FILLED_BY = 'Автопрогноз';

    public const AUTO_SOURCE = 'auto_forecast';

    private const TIME_COLUMN = 'filled_at';

    private const FORECAST_LEVEL_RATIOS = [
        25 => 0.25,
        50 => 0.50,
        75 => 0.75,
        90 => 0.90,
    ];

    private array $columnCache = [];

    private array $tableCache = [];

    public function forecastAll(bool $dryRun = false, float $percentile = 0.5, int $minCycles = 1): array
    {
        $missing = $this->missingRequiredSchema();

        if ($missing !== []) {
            return [
                'available' => false,
                'missing' => $missing,
                'checked' => 0,
                'candidates' => 0,
                'updated' => 0,
                'skipped' => [],
                'changes' => [],
            ];
        }

        $stats = [
            'available' => true,
            'missing' => [],
            'checked' => 0,
            'candidates' => 0,
            'updated' => 0,
            'skipped' => [],
            'changes' => [],
        ];

        $now = CarbonImmutable::instance(now());
        $percentile = $this->normalizePercentile($percentile);
        $minCycles = max(1, $minCycles);

        Bunker::query()
            ->select($this->bunkerSelectColumns())
            ->orderBy('id')
            ->chunk(100, function (Collection $bunkers) use (&$stats, $dryRun, $now, $percentile, $minCycles): void {
                foreach ($bunkers as $bunker) {
                    $stats['checked']++;

                    $result = $this->forecastBunker($bunker, $now, $dryRun, $percentile, $minCycles);

                    if ($result['status'] === 'skipped') {
                        $reason = $result['reason'] ?? 'unknown';
                        $stats['skipped'][$reason] = ($stats['skipped'][$reason] ?? 0) + 1;

                        continue;
                    }

                    $stats[$result['status'] === 'updated' ? 'updated' : 'candidates']++;
                    $stats['changes'][] = $result;
                }
            });

        ksort($stats['skipped']);

        return $stats;
    }

    public function forecastBunker(Bunker $bunker, CarbonInterface $now, bool $dryRun = false, float $percentile = 0.5, int $minCycles = 1): array
    {
        $events = $this->historyForBunker((string) $bunker->getKey());

        if ($events->isEmpty()) {
            return $this->skipped('no_history');
        }

        $durations = $this->completedCycleDurationsFromEvents($events);

        if (count($durations) < max(1, $minCycles)) {
            return $this->skipped('insufficient_completed_cycles');
        }

        $resetAt = $this->currentCycleResetAt($events);

        if (! $resetAt) {
            return $this->skipped('no_open_reset');
        }

        if ($this->hasFullEventAfter($events, $resetAt)) {
            return $this->skipped('current_cycle_already_full');
        }

        $currentLevel = (int) ($bunker->fill_level ?? 0);

        if ($currentLevel >= 100) {
            return $this->skipped('current_level_full');
        }

        $elapsedSeconds = max(0, $now->getTimestamp() - $resetAt->getTimestamp());
        $targetLevel = $this->predictedLevel($durations, $elapsedSeconds, $percentile);

        if ($targetLevel === null) {
            return $this->skipped('not_due');
        }

        if ($targetLevel <= $currentLevel) {
            return $this->skipped('current_level_at_or_above_forecast');
        }

        $forecastSeconds = $this->percentile($durations, $this->normalizePercentile($percentile));
        $result = [
            'status' => $dryRun ? 'candidate' : 'updated',
            'bunker_id' => (string) $bunker->getKey(),
            'bunker_number' => $bunker->number,
            'old_level' => $currentLevel,
            'new_level' => $targetLevel,
            'cycle_count' => count($durations),
            'elapsed_seconds' => $elapsedSeconds,
            'forecast_seconds' => $forecastSeconds,
            'cycle_started_at' => $resetAt->toDateTimeString(),
        ];

        if (! $dryRun) {
            $this->updateBunkerLevel($bunker, $targetLevel, $now);
        }

        return $result;
    }

    public function predictedLevel(array $completedCycleDurations, int $elapsedSeconds, float $percentile = 0.5): ?int
    {
        $forecastSeconds = $this->percentile($completedCycleDurations, $this->normalizePercentile($percentile));

        if ($forecastSeconds <= 0) {
            return null;
        }

        $ratio = $elapsedSeconds / $forecastSeconds;
        $targetLevel = null;

        foreach (self::FORECAST_LEVEL_RATIOS as $level => $levelRatio) {
            if ($ratio >= $levelRatio) {
                $targetLevel = $level;
            }
        }

        return $targetLevel;
    }

    public function completedCycleDurationsFromEvents(iterable $events): array
    {
        $startedAt = null;
        $durations = [];

        foreach ($events as $event) {
            $level = $this->eventLevel($event);
            $eventAt = $this->eventTimestamp($event);

            if ($level <= 0) {
                $startedAt = $eventAt;

                continue;
            }

            if ($level >= 100 && $startedAt) {
                $duration = $eventAt->getTimestamp() - $startedAt->getTimestamp();

                if ($duration > 0) {
                    $durations[] = $duration;
                }

                $startedAt = null;
            }
        }

        return $durations;
    }

    private function historyForBunker(string $bunkerId): Collection
    {
        $query = BunkerFillRequest::query()
            ->select($this->requestSelectColumns())
            ->where('bunker_id', $bunkerId)
            ->whereNotNull(self::TIME_COLUMN)
            ->orderBy(self::TIME_COLUMN);

        if ($this->hasColumn('bunker_fill_requests', 'id')) {
            $query->orderBy('id');
        }

        if ($this->hasColumn('bunker_fill_requests', 'fill_level_source')) {
            $query->where(function ($query): void {
                $query
                    ->whereNull('fill_level_source')
                    ->orWhere('fill_level_source', '!=', self::AUTO_SOURCE);
            });
        }

        if ($this->hasColumn('bunker_fill_requests', 'filled_by')) {
            $query->where(function ($query): void {
                $query
                    ->whereNull('filled_by')
                    ->orWhere('filled_by', '!=', self::AUTO_FILLED_BY);
            });
        }

        return $query->get();
    }

    private function currentCycleResetAt(iterable $events): ?CarbonImmutable
    {
        $resetAt = null;

        foreach ($events as $event) {
            if ($this->eventLevel($event) <= 0) {
                $resetAt = $this->eventTimestamp($event);
            }
        }

        return $resetAt;
    }

    private function hasFullEventAfter(iterable $events, CarbonInterface $resetAt): bool
    {
        foreach ($events as $event) {
            if (
                $this->eventLevel($event) >= 100
                && $this->eventTimestamp($event)->greaterThan($resetAt)
            ) {
                return true;
            }
        }

        return false;
    }

    private function updateBunkerLevel(Bunker $bunker, int $targetLevel, CarbonInterface $now): void
    {
        $updates = [
            'fill_level' => $targetLevel,
        ];

        if ($this->hasColumn('bunkers', 'last_filled_at')) {
            $updates['last_filled_at'] = $now;
        }

        if ($this->hasColumn('bunkers', 'last_filled_by')) {
            $updates['last_filled_by'] = self::AUTO_FILLED_BY;
        }

        if ($this->hasColumn('bunkers', 'fill_level_source')) {
            $updates['fill_level_source'] = self::AUTO_SOURCE;
        }

        if ($this->hasColumn('bunkers', 'updated_at')) {
            $updates['updated_at'] = $now;
        }

        DB::table('bunkers')
            ->where('id', $bunker->getKey())
            ->update($updates);
    }

    private function percentile(array $values, float $percentile): int
    {
        $values = array_values(array_filter($values, fn (int|float $value): bool => $value > 0));

        if ($values === []) {
            return 0;
        }

        sort($values, SORT_NUMERIC);

        if (count($values) === 1) {
            return (int) round($values[0]);
        }

        $position = (count($values) - 1) * $percentile;
        $lowerIndex = (int) floor($position);
        $upperIndex = (int) ceil($position);

        if ($lowerIndex === $upperIndex) {
            return (int) round($values[$lowerIndex]);
        }

        $weight = $position - $lowerIndex;

        return (int) round($values[$lowerIndex] + (($values[$upperIndex] - $values[$lowerIndex]) * $weight));
    }

    private function normalizePercentile(float $percentile): float
    {
        if ($percentile > 1) {
            $percentile /= 100;
        }

        return min(0.99, max(0.01, $percentile));
    }

    private function bunkerSelectColumns(): array
    {
        return $this->existingColumns('bunkers', [
            'id',
            'number',
            'fill_level',
        ]);
    }

    private function requestSelectColumns(): array
    {
        return $this->existingColumns('bunker_fill_requests', [
            'id',
            'bunker_id',
            'fill_level',
            self::TIME_COLUMN,
            'filled_by',
            'fill_level_source',
        ]);
    }

    private function existingColumns(string $table, array $columns): array
    {
        return array_values(array_filter(
            array_unique($columns),
            fn (string $column): bool => $this->hasColumn($table, $column),
        ));
    }

    private function missingRequiredSchema(): array
    {
        $missing = [];

        foreach (['bunkers', 'bunker_fill_requests'] as $table) {
            if (! $this->hasTable($table)) {
                $missing[] = $table;
            }
        }

        foreach ([
            'bunkers.id',
            'bunkers.fill_level',
            'bunker_fill_requests.bunker_id',
            'bunker_fill_requests.fill_level',
            'bunker_fill_requests.'.self::TIME_COLUMN,
        ] as $tableColumn) {
            [$table, $column] = explode('.', $tableColumn, 2);

            if (! $this->hasColumn($table, $column)) {
                $missing[] = $tableColumn;
            }
        }

        return $missing;
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }

        try {
            $this->tableCache[$table] = Schema::hasTable($table);
        } catch (Throwable) {
            $this->tableCache[$table] = false;
        }

        return $this->tableCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        try {
            $this->columnCache[$key] = Schema::hasColumn($table, $column);
        } catch (Throwable) {
            $this->columnCache[$key] = false;
        }

        return $this->columnCache[$key];
    }

    private function eventLevel(mixed $event): int
    {
        return (int) data_get($event, 'fill_level', 0);
    }

    private function eventTimestamp(mixed $event): CarbonImmutable
    {
        $value = data_get($event, self::TIME_COLUMN);

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value);
        }

        return CarbonImmutable::parse((string) $value);
    }

    private function skipped(string $reason): array
    {
        return [
            'status' => 'skipped',
            'reason' => $reason,
        ];
    }
}
