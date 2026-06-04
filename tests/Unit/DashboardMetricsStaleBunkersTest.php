<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class DashboardMetricsStaleBunkersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetDashboardMetricsCache();
        Schema::dropIfExists('bunker_fill_requests');
        Schema::dropIfExists('bunkers');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-04 12:00:00'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        Schema::dropIfExists('bunker_fill_requests');
        Schema::dropIfExists('bunkers');
        $this->resetDashboardMetricsCache();

        parent::tearDown();
    }

    public function test_stale_bunkers_query_returns_bunkers_without_pickup_for_fourteen_days(): void
    {
        $this->createBunkerTables();

        DB::table('bunkers')->insert([
            ['id' => 'recent'],
            ['id' => 'old'],
            ['id' => 'without_executed_at'],
            ['id' => 'exact_cutoff'],
            ['id' => 'without_requests'],
        ]);

        DB::table('bunker_fill_requests')->insert([
            ['bunker_id' => 'recent', 'executed_at' => '2026-06-01 10:00:00'],
            ['bunker_id' => 'old', 'executed_at' => '2026-05-20 10:00:00'],
            ['bunker_id' => 'without_executed_at', 'executed_at' => null],
            ['bunker_id' => 'exact_cutoff', 'executed_at' => '2026-05-21 12:00:00'],
        ]);

        $ids = DashboardMetrics::staleBunkersQuery()
            ?->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame(['old', 'without_executed_at', 'without_requests'], $ids);
    }

    public function test_stale_bunker_pickup_index_migration_adds_matching_index(): void
    {
        $this->createBunkerTables();

        $migration = require database_path('migrations/2026_06_04_000010_add_stale_bunker_pickup_index_to_bunker_fill_requests_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('bunker_fill_requests', ['bunker_id', 'executed_at']));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('bunker_fill_requests', ['bunker_id', 'executed_at']));
    }

    private function createBunkerTables(): void
    {
        Schema::create('bunkers', function ($table): void {
            $table->string('id')->primary();
        });

        Schema::create('bunker_fill_requests', function ($table): void {
            $table->id();
            $table->string('bunker_id');
            $table->dateTime('executed_at')->nullable();
        });
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
