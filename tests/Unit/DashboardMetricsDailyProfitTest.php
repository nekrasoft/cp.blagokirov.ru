<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class DashboardMetricsDailyProfitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-04 12:00:00'));
        $this->resetDashboardMetricsCache();
        Schema::dropIfExists('daily_expense_allocations');
        Schema::dropIfExists('driver_work_time');
        Schema::dropIfExists('works');

        Schema::create('works', function ($table): void {
            $table->id();
            $table->date('date')->nullable();
            $table->decimal('revenue', 14, 2)->nullable();
        });

        Schema::create('daily_expense_allocations', function ($table): void {
            $table->id();
            $table->date('expense_date');
            $table->string('expense_code', 16);
            $table->decimal('amount', 14, 2);
        });

        Schema::create('driver_work_time', function ($table): void {
            $table->id();
            $table->date('work_date');
        });

        $this->resetDashboardMetricsCache();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('daily_expense_allocations');
        Schema::dropIfExists('driver_work_time');
        Schema::dropIfExists('works');
        $this->resetDashboardMetricsCache();

        parent::tearDown();
    }

    public function test_daily_profit_aggregates_closed_days_only(): void
    {
        DB::table('works')->insert([
            ['date' => '2026-06-01', 'revenue' => 50000],
            ['date' => '2026-06-02', 'revenue' => 20000],
            ['date' => '2026-06-03', 'revenue' => 999999],
            ['date' => '2026-06-04', 'revenue' => 777777],
        ]);

        DB::table('daily_expense_allocations')->insert([
            ['expense_date' => '2026-06-01', 'expense_code' => '183', 'amount' => 15000],
            ['expense_date' => '2026-06-01', 'expense_code' => '185', 'amount' => 10000],
            ['expense_date' => '2026-06-02', 'expense_code' => '183', 'amount' => 15000],
            ['expense_date' => '2026-06-02', 'expense_code' => '182', 'amount' => 7000],
            ['expense_date' => '2026-06-03', 'expense_code' => '185', 'amount' => 999999],
            ['expense_date' => '2026-06-04', 'expense_code' => '185', 'amount' => 777777],
        ]);

        $profit = DashboardMetrics::dailyProfitByDay(2);

        $this->assertSame(['02.06', '03.06'], $profit['labels']);
        $this->assertSame([20000.0, 999999.0], $profit['revenue']);
        $this->assertSame([15000.0, 0.0], $profit['fuel_expense']);
        $this->assertSame([0.0, 999999.0], $profit['landfill_expense']);
        $this->assertSame([15000.0, 999999.0], $profit['total_expense']);
        $this->assertSame([5000.0, 0.0], $profit['profit']);
    }

    public function test_daily_profit_report_groups_by_month(): void
    {
        DB::table('works')->insert([
            ['date' => '2026-05-31', 'revenue' => 10000],
            ['date' => '2026-06-01', 'revenue' => 50000],
            ['date' => '2026-06-02', 'revenue' => 20000],
        ]);

        DB::table('daily_expense_allocations')->insert([
            ['expense_date' => '2026-05-31', 'expense_code' => '185', 'amount' => 2000],
            ['expense_date' => '2026-06-01', 'expense_code' => '183', 'amount' => 15000],
            ['expense_date' => '2026-06-02', 'expense_code' => '183', 'amount' => 15000],
            ['expense_date' => '2026-06-02', 'expense_code' => '185', 'amount' => 10000],
        ]);

        DB::table('driver_work_time')->insert([
            ['work_date' => '2026-05-31'],
            ['work_date' => '2026-06-01'],
            ['work_date' => '2026-06-01'],
            ['work_date' => '2026-06-02'],
        ]);

        $report = DashboardMetrics::dailyProfitReport('2026-05-01', '2026-06-30', 'month');

        $this->assertSame(['05.2026', '06.2026'], $report['labels']);
        $this->assertSame([10000.0, 70000.0], $report['revenue']);
        $this->assertSame([0.0, 30000.0], $report['fuel_expense']);
        $this->assertSame([2000.0, 10000.0], $report['landfill_expense']);
        $this->assertSame([8000.0, 30000.0], $report['profit']);
        $this->assertSame([25, 2], array_column($report['rows'], 'work_days'));
        $this->assertSame([320.0, 15000.0], array_column($report['rows'], 'avg_profit_per_work_day'));
        $this->assertSame(38000.0, $report['totals']['profit']);
        $this->assertSame(27, $report['totals']['work_days']);
        $this->assertEqualsWithDelta(1407.41, $report['totals']['avg_profit_per_work_day'], 0.01);
    }

    public function test_daily_profit_report_defaults_to_last_seven_closed_days_and_can_sort_days_desc(): void
    {
        $report = DashboardMetrics::dailyProfitReport(sortDaysDesc: true);

        $this->assertSame('2026-05-28', $report['date_from']);
        $this->assertSame('2026-06-03', $report['date_to']);
        $this->assertSame(['03.06', '02.06', '01.06', '31.05', '30.05', '29.05', '28.05'], $report['labels']);
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
