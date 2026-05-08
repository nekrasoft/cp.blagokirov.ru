<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use App\Models\CounterpartyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class DashboardMetricsDistrictScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetDashboardMetricsCache();
        Schema::dropIfExists('bunkers');
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bunker_fill_requests');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bunkers');
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bunker_fill_requests');
        $this->resetDashboardMetricsCache();

        parent::tearDown();
    }

    public function test_fill_requests_query_applies_counterparty_district_scope(): void
    {
        Schema::create('bunker_fill_requests', function ($table): void {
            $table->id();
            $table->unsignedInteger('counterparty_id');
            $table->string('district')->nullable();
        });

        DB::table('bunker_fill_requests')->insert([
            ['id' => 1, 'counterparty_id' => 10, 'district' => 'Центральный'],
            ['id' => 2, 'counterparty_id' => 10, 'district' => 'Северный'],
            ['id' => 3, 'counterparty_id' => 20, 'district' => 'Центральный'],
        ]);

        $ids = DashboardMetrics::fillRequestsQuery($this->counterpartyUser())
            ?->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1], $ids);
    }

    public function test_bunkers_query_applies_counterparty_district_scope(): void
    {
        Schema::create('bunkers', function ($table): void {
            $table->string('id')->primary();
            $table->unsignedInteger('counterparty_id');
            $table->string('district')->nullable();
        });

        DB::table('bunkers')->insert([
            ['id' => 'a', 'counterparty_id' => 10, 'district' => 'Центральный'],
            ['id' => 'b', 'counterparty_id' => 10, 'district' => 'Северный'],
            ['id' => 'c', 'counterparty_id' => 20, 'district' => 'Центральный'],
        ]);

        $ids = DashboardMetrics::bunkersQuery($this->counterpartyUser())
            ?->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame(['a'], $ids);
    }

    public function test_invoices_query_ignores_district_scope(): void
    {
        $this->createInvoicesAndWorksTables(includeWorksDistrict: true);

        DB::table('invoices')->insert([
            ['id' => 1, 'counterparty_id' => 10],
            ['id' => 2, 'counterparty_id' => 10],
            ['id' => 3, 'counterparty_id' => 20],
        ]);
        DB::table('works')->insert([
            ['id' => 1, 'invoice_id' => 1, 'district' => 'Центральный'],
            ['id' => 2, 'invoice_id' => 2, 'district' => 'Северный'],
            ['id' => 3, 'invoice_id' => 3, 'district' => 'Центральный'],
        ]);

        $ids = DashboardMetrics::invoicesQuery($this->counterpartyUser())
            ?->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 2], $ids);
    }

    public function test_works_query_ignores_district_scope(): void
    {
        $this->createInvoicesAndWorksTables(includeWorksDistrict: true);

        DB::table('invoices')->insert([
            ['id' => 1, 'counterparty_id' => 10],
            ['id' => 2, 'counterparty_id' => 10],
            ['id' => 3, 'counterparty_id' => 20],
        ]);
        DB::table('works')->insert([
            ['id' => 1, 'invoice_id' => 1, 'district' => 'Центральный'],
            ['id' => 2, 'invoice_id' => 2, 'district' => 'Северный'],
            ['id' => 3, 'invoice_id' => 3, 'district' => 'Центральный'],
        ]);

        $ids = DashboardMetrics::worksQuery($this->counterpartyUser())
            ?->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 2], $ids);
    }

    public function test_invoices_query_does_not_require_district_resolution(): void
    {
        $this->createInvoicesAndWorksTables(includeWorksDistrict: false);

        DB::table('invoices')->insert([
            ['id' => 1, 'counterparty_id' => 10],
        ]);
        DB::table('works')->insert([
            ['id' => 1, 'invoice_id' => 1],
        ]);

        $ids = DashboardMetrics::invoicesQuery($this->counterpartyUser())
            ?->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1], $ids);
    }

    private function createInvoicesAndWorksTables(bool $includeWorksDistrict): void
    {
        Schema::create('invoices', function ($table): void {
            $table->id();
            $table->unsignedInteger('counterparty_id');
        });

        Schema::create('works', function ($table) use ($includeWorksDistrict): void {
            $table->id();
            $table->unsignedInteger('invoice_id')->nullable();

            if ($includeWorksDistrict) {
                $table->string('district')->nullable();
            }
        });
    }

    private function counterpartyUser(): CounterpartyUser
    {
        $user = new CounterpartyUser([
            'counterparty_id' => 10,
            'district_scope' => 'Центральный',
        ]);

        $user->setRelation('counterparty', null);

        return $user;
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
