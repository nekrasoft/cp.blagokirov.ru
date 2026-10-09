<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'sheet_row' => fn (Blueprint $table) => $table->text('sheet_row')->nullable(),
            'sheets_status' => fn (Blueprint $table) => $table->string('sheets_status', 20)->nullable(),
            'sheets_attempts' => fn (Blueprint $table) => $table->unsignedInteger('sheets_attempts')->default(0),
            'sheets_next_attempt_at' => fn (Blueprint $table) => $table->dateTime('sheets_next_attempt_at')->nullable(),
            'sheets_delivery_token' => fn (Blueprint $table) => $table->string('sheets_delivery_token', 36)->nullable(),
            'sheets_error' => fn (Blueprint $table) => $table->string('sheets_error', 255)->nullable(),
        ];
        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('bunker_pickup_reports', $name)) {
                Schema::table('bunker_pickup_reports', $definition);
            }
        }
        if (! Schema::hasIndex('bunker_pickup_reports', 'idx_pickup_sheets_due')) {
            Schema::table('bunker_pickup_reports', fn (Blueprint $table) => $table->index(['sheets_status', 'sheets_next_attempt_at', 'id'], 'idx_pickup_sheets_due'));
        }
    }

    public function down(): void
    {
        Schema::table('bunker_pickup_reports', function (Blueprint $table): void {
            $table->dropIndex('idx_pickup_sheets_due');
            $table->dropColumn(['sheet_row', 'sheets_status', 'sheets_attempts', 'sheets_next_attempt_at', 'sheets_delivery_token', 'sheets_error']);
        });
    }
};
