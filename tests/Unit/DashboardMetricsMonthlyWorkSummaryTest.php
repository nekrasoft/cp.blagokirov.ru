<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use App\Models\CounterpartyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class DashboardMetricsMonthlyWorkSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetDashboardMetricsCache();
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');

        Schema::create('invoices', function ($table): void {
            $table->id();
            $table->unsignedInteger('counterparty_id');
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->dateTime('paid_at')->nullable();
        });

        Schema::create('works', function ($table): void {
            $table->id();
            $table->date('date')->nullable();
            $table->string('structure')->nullable();
            $table->string('operation')->nullable();
            $table->string('object_count')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
        });

        $this->resetDashboardMetricsCache();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');
        $this->resetDashboardMetricsCache();

        parent::tearDown();
    }

    public function test_monthly_work_summary_aggregates_works_and_received_money_by_category(): void
    {
        DB::table('invoices')->insert([
            ['id' => 1, 'counterparty_id' => 10, 'paid_amount' => 1200, 'paid_at' => '2026-04-10 12:00:00'],
            ['id' => 2, 'counterparty_id' => 10, 'paid_amount' => 300, 'paid_at' => '2026-04-11 12:00:00'],
            ['id' => 3, 'counterparty_id' => 10, 'paid_amount' => 999, 'paid_at' => '2026-05-01 12:00:00'],
            ['id' => 4, 'counterparty_id' => 20, 'paid_amount' => 500, 'paid_at' => '2026-04-12 12:00:00'],
        ]);

        DB::table('works')->insert([
            ['id' => 1, 'date' => '2026-04-03', 'structure' => 'Контейнер', 'operation' => null, 'object_count' => '10,5', 'revenue' => 900, 'invoice_id' => 1],
            ['id' => 2, 'date' => '2026-04-04', 'structure' => 'Ломовоз', 'operation' => 'ходка', 'object_count' => '2', 'revenue' => 300, 'invoice_id' => 1],
            ['id' => 3, 'date' => '2026-03-20', 'structure' => 'Контейнер', 'operation' => null, 'object_count' => '7', 'revenue' => 700, 'invoice_id' => 2],
            ['id' => 4, 'date' => '2026-04-05', 'structure' => 'Контейнер', 'operation' => null, 'object_count' => '1', 'revenue' => 100, 'invoice_id' => 3],
            ['id' => 5, 'date' => '2026-04-06', 'structure' => 'Контейнер', 'operation' => null, 'object_count' => '100', 'revenue' => 10000, 'invoice_id' => 4],
        ]);

        $summary = DashboardMetrics::monthlyWorkSummary('2026-04', $this->counterpartyUser());

        $container = $this->rowByName($summary['rows'], 'Контейнер');
        $truck = $this->rowByName($summary['rows'], 'Ломовоз (ходка)');

        $this->assertSame(11.5, $container['quantity']);
        $this->assertSame(92.0, $container['volume']);
        $this->assertSame(1000.0, $container['revenue']);
        $this->assertSame(1200.0, $container['received']);

        $this->assertSame(2.0, $truck['quantity']);
        $this->assertSame(60.0, $truck['volume']);
        $this->assertSame(300.0, $truck['revenue']);
        $this->assertSame(300.0, $truck['received']);

        $this->assertSame(13.5, $summary['totals']['quantity']);
        $this->assertSame(152.0, $summary['totals']['volume']);
        $this->assertSame(1300.0, $summary['totals']['revenue']);
        $this->assertSame(1500.0, $summary['totals']['received']);
    }

    /**
     * @param  array<int, array{name: string}>  $rows
     * @return array<string, mixed>
     */
    private function rowByName(array $rows, string $name): array
    {
        foreach ($rows as $row) {
            if ($row['name'] === $name) {
                return $row;
            }
        }

        $this->fail("Row [{$name}] was not found.");
    }

    private function counterpartyUser(): CounterpartyUser
    {
        $user = new CounterpartyUser([
            'counterparty_id' => 10,
            'district_scope' => null,
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
