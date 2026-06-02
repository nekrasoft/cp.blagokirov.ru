<?php

namespace Tests\Unit;

use App\Filament\Support\DriverWorkTimeSummary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverWorkTimeSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('driver_work_time');
        Schema::create('driver_work_time', function (Blueprint $table): void {
            $table->id();
            $table->string('source');
            $table->string('source_user_id');
            $table->string('source_user_name')->nullable();
            $table->date('work_date');
            $table->unsignedInteger('duration_minutes');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('driver_work_time');

        parent::tearDown();
    }

    public function test_it_summarizes_selected_month_separately_for_each_driver(): void
    {
        DB::table('driver_work_time')->insert([
            ['source' => 'max', 'source_user_id' => '10', 'source_user_name' => 'Иван', 'work_date' => '2026-05-02', 'duration_minutes' => 480],
            ['source' => 'max', 'source_user_id' => '10', 'source_user_name' => 'Иван', 'work_date' => '2026-05-03', 'duration_minutes' => 510],
            ['source' => 'max', 'source_user_id' => '20', 'source_user_name' => 'Петр', 'work_date' => '2026-05-03', 'duration_minutes' => 425],
            ['source' => 'max', 'source_user_id' => '10', 'source_user_name' => 'Иван', 'work_date' => '2026-06-01', 'duration_minutes' => 600],
        ]);

        $summary = DriverWorkTimeSummary::forMonth('2026-05');

        $this->assertTrue($summary['has_data']);
        $this->assertSame('2026-05', $summary['month_key']);
        $this->assertSame('Иван', $summary['rows'][0]['driver_name']);
        $this->assertSame(2, $summary['rows'][0]['work_days']);
        $this->assertSame(990, $summary['rows'][0]['total_minutes']);
        $this->assertSame('16 ч 30 мин', $summary['rows'][0]['total_duration_formatted']);
        $this->assertSame('Петр', $summary['rows'][1]['driver_name']);
        $this->assertSame(425, $summary['rows'][1]['total_minutes']);
        $this->assertSame(1415, $summary['totals']['total_minutes']);
        $this->assertSame('23,58', $summary['totals']['total_hours_formatted']);
    }

    public function test_it_keeps_same_external_id_separate_for_different_sources(): void
    {
        DB::table('driver_work_time')->insert([
            ['source' => 'max', 'source_user_id' => '10', 'source_user_name' => 'Иван', 'work_date' => '2026-05-02', 'duration_minutes' => 480],
            ['source' => 'telegram', 'source_user_id' => '10', 'source_user_name' => 'Другой водитель', 'work_date' => '2026-05-02', 'duration_minutes' => 300],
        ]);

        $summary = DriverWorkTimeSummary::forMonth('2026-05');

        $this->assertCount(2, $summary['rows']);
        $this->assertSame(780, $summary['totals']['total_minutes']);
    }

    public function test_monthly_summary_migration_adds_work_date_leading_index(): void
    {
        $migration = require database_path('migrations/2026_06_02_000009_add_monthly_summary_index_to_driver_work_time_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('driver_work_time', ['work_date', 'source', 'source_user_id']));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('driver_work_time', ['work_date', 'source', 'source_user_id']));
    }
}
