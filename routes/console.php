<?php

use App\Services\BunkerFillLevelForecastService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bunkers:forecast-fill-levels {--dry-run : Показать прогноз без записи в БД} {--percentile=50 : Перцентиль длительности цикла, 50 = медиана} {--min-cycles=1 : Минимум завершенных циклов 0-100 по бункеру}', function (BunkerFillLevelForecastService $service): int {
    $stats = $service->forecastAll(
        dryRun: (bool) $this->option('dry-run'),
        percentile: (float) $this->option('percentile'),
        minCycles: (int) $this->option('min-cycles'),
    );

    if (! $stats['available']) {
        $this->warn('Прогноз заполненности пропущен: нет нужной схемы ('.implode(', ', $stats['missing']).').');

        return self::SUCCESS;
    }

    $this->info(sprintf(
        'Проверено: %d, кандидатов: %d, обновлено: %d.',
        $stats['checked'],
        $stats['candidates'],
        $stats['updated'],
    ));

    if ($stats['changes'] !== []) {
        $this->table(
            ['Бункер', '№', 'Было', 'Стало', 'Циклов'],
            array_map(
                fn (array $change): array => [
                    $change['bunker_id'],
                    $change['bunker_number'],
                    $change['old_level'].'%',
                    $change['new_level'].'%',
                    $change['cycle_count'],
                ],
                array_slice($stats['changes'], 0, 50),
            ),
        );
    }

    foreach ($stats['skipped'] as $reason => $count) {
        $this->line($reason.': '.$count);
    }

    return self::SUCCESS;
})->purpose('Прогнозирует промежуточную заполненность бункеров по их собственной истории');

Schedule::command('bunkers:forecast-fill-levels')
    ->hourly();
