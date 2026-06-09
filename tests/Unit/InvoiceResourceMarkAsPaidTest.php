<?php

namespace Tests\Unit;

use App\Filament\Resources\InvoiceResource;
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

        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');

        Schema::create('invoices', function ($table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->dateTime('paid_at')->nullable();
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
        Schema::dropIfExists('works');
        Schema::dropIfExists('invoices');

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

    public function test_works_invoice_id_index_migration_adds_matching_index(): void
    {
        $migration = require database_path('migrations/2026_06_09_000014_add_invoice_id_index_to_works_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('works', ['invoice_id']));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('works', ['invoice_id']));
    }

    private function invokeMarkAsPaid($records): void
    {
        $reflection = new ReflectionClass(InvoiceResource::class);
        $method = $reflection->getMethod('markAsPaid');
        $method->setAccessible(true);
        $method->invoke(null, $records);
    }
}
