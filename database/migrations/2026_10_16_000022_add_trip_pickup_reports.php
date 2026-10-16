<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'report_type' => fn (Blueprint $table) => $table->string('report_type', 20)->default('bunker'),
            'trips_count' => fn (Blueprint $table) => $table->unsignedInteger('trips_count')->nullable(),
            'trip_volume_m3' => fn (Blueprint $table) => $table->decimal('trip_volume_m3', 12, 2)->nullable(),
            'district' => fn (Blueprint $table) => $table->string('district')->nullable(),
            'address' => fn (Blueprint $table) => $table->string('address', 500)->nullable(),
            'cash_income' => fn (Blueprint $table) => $table->decimal('cash_income', 12, 2)->nullable(),
        ];
        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('bunker_pickup_reports', $name)) {
                Schema::table('bunker_pickup_reports', $definition);
            }
        }
        if (! Schema::hasIndex('bunker_pickup_reports', 'idx_pickup_counterparty_type_district')) {
            Schema::table('bunker_pickup_reports', fn (Blueprint $table) => $table->index(['counterparty_id', 'report_type', 'district'], 'idx_pickup_counterparty_type_district'));
        }
    }

    public function down(): void
    {
        Schema::table('bunker_pickup_reports', function (Blueprint $table): void {
            $table->dropIndex('idx_pickup_counterparty_type_district');
            $table->dropColumn(['report_type', 'trips_count', 'trip_volume_m3', 'district', 'address', 'cash_income']);
        });
    }
};
