<?php

namespace App\Filament\Support;

use App\Models\DriverSalarySetting;
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
     *         source_key: string,
     *         work_days: int,
     *         record_count: int,
     *         total_minutes: int,
     *         total_duration_formatted: string,
     *         total_hours_formatted: string,
     *         has_salary_settings: bool,
     *         hourly_rate_formatted: string,
     *         overtime_threshold_hours_formatted: string,
     *         overtime_hourly_rate_formatted: string,
     *         base_hours: float,
     *         base_hours_formatted: string,
     *         overtime_hours: float,
     *         overtime_hours_formatted: string,
     *         salary_amount: float|null,
     *         salary_formatted: string
     *     }>,
     *     totals: array{
     *         driver_name: string,
     *         work_days: int,
     *         record_count: int,
     *         total_minutes: int,
     *         total_duration_formatted: string,
     *         total_hours_formatted: string,
     *         base_hours: float,
     *         base_hours_formatted: string,
     *         overtime_hours: float,
     *         overtime_hours_formatted: string,
     *         salary_amount: float,
     *         salary_formatted: string,
     *         missing_salary_settings_count: int
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

                $salarySettings = self::salarySettingsForRows($rows);

                $rows = array_map(
                    fn (array $row): array => self::withSalary(
                        $row,
                        $salarySettings[self::driverKey($row['source_key'], $row['driver_id'])] ?? null,
                    ),
                    $rows,
                );
            } catch (Throwable $e) {
                report($e);
            }
        }

        if (($rows[0]['has_salary_settings'] ?? null) === null) {
            $rows = array_map(
                fn (array $row): array => self::withSalary($row, null),
                $rows,
            );
        }

        $totalMinutes = array_sum(array_column($rows, 'total_minutes'));
        $baseHours = array_sum(array_map(
            fn (array $row): float => (float) $row['base_hours'],
            $rows,
        ));
        $overtimeHours = array_sum(array_map(
            fn (array $row): float => (float) $row['overtime_hours'],
            $rows,
        ));
        $salaryAmount = array_sum(array_map(
            fn (array $row): float => $row['salary_amount'] === null ? 0.0 : (float) $row['salary_amount'],
            $rows,
        ));
        $missingSalarySettingsCount = count(array_filter(
            $rows,
            fn (array $row): bool => ! $row['has_salary_settings'],
        ));

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
                'base_hours' => $baseHours,
                'base_hours_formatted' => self::formatDecimal($baseHours),
                'overtime_hours' => $overtimeHours,
                'overtime_hours_formatted' => self::formatDecimal($overtimeHours),
                'salary_amount' => $salaryAmount,
                'salary_formatted' => self::formatMoney($salaryAmount),
                'missing_salary_settings_count' => $missingSalarySettingsCount,
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
     *     source_key: string,
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
        $source = trim((string) $record->source);
        $totalMinutes = max(0, (int) $record->total_minutes);

        return [
            'driver_name' => $driverName !== '' ? $driverName : 'Без имени',
            'driver_id' => trim((string) $record->source_user_id),
            'source' => strtoupper($source),
            'source_key' => $source,
            'work_days' => max(0, (int) $record->work_days),
            'record_count' => max(0, (int) $record->record_count),
            'total_minutes' => $totalMinutes,
            'total_duration_formatted' => self::formatDuration($totalMinutes),
            'total_hours_formatted' => self::formatHours($totalMinutes),
        ];
    }

    /**
     * @param  array<int, array{source_key: string, driver_id: string}>  $rows
     * @return array<string, DriverSalarySetting>
     */
    private static function salarySettingsForRows(array $rows): array
    {
        if ($rows === [] || ! self::hasSalarySettingsTable()) {
            return [];
        }

        $sources = array_values(array_unique(array_filter(array_column($rows, 'source_key'))));
        $driverIds = array_values(array_unique(array_filter(array_column($rows, 'driver_id'))));

        if ($sources === [] || $driverIds === []) {
            return [];
        }

        return DriverSalarySetting::query()
            ->whereIn('source', $sources)
            ->whereIn('source_user_id', $driverIds)
            ->get()
            ->keyBy(fn (DriverSalarySetting $setting): string => self::driverKey(
                (string) $setting->source,
                (string) $setting->source_user_id,
            ))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function withSalary(array $row, ?DriverSalarySetting $setting): array
    {
        if (! $setting) {
            return $row + [
                'has_salary_settings' => false,
                'hourly_rate_formatted' => 'Не задано',
                'overtime_threshold_hours_formatted' => 'Не задано',
                'overtime_hourly_rate_formatted' => 'Не задано',
                'base_hours' => 0.0,
                'base_hours_formatted' => 'Не задано',
                'overtime_hours' => 0.0,
                'overtime_hours_formatted' => 'Не задано',
                'salary_amount' => null,
                'salary_formatted' => 'Не задано',
            ];
        }

        $totalHours = max(0, (int) $row['total_minutes']) / 60;
        $hourlyRate = max(0.0, (float) $setting->hourly_rate);
        $overtimeThresholdHours = max(0.0, (float) $setting->overtime_threshold_hours);
        $overtimeHourlyRate = max(0.0, (float) $setting->overtime_hourly_rate);
        $baseHours = min($totalHours, $overtimeThresholdHours);
        $overtimeHours = max(0.0, $totalHours - $overtimeThresholdHours);
        $baseSalary = round($baseHours * $hourlyRate, 2);
        $overtimeSalary = round($overtimeHours * $overtimeHourlyRate, 2);
        $salaryAmount = round($baseSalary + $overtimeSalary, 2);

        return $row + [
            'has_salary_settings' => true,
            'hourly_rate_formatted' => self::formatMoney($hourlyRate),
            'overtime_threshold_hours_formatted' => self::formatDecimal($overtimeThresholdHours),
            'overtime_hourly_rate_formatted' => self::formatMoney($overtimeHourlyRate),
            'base_hours' => $baseHours,
            'base_hours_formatted' => self::formatDecimal($baseHours),
            'overtime_hours' => $overtimeHours,
            'overtime_hours_formatted' => self::formatDecimal($overtimeHours),
            'salary_amount' => $salaryAmount,
            'salary_formatted' => self::formatMoney($salaryAmount),
        ];
    }

    private static function driverKey(string $source, string $driverId): string
    {
        return $source.'|'.$driverId;
    }

    private static function formatDecimal(float $value): string
    {
        return number_format(max(0.0, $value), 2, ',', ' ');
    }

    private static function formatMoney(float $value): string
    {
        return number_format(max(0.0, $value), 2, ',', ' ').' руб.';
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

    private static function hasSalarySettingsTable(): bool
    {
        try {
            return Schema::hasTable('driver_salary_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
