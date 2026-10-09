<?php

namespace Tests\Unit;

use App\Models\BunkerPickupReport;
use App\Services\BunkerPickupWaybillService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BunkerPickupWaybillServiceTest extends TestCase
{
    public function test_late_waybill_is_saved_and_limit_is_enforced(): void
    {
        $migration = require database_path('migrations/2026_09_22_000018_create_bunker_pickup_reports.php');
        $migration->up();
        try {
            $report = BunkerPickupReport::query()->create([
                'driver_source' => 'max', 'driver_user_id' => '1', 'cleanup_status' => 'cleaned',
                'billing_units' => 1, 'completed_at' => now(), 'waybill_missing_reason' => 'Позже',
            ]);
            $upload = UploadedFile::fake()->createWithContent('ticket.pdf', "%PDF-1.4\n%%EOF");
            $service = app(BunkerPickupWaybillService::class);
            $service->attach($report, [$upload]);
            $this->assertSame(1, $report->waybills()->count());
            $this->assertNull($report->fresh()->waybill_missing_reason);
            $this->expectException(ValidationException::class);
            $service->attach($report, array_fill(0, 5, $upload));
        } finally {
            Schema::dropIfExists('bunker_pickup_files');
            Schema::dropIfExists('bunker_pickup_items');
            Schema::dropIfExists('bunker_pickup_reports');
        }
    }
}
