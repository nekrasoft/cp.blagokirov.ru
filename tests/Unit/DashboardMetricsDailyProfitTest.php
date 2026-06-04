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

        $this->resetDashboardMetricsCache();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('daily_expense_allocations');
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
        ]);

        DB::table('daily_expense_allocations')->insert([
            ['expense_date' => '2026-06-01', 'expense_code' => '183', 'amount' => 15000],
            ['expense_date' => '2026-06-01', 'expense_code' => '185', 'amount' => 10000],
            ['expense_date' => '2026-06-02', 'expense_code' => '183', 'amount' => 15000],
            ['expense_date' => '2026-06-02', 'expense_code' => '182', 'amount' => 7000],
            ['expense_date' => '2026-06-03', 'expense_code' => '185', 'amount' => 999999],
        ]);

        $profit = DashboardMetrics::dailyProfitByDay(2);

        $this->assertSame(['01.06', '02.06'], $profit['labels']);
        $this->assertSame([50000.0, 20000.0], $profit['revenue']);
        $this->assertSame([15000.0, 15000.0], $profit['fuel_expense']);
        $this->assertSame([10000.0, 0.0], $profit['landfill_expense']);
        $this->assertSame([25000.0, 15000.0], $profit['total_expense']);
        $this->assertSame([25000.0, 5000.0], $profit['profit']);
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
