<?php

namespace App\Filament\Support;

use App\Models\DriverWorkTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DriverWorkTimeSummary
{
    private const MONTH_NAMES = [
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь',
    ];

    public static function canBuild(): bool
    {
        return self::hasTable()
            && self::hasColumn('source')
            && self::hasColumn('source_user_id')
            && self::hasColumn('work_date')
            && self::hasColumn('duration_minutes');
    }

    /**
     * @return array{
     *     month: CarbonImmutable,
     *     month_key: string,
     *     month_label: string,
     *     rows: array<int, array{
     *         driver_name: string,
     *         driver_id: string,
     *         source: string,
     *         work_days: int,
     *         record_count: int,
     *         total_minutes: int,
     *         total_duration_formatted: string,
     *         total_hours_formatted: string
     *     }>,
     *     totals: array{
     *         driver_name: string,
     *         work_days: int,
     *         record_count: int,
     *         total_minutes: int,
     *         total_duration_formatted: string,
     *         total_hours_formatted: string
     *     },
     *     has_data: bool
     * }
     */
    public static function forMonth(CarbonInterface|string|null $month = null): array
    {
        $month = self::month($month);
        $rows = [];

        if (self::canBuild()) {
            try {
                $query = DriverWorkTime::query()
                    ->select(['source', 'source_user_id'])
                    ->selectRaw('COUNT(*) as record_count')
                    ->selectRaw('COUNT(DISTINCT work_date) as work_days')
                    ->selectRaw('COALESCE(SUM(duration_minutes), 0) as total_minutes')
                    ->whereBetween('work_date', [
                        $month->startOfMonth()->toDateString(),
                        $month->endOfMonth()->toDateString(),
                    ])
                    ->groupBy(['source', 'source_user_id']);

                if (self::hasColumn('source_user_name')) {
                    $query->selectRaw("MAX(NULLIF(TRIM(source_user_name), '')) as source_user_name");
                }

                $rows = $query
                    ->get()
                    ->map(self::formatRow(...))
                    ->sortBy([
                        ['driver_name', 'asc'],
                        ['driver_id', 'asc'],
                    ], SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                report($e);
            }
        }

        $totalMinutes = array_sum(array_column($rows, 'total_minutes'));

        return [
            'month' => $month,
            'month_key' => $month->format('Y-m'),
            'month_label' => self::monthLabel($month),
            'rows' => $rows,
            'totals' => [
                'driver_name' => 'ИТОГО',
                'work_days' => array_sum(array_column($rows, 'work_days')),
                'record_count' => array_sum(array_column($rows, 'record_count')),
                'total_minutes' => $totalMinutes,
                'total_duration_formatted' => self::formatDuration($totalMinutes),
                'total_hours_formatted' => self::formatHours($totalMinutes),
            ],
            'has_data' => $rows !== [],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function monthOptions(int $fallbackMonths = 24): array
    {
        $current = CarbonImmutable::now()->startOfMonth();
        $oldest = $current->subMonths(max(1, $fallbackMonths) - 1);
        $firstDataMonth = self::firstDataMonth();
        $options = [];

        if ($firstDataMonth && $firstDataMonth->lessThan($oldest)) {
            $oldest = $firstDataMonth;
        }

        for ($month = $current; $month->greaterThanOrEqualTo($oldest); $month = $month->subMonth()) {
            $options[$month->format('Y-m')] = self::monthLabel($month);
        }

        return $options;
    }

    public static function month(CarbonInterface|string|null $month = null): CarbonImmutable
    {
        if ($month instanceof CarbonInterface) {
            return CarbonImmutable::instance($month)->startOfMonth();
        }

        $month = trim((string) $month);

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            return CarbonImmutable::createFromFormat('Y-m-d', "{$month}-01")->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    public static function formatDuration(int $minutes): string
    {
        $minutes = max(0, $minutes);

        return sprintf('%d ч %02d мин', intdiv($minutes, 60), $minutes % 60);
    }

    public static function formatHours(int $minutes): string
    {
        return number_format(max(0, $minutes) / 60, 2, ',', ' ');
    }

    private static function firstDataMonth(): ?CarbonImmutable
    {
        if (! self::hasTable() || ! self::hasColumn('work_date')) {
            return null;
        }

        try {
            $date = DriverWorkTime::query()->min('work_date');

            return $date ? CarbonImmutable::parse($date)->startOfMonth() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     driver_name: string,
     *     driver_id: string,
     *     source: string,
     *     work_days: int,
     *     record_count: int,
     *     total_minutes: int,
     *     total_duration_formatted: string,
     *     total_hours_formatted: string
     * }
     */
    private static function formatRow(DriverWorkTime $record): array
    {
        $driverName = trim((string) $record->source_user_name);
        $totalMinutes = max(0, (int) $record->total_minutes);

        return [
            'driver_name' => $driverName !== '' ? $driverName : 'Без имени',
            'driver_id' => trim((string) $record->source_user_id),
            'source' => strtoupper(trim((string) $record->source)),
            'work_days' => max(0, (int) $record->work_days),
            'record_count' => max(0, (int) $record->record_count),
            'total_minutes' => $totalMinutes,
            'total_duration_formatted' => self::formatDuration($totalMinutes),
            'total_hours_formatted' => self::formatHours($totalMinutes),
        ];
    }

    private static function monthLabel(CarbonImmutable $month): string
    {
        $name = self::MONTH_NAMES[(int) $month->format('n')] ?? $month->format('m');

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8').' '.$month->format('Y');
    }

    private static function hasTable(): bool
    {
        try {
            return Schema::hasTable('driver_work_time');
        } catch (Throwable) {
            return false;
        }
    }

    private static function hasColumn(string $column): bool
    {
        try {
            return Schema::hasColumn('driver_work_time', $column);
        } catch (Throwable) {
            return false;
        }
    }
}
