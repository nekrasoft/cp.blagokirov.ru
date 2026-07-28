<?php

namespace Tests\Unit;

use App\Filament\Resources\CounterpartyResource;
use App\Models\Counterparty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class CounterpartyOperationTypeFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('counterparties');
        Schema::create('counterparties', function ($table): void {
            $table->id();
            $table->string('short_name')->nullable();
            $table->string('name')->nullable();
            $table->string('operation_type')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('counterparties');

        parent::tearDown();
    }

    public function test_container_pickup_filter_includes_legacy_default_values(): void
    {
        $this->seedCounterparties();

        $this->assertSame([1, 2, 3], $this->filteredIds('container_pickup'));
    }

    public function test_trip_removal_filter_uses_explicit_operation_type(): void
    {
        $this->seedCounterparties();

        $this->assertSame([4], $this->filteredIds('trip_removal'));
    }

    public function test_blank_operation_type_filter_keeps_query_unfiltered(): void
    {
        $this->seedCounterparties();

        $this->assertSame([1, 2, 3, 4, 5], $this->filteredIds(null));
        $this->assertSame([1, 2, 3, 4, 5], $this->filteredIds(''));
    }

    public function test_operation_type_migration_backfills_default_and_adds_lookup_index(): void
    {
        $this->seedCounterparties();

        $migration = require database_path('migrations/2026_07_28_000017_backfill_and_index_counterparties_operation_type.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('counterparties', ['operation_type']));
        $this->assertSame([
            1 => 'container_pickup',
            2 => 'container_pickup',
            3 => 'container_pickup',
            4 => 'trip_removal',
            5 => 'unknown',
        ], DB::table('counterparties')->orderBy('id')->pluck('operation_type', 'id')->all());

        $migration->down();

        $this->assertFalse(Schema::hasIndex('counterparties', ['operation_type']));
    }

    private function seedCounterparties(): void
    {
        DB::table('counterparties')->insert([
            ['id' => 1, 'short_name' => 'Legacy Null', 'name' => 'Legacy Null', 'operation_type' => null],
            ['id' => 2, 'short_name' => 'Legacy Empty', 'name' => 'Legacy Empty', 'operation_type' => ''],
            ['id' => 3, 'short_name' => 'Container', 'name' => 'Container', 'operation_type' => 'container_pickup'],
            ['id' => 4, 'short_name' => 'Trip', 'name' => 'Trip', 'operation_type' => 'trip_removal'],
            ['id' => 5, 'short_name' => 'Unknown', 'name' => 'Unknown', 'operation_type' => 'unknown'],
        ]);
    }

    /**
     * @return list<int>
     */
    private function filteredIds(?string $operationType): array
    {
        $query = Counterparty::query()->orderBy('id');

        $this->applyOperationTypeFilter($query, $operationType);

        return $query->pluck('id')->all();
    }

    private function applyOperationTypeFilter(Builder $query, ?string $operationType): void
    {
        $method = new ReflectionMethod(CounterpartyResource::class, 'applyOperationTypeFilter');
        $method->setAccessible(true);
        $method->invoke(null, $query, $operationType);
    }
}
