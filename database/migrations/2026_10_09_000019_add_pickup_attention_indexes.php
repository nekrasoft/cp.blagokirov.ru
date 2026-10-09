<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bunker_pickup_reports', function (Blueprint $table): void {
            $table->index(['cleanup_status', 'completed_at'], 'idx_pickup_cleanup_completed');
        });
        Schema::table('bunker_fill_requests', function (Blueprint $table): void {
            $table->index(['executed_at', 'cancelled_at', 'filled_at'], 'idx_requests_status_filled');
        });
    }

    public function down(): void
    {
        Schema::table('bunker_pickup_reports', fn (Blueprint $table) => $table->dropIndex('idx_pickup_cleanup_completed'));
        Schema::table('bunker_fill_requests', fn (Blueprint $table) => $table->dropIndex('idx_requests_status_filled'));
    }
};
