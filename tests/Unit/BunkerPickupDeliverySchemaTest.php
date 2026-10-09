<?php

namespace Tests\Unit;

use App\Filament\Resources\BunkerPickupReportResource;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BunkerPickupDeliverySchemaTest extends TestCase
{
    public function test_delivery_migrations_can_follow_api_schema_initialization(): void
    {
        $create = require database_path('migrations/2026_09_22_000018_create_bunker_pickup_reports.php');
        $identity = require database_path('migrations/2026_10_09_000020_add_pickup_submission_identity.php');
        $delivery = require database_path('migrations/2026_10_09_000021_add_pickup_sheet_delivery.php');
        $create->up();
        try {
            $identity->up();
            $delivery->up();
            $identity->up();
            $delivery->up();
            $this->assertTrue(Schema::hasIndex('bunker_pickup_reports', 'uq_pickup_submission', 'unique'));
            $this->assertTrue(Schema::hasIndex('bunker_pickup_reports', 'idx_pickup_sheets_due'));
            $this->assertTrue(Schema::hasColumn('bunker_pickup_reports', 'sheet_row'));
            $this->assertSame('Ожидает повторной доставки', BunkerPickupReportResource::sheetsStatusLabel('retry'));
        } finally {
            Schema::dropIfExists('bunker_pickup_files');
            Schema::dropIfExists('bunker_pickup_items');
            Schema::dropIfExists('bunker_pickup_reports');
        }
    }
}
