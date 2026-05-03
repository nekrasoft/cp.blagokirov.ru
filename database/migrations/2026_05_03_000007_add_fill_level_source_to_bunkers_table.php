<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bunkers') || Schema::hasColumn('bunkers', 'fill_level_source')) {
            return;
        }

        Schema::table('bunkers', function (Blueprint $table): void {
            $table->string('fill_level_source', 50)->nullable()->after('last_filled_by');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bunkers') || ! Schema::hasColumn('bunkers', 'fill_level_source')) {
            return;
        }

        Schema::table('bunkers', function (Blueprint $table): void {
            $table->dropColumn('fill_level_source');
        });
    }
};
