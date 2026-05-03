<?php

namespace Tests\Unit;

use App\Services\BunkerFillLevelForecastService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BunkerFillLevelForecastServiceTest extends TestCase
{
    public function test_predicted_level_uses_only_intermediate_levels(): void
    {
        $service = new BunkerFillLevelForecastService;
        $day = 86400;

        $this->assertNull($service->predictedLevel([$day], (int) ($day * 0.20), 50));
        $this->assertSame(25, $service->predictedLevel([$day], (int) ($day * 0.25), 50));
        $this->assertSame(50, $service->predictedLevel([$day], (int) ($day * 0.50), 50));
        $this->assertSame(75, $service->predictedLevel([$day], (int) ($day * 0.75), 50));
        $this->assertSame(90, $service->predictedLevel([$day], $day * 2, 50));
    }

    public function test_completed_cycles_are_built_from_zero_to_hundred_events(): void
    {
        $service = new BunkerFillLevelForecastService;

        $durations = $service->completedCycleDurationsFromEvents([
            ['fill_level' => 0, 'filled_at' => CarbonImmutable::parse('2026-01-01 00:00:00')],
            ['fill_level' => 50, 'filled_at' => CarbonImmutable::parse('2026-01-02 00:00:00')],
            ['fill_level' => 100, 'filled_at' => CarbonImmutable::parse('2026-01-05 00:00:00')],
            ['fill_level' => 0, 'filled_at' => CarbonImmutable::parse('2026-01-10 00:00:00')],
            ['fill_level' => 100, 'filled_at' => CarbonImmutable::parse('2026-01-13 00:00:00')],
        ]);

        $this->assertSame([345600, 259200], $durations);
    }

    public function test_completed_cycles_are_built_from_request_execution_to_next_full_request(): void
    {
        $service = new BunkerFillLevelForecastService;

        $durations = $service->completedCycleDurationsFromEvents([
            [
                'fill_level' => 100,
                'filled_at' => CarbonImmutable::parse('2026-01-01 09:00:00'),
                'executed_at' => CarbonImmutable::parse('2026-01-01 12:00:00'),
            ],
            [
                'fill_level' => 100,
                'filled_at' => CarbonImmutable::parse('2026-01-05 12:00:00'),
                'executed_at' => CarbonImmutable::parse('2026-01-05 18:00:00'),
            ],
            [
                'fill_level' => 100,
                'filled_at' => CarbonImmutable::parse('2026-01-08 18:00:00'),
                'executed_at' => null,
            ],
        ]);

        $this->assertSame([345600, 259200], $durations);
    }
}
