<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class DashboardMetricsDriverWorkTimeMachineLoadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-09 12:00:00'));
        $this->resetDashboardMetricsCache();
        Schema::dropIfExists('driver_work_time');

        Schema::create('driver_work_time', function ($table): void {
            $table->id();
            $table->date('work_date')->index();
            $table->unsignedInteger('duration_minutes');
        });

        $this->resetDashboardMetricsCache();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('driver_work_time');
        $this->resetDashboardMetricsCache();

        parent::tearDown();
    }

    public function test_machine_load_uses_current_month_work_dates_and_two_machines(): void
    {
        DB::table('driver_work_time')->insert([
            ['work_date' => '2026-05-31', 'duration_minutes' => 999],
            ['work_date' => '2026-06-02', 'duration_minutes' => 480],
            ['work_date' => '2026-06-02', 'duration_minutes' => 420],
            ['work_date' => '2026-06-03', 'duration_minutes' => 600],
            ['work_date' => '2026-06-03', 'duration_minutes' => 480],
            ['work_date' => '2026-06-08', 'duration_minutes' => 720],
        ]);

        $load = DashboardMetrics::driverWorkTimeMachineLoadForCurrentMonth();

        $this->assertSame(3, $load['work_days']);
        $this->assertSame(2700, $load['total_minutes']);
        $this->assertSame(7.5, $load['average_hours_per_machine']);
    }

    public function test_machine_load_trend_returns_hours_per_machine_for_last_seven_days(): void
    {
        DB::table('driver_work_time')->insert([
            ['work_date' => '2026-06-02', 'duration_minutes' => 900],
            ['work_date' => '2026-06-03', 'duration_minutes' => 1080],
            ['work_date' => '2026-06-08', 'duration_minutes' => 720],
        ]);

        $trend = DashboardMetrics::driverWorkTimeMachineLoadTrend(7);

        $this->assertSame(['03.06', '04.06', '05.06', '06.06', '07.06', '08.06', '09.06'], $trend['labels']);
        $this->assertSame([9.0, 0.0, 0.0, 0.0, 0.0, 6.0, 0.0], $trend['data']);
    }

    private function resetDashboardMetricsCache(): void
    {
        $reflection = new ReflectionClass(DashboardMetrics::class);

        foreach (['tableCache', 'columnCache'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setValue(null, []);
        }
    }
}
