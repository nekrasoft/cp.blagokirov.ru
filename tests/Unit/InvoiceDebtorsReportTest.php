<?php

namespace Tests\Unit;

use App\Filament\Support\DashboardMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class InvoiceDebtorsReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->flushDashboardMetricsSchemaCache();
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('counterparties');

        Schema::create('counterparties', function ($table): void {
            $table->id();
            $table->string('short_name')->nullable();
            $table->string('name')->nullable();
        });

        Schema::create('invoices', function ($table): void {
            $table->id();
            $table->unsignedInteger('counterparty_id')->nullable();
            $table->string('status')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
        });

        Schema::create('invoice_items', function ($table): void {
            $table->id();
            $table->unsignedInteger('invoice_id');
            $table->decimal('price', 12, 2);
            $table->decimal('amount', 12, 2);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('counterparties');
        $this->flushDashboardMetricsSchemaCache();

        parent::tearDown();
    }

    public function test_unpaid_invoice_debtors_query_groups_unpaid_totals_by_counterparty(): void
    {
        Carbon::setTestNow('2026-06-17 12:00:00');

        DB::table('counterparties')->insert([
            ['id' => 10, 'short_name' => 'Альфа', 'name' => 'ООО Альфа'],
            ['id' => 20, 'short_name' => 'Бета', 'name' => 'ООО Бета'],
        ]);

        DB::table('invoices')->insert([
            ['id' => 1, 'counterparty_id' => 10, 'status' => 'issued', 'due_date' => '2026-06-10', 'paid_amount' => null],
            ['id' => 2, 'counterparty_id' => 10, 'status' => 'pending', 'due_date' => '2026-06-30', 'paid_amount' => null],
            ['id' => 3, 'counterparty_id' => 20, 'status' => null, 'due_date' => null, 'paid_amount' => null],
            ['id' => 4, 'counterparty_id' => 10, 'status' => 'paid', 'due_date' => '2026-06-01', 'paid_amount' => 999],
            ['id' => 5, 'counterparty_id' => 20, 'status' => 'failed', 'due_date' => '2026-06-01', 'paid_amount' => null],
        ]);

        DB::table('invoice_items')->insert([
            ['invoice_id' => 1, 'price' => 100, 'amount' => 2],
            ['invoice_id' => 1, 'price' => 50, 'amount' => 1],
            ['invoice_id' => 2, 'price' => 300, 'amount' => 1],
            ['invoice_id' => 3, 'price' => 125, 'amount' => 1],
            ['invoice_id' => 4, 'price' => 999, 'amount' => 1],
            ['invoice_id' => 5, 'price' => 777, 'amount' => 1],
        ]);

        $rows = DashboardMetrics::unpaidInvoiceDebtorsQuery()
            ?->orderBy('invoices.counterparty_id')
            ->get()
            ->keyBy('counterparty_id');

        $this->assertNotNull($rows);
        $this->assertCount(2, $rows);
        $this->assertEqualsWithDelta(550, (float) $rows[10]->getAttribute('unpaid_total'), 0.001);
        $this->assertSame(2, (int) $rows[10]->getAttribute('unpaid_invoices_count'));
        $this->assertSame(1, (int) $rows[10]->getAttribute('overdue_invoices_count'));
        $this->assertSame('2026-06-10', (string) $rows[10]->getAttribute('oldest_due_date'));

        $this->assertEqualsWithDelta(125, (float) $rows[20]->getAttribute('unpaid_total'), 0.001);
        $this->assertSame(1, (int) $rows[20]->getAttribute('unpaid_invoices_count'));
        $this->assertSame(0, (int) $rows[20]->getAttribute('overdue_invoices_count'));
    }

    public function test_unpaid_debtors_lookup_index_migration_adds_matching_index(): void
    {
        $migration = require database_path('migrations/2026_06_17_000016_add_unpaid_debtors_lookup_index_to_invoices_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('invoices', ['status', 'counterparty_id']));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('invoices', ['status', 'counterparty_id']));
    }

    private function flushDashboardMetricsSchemaCache(): void
    {
        $reflection = new ReflectionClass(DashboardMetrics::class);

        foreach (['tableCache', 'columnCache'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, []);
        }
    }
}
