<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::getConnection()->getDriverName() !== 'mysql'
            || ! Schema::hasTable('bunkers')
            || ! Schema::hasTable('bunker_fill_requests')
            || ! Schema::hasColumn('bunkers', 'last_filled_at')
            || ! Schema::hasColumn('bunkers', 'last_filled_by')
            || ! Schema::hasColumn('bunker_fill_requests', 'cancelled_at')
        ) {
            return;
        }

        DB::update(
            <<<'SQL'
                UPDATE bunker_fill_requests AS fr
                INNER JOIN bunkers AS b ON b.id = fr.bunker_id
                SET fr.filled_at = b.last_filled_at,
                    fr.filled_by = b.last_filled_by,
                    fr.fill_level = 100
                WHERE fr.executed_at IS NULL
                  AND fr.cancelled_at IS NULL
                  AND b.last_filled_at > fr.filled_at
                  AND b.last_filled_by IS NOT NULL
                  AND b.last_filled_by <> ?
                SQL,
            ['Автопрогноз'],
        );
    }

    public function down(): void
    {
        // Восстановление прежней даты заявки уничтожило бы корректную ручную отметку.
    }
};
