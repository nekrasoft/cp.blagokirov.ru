<?php

namespace Tests\Unit;

use App\Filament\Support\BunkerPickupReportScope;
use App\Http\Controllers\BunkerPickupFileController;
use App\Models\BunkerPickupReport;
use App\Models\CounterpartyUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TripPickupReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::connection()->getPdo()->sqliteCreateFunction('lower', fn ($value) => mb_strtolower((string) $value));
        Schema::create('bunker_fill_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('bunker_id');
            $table->string('district');
            $table->dateTime('filled_at');
            $table->dateTime('executed_at')->nullable();
        });
        (require database_path('migrations/2026_09_22_000018_create_bunker_pickup_reports.php'))->up();
        $migration = require database_path('migrations/2026_10_16_000022_add_trip_pickup_reports.php');
        $migration->up();
        $migration->up();
    }

    protected function tearDown(): void
    {
        foreach (['bunker_pickup_files', 'bunker_pickup_items', 'bunker_pickup_reports', 'bunker_fill_requests'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_trip_reports_and_files_obey_counterparty_and_district_scope(): void
    {
        foreach ([[1, 'Знак'], [1, 'Инноград'], [2, 'Знак']] as $index => [$counterparty, $district]) {
            DB::table('bunker_pickup_reports')->insert([
                'id' => $index + 1, 'report_type' => 'trip', 'counterparty_id' => $counterparty, 'district' => $district,
                'address' => 'Площадка', 'driver_source' => 'max', 'driver_user_id' => '42', 'cleanup_status' => 'not_recorded',
                'billing_units' => 2, 'trips_count' => 2, 'trip_volume_m3' => 60, 'completed_at' => '2026-10-16 12:00:00',
            ]);
            DB::table('bunker_pickup_files')->insert([
                'id' => $index + 1, 'report_id' => $index + 1, 'kind' => 'site_photo', 'file_name' => 'body.jpg',
                'content_type' => 'image/jpeg', 'file_size' => 3, 'file_sha256' => hash('sha256', 'jpg'), 'file_data' => 'jpg',
            ]);
        }
        $bunker = BunkerPickupReport::findOrFail(1)->replicate();
        $bunker->report_type = 'bunker';
        $bunker->save();
        DB::table('bunker_fill_requests')->insert(['id' => 1, 'bunker_id' => 'b1', 'district' => 'Знак', 'filled_at' => '2026-10-16 12:00:00']);
        DB::table('bunker_pickup_items')->insert(['report_id' => $bunker->id, 'request_id' => 1, 'bunker_id' => 'b1', 'bunker_number' => 1, 'billing_units' => 1, 'estimated_volume_m3' => 8]);
        $user = new CounterpartyUser(['counterparty_id' => 1, 'district_scope' => 'Знак']);
        $this->assertSame([1, 4], BunkerPickupReportScope::apply(BunkerPickupReport::query(), $user)->orderBy('id')->pluck('id')->all());
        Auth::guard('counterparty')->setUser($user);
        $controller = new BunkerPickupFileController;
        $this->assertSame('jpg', $controller->counterparty(1)->getContent());
        foreach ([2, 3] as $forbidden) {
            try {
                $controller->counterparty($forbidden);
                $this->fail('Foreign district or counterparty file was disclosed');
            } catch (HttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
        $user->district_scope = '';
        $this->assertSame([1, 2, 4], BunkerPickupReportScope::apply(BunkerPickupReport::query(), $user)->orderBy('id')->pluck('id')->all());
        $this->assertTrue(Schema::hasIndex('bunker_pickup_reports', 'idx_pickup_counterparty_type_district'));
    }
}
