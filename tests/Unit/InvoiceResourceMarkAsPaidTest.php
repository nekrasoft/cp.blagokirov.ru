<?php

namespace Tests\Unit;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Support\RuntimeSchemaCache;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class InvoiceResourceMarkAsPaidTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RuntimeSchemaCache::flush();
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');

        Schema::create('invoices', function ($table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->dateTime('paid_at')->nullable();
        });

        Schema::create('invoice_items', function ($table): void {
            $table->id();
            $table->unsignedInteger('invoice_id');
            $table->decimal('price', 12, 2);
            $table->decimal('amount', 12, 2);
        });

        Schema::create('works', function ($table): void {
            $table->id();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');
        RuntimeSchemaCache::flush();

        parent::tearDown();
    }

    public function test_mark_as_paid_sets_full_work_revenue_sum_and_today_payment_date(): void
    {
        Carbon::setTestNow('2026-06-09 15:30:00');

        DB::table('invoices')->insert([
            ['id' => 1, 'status' => 'issued', 'paid_amount' => null, 'paid_at' => null],
            ['id' => 2, 'status' => 'pending', 'paid_amount' => 10, 'paid_at' => null],
            ['id' => 3, 'status' => null, 'paid_amount' => null, 'paid_at' => null],
        ]);

        DB::table('works')->insert([
            ['invoice_id' => 1, 'revenue' => 1200],
            ['invoice_id' => 1, 'revenue' => 300.50],
            ['invoice_id' => 2, 'revenue' => 700],
            ['invoice_id' => null, 'revenue' => 999],
        ]);

        $this->invokeMarkAsPaid(Invoice::query()->whereKey([1, 2, 3])->get());

        $invoices = DB::table('invoices')->orderBy('id')->get()->keyBy('id');

        $this->assertSame('paid', $invoices[1]->status);
        $this->assertSame('1500.5', (string) $invoices[1]->paid_amount);
        $this->assertSame('2026-06-09 15:30:00', $invoices[1]->paid_at);

        $this->assertSame('paid', $invoices[2]->status);
        $this->assertSame('700', (string) $invoices[2]->paid_amount);
        $this->assertSame('2026-06-09 15:30:00', $invoices[2]->paid_at);

        $this->assertSame('paid', $invoices[3]->status);
        $this->assertSame('0', (string) $invoices[3]->paid_amount);
        $this->assertSame('2026-06-09 15:30:00', $invoices[3]->paid_at);
    }

    public function test_invoice_query_loads_total_amount_from_items(): void
    {
        DB::table('invoices')->insert([
            ['id' => 1, 'status' => 'paid', 'paid_amount' => 10, 'paid_at' => '2026-06-09 15:30:00'],
        ]);

        DB::table('invoice_items')->insert([
            ['invoice_id' => 1, 'price' => 100, 'amount' => 2],
            ['invoice_id' => 1, 'price' => 50.25, 'amount' => 3],
        ]);

        $invoice = InvoiceResource::getEloquentQuery()->whereKey(1)->firstOrFail();

        $this->assertEqualsWithDelta(350.75, (float) $invoice->getAttribute('items_total'), 0.001);
        $this->assertEqualsWithDelta(350.75, $this->invokeInvoiceTotalAmount($invoice), 0.001);
    }

    public function test_paid_status_label_includes_payment_date(): void
    {
        $invoice = new Invoice([
            'status' => 'paid',
            'paid_at' => '2026-06-09 15:30:00',
        ]);

        $this->assertSame('Оплачен 09.06.2026', $this->invokeInvoiceStatusLabel('paid', $invoice));
    }

    public function test_works_invoice_id_index_migration_adds_matching_index(): void
    {
        $migration = require database_path('migrations/2026_06_09_000014_add_invoice_id_index_to_works_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('works', ['invoice_id']));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('works', ['invoice_id']));
    }

    public function test_invoice_items_invoice_id_index_migration_adds_matching_index(): void
    {
        $migration = require database_path('migrations/2026_06_11_000015_add_invoice_items_invoice_id_index.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('invoice_items', ['invoice_id']));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('invoice_items', ['invoice_id']));
    }

    private function invokeMarkAsPaid($records): void
    {
        $reflection = new ReflectionClass(InvoiceResource::class);
        $method = $reflection->getMethod('markAsPaid');
        $method->setAccessible(true);
        $method->invoke(null, $records);
    }

    private function invokeInvoiceTotalAmount(Invoice $invoice): float
    {
        $reflection = new ReflectionClass(InvoiceResource::class);
        $method = $reflection->getMethod('invoiceTotalAmount');
        $method->setAccessible(true);

        return $method->invoke(null, $invoice);
    }

    private function invokeInvoiceStatusLabel(?string $state, Invoice $invoice): string
    {
        $reflection = new ReflectionClass(InvoiceResource::class);
        $method = $reflection->getMethod('invoiceStatusLabel');
        $method->setAccessible(true);

        return $method->invoke(null, $state, $invoice);
    }
}
