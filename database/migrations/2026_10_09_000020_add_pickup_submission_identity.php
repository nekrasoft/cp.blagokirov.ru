<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['submission_key' => 36, 'submission_hash' => 64] as $column => $length) {
            if (! Schema::hasColumn('bunker_pickup_reports', $column)) {
                Schema::table('bunker_pickup_reports', fn (Blueprint $table) => $table->string($column, $length)->nullable());
            }
        }
        if (! Schema::hasIndex('bunker_pickup_reports', 'uq_pickup_submission')) {
            Schema::table('bunker_pickup_reports', fn (Blueprint $table) => $table->unique('submission_key', 'uq_pickup_submission'));
        }
    }

    public function down(): void
    {
        Schema::table('bunker_pickup_reports', function (Blueprint $table): void {
            $table->dropUnique('uq_pickup_submission');
            $table->dropColumn(['submission_key', 'submission_hash']);
        });
    }
};
