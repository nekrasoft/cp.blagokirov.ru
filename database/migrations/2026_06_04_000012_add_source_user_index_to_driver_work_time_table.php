<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'driver_work_time';

    private const INDEX = 'idx_driver_work_time_source_user';

    public function up(): void
    {
        if (
            ! Schema::hasTable(self::TABLE)
            || ! Schema::hasColumn(self::TABLE, 'source')
            || ! Schema::hasColumn(self::TABLE, 'source_user_id')
            || ! Schema::hasColumn(self::TABLE, 'work_date')
            || Schema::hasIndex(self::TABLE, self::INDEX)
        ) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index(['source', 'source_user_id', 'work_date'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasIndex(self::TABLE, self::INDEX)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropIndex(self::INDEX);
        });
    }
};
