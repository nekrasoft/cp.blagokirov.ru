<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FixStroymodaPickupReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('counterparties', function (Blueprint $table): void {
            $table->id();
            $table->string('short_name');
        });
        Schema::create('bunkers', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('counterparty_id')->nullable();
        });
        Schema::create('bunker_pickup_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->string('contractor');
            $table->dateTime('completed_at')->index();
        });
        Schema::create('bunker_pickup_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('report_id')->index();
            $table->string('bunker_id');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bunker_pickup_items');
        Schema::dropIfExists('bunker_pickup_reports');
        Schema::dropIfExists('bunkers');
        Schema::dropIfExists('counterparties');

        parent::tearDown();
    }

    public function test_migration_corrects_only_the_stroymoda_report_from_the_screenshot(): void
    {
        DB::table('counterparties')->insert([
            ['id' => 1, 'short_name' => 'Азбука'],
            ['id' => 2, 'short_name' => 'СТРОЙМОДА'],
        ]);
        DB::table('bunkers')->insert([
            ['id' => 'reassigned', 'counterparty_id' => 2],
            ['id' => 'still-azbuka', 'counterparty_id' => 1],
        ]);
        DB::table('bunker_pickup_reports')->insert([
            ['id' => 10, 'counterparty_id' => 1, 'contractor' => 'Азбука', 'completed_at' => '2026-09-28 23:04:30'],
            ['id' => 11, 'counterparty_id' => 1, 'contractor' => 'Азбука', 'completed_at' => '2026-09-28 23:04:40'],
            ['id' => 12, 'counterparty_id' => 1, 'contractor' => 'Азбука', 'completed_at' => '2026-09-28 23:05:00'],
        ]);
        DB::table('bunker_pickup_items')->insert([
            ['report_id' => 10, 'bunker_id' => 'reassigned'],
            ['report_id' => 11, 'bunker_id' => 'still-azbuka'],
            ['report_id' => 12, 'bunker_id' => 'reassigned'],
        ]);

        $migration = require database_path('migrations/2026_09_29_000021_fix_stroymoda_pickup_report.php');
        $migration->up();

        $reports = DB::table('bunker_pickup_reports')->orderBy('id')->get()->keyBy('id');

        $this->assertSame(2, (int) $reports[10]->counterparty_id);
        $this->assertSame('СТРОЙМОДА', $reports[10]->contractor);
        $this->assertSame('Азбука', $reports[11]->contractor);
        $this->assertSame('Азбука', $reports[12]->contractor);
    }
}
