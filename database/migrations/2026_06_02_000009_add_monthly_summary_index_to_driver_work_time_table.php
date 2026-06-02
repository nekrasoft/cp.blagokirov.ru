<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'idx_driver_work_time_work_date_source_user';

    private const TABLE = 'driver_work_time';

    public function up(): void
    {
        if (
            ! Schema::hasTable(self::TABLE)
            || ! Schema::hasColumn(self::TABLE, 'work_date')
            || ! Schema::hasColumn(self::TABLE, 'source')
            || ! Schema::hasColumn(self::TABLE, 'source_user_id')
            || $this->hasWorkDateLeadingIndex()
        ) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index(['work_date', 'source', 'source_user_id'], self::INDEX);
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

    private function hasWorkDateLeadingIndex(): bool
    {
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            if (($index['columns'][0] ?? null) === 'work_date') {
                return true;
            }
        }

        return false;
    }
};
