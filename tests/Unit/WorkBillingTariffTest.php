<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkBillingTariffTest extends TestCase
{
    public function test_migration_keeps_historical_work_and_can_run_twice(): void
    {
        Schema::create('works', function (Blueprint $table): void {
            $table->id();
            $table->decimal('revenue', 14, 2);
        });
        try {
            DB::table('works')->insert(['revenue' => 36000]);
            $migration = require database_path('migrations/2026_10_17_000023_add_work_billing_tariff.php');
            $migration->up();
            $migration->up();
            $record = DB::table('works')->first();
            $this->assertEquals(36000, $record->revenue);
            $this->assertNull($record->billing_unit);
            $this->assertNull($record->tariff);
        } finally {
            Schema::dropIfExists('works');
        }
    }
}
