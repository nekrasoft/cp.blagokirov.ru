<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class DashboardMetricsFillRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-08 12:00:00'));
        $this->resetDashboardMetricsCache();
        Schema::dropIfExists('driver_work_time');
        Schema::dropIfExists('bunker_fill_requests');

        Schema::create('bunker_fill_requests', function ($table): void {
            $table->id();
            $table->dateTime('filled_at')->nullable()->index();
        });

        Schema::create('driver_work_time', function ($table): void {
            $table->id();
            $table->date('work_date')->index();
        });

        $this->resetDashboardMetricsCache();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('driver_work_time');
        Schema::dropIfExists('bunker_fill_requests');
        $this->resetDashboardMetricsCache();

        parent::tearDown();
    }

    public function test_fill_requests_average_per_work_day_uses_driver_work_dates(): void
    {
        DB::table('driver_work_time')->insert([
            ['work_date' => '2026-06-02'],
            ['work_date' => '2026-06-02'],
            ['work_date' => '2026-06-05'],
            ['work_date' => '2026-06-08'],
        ]);

        DB::table('bunker_fill_requests')->insert([
            ['filled_at' => '2026-06-02 09:00:00'],
            ['filled_at' => '2026-06-02 10:00:00'],
            ['filled_at' => '2026-06-03 11:00:00'],
            ['filled_at' => '2026-06-05 12:00:00'],
            ['filled_at' => '2026-06-05 13:00:00'],
            ['filled_at' => '2026-06-06 09:00:00'],
            ['filled_at' => '2026-06-07 09:00:00'],
            ['filled_at' => '2026-06-08 09:00:00'],
            ['filled_at' => '2026-06-08 10:00:00'],
            ['filled_at' => '2026-06-08 11:00:00'],
            ['filled_at' => '2026-06-08 12:00:00'],
            ['filled_at' => '2026-06-08 13:00:00'],
        ]);

        $this->assertSame(3.0, DashboardMetrics::fillRequestsAveragePerWorkDay(7));
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
