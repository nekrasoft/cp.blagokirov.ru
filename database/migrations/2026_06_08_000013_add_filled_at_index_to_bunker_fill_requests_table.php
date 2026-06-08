<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'bunker_fill_requests';

    private const INDEX = 'idx_bunker_fill_requests_filled_at';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)
            || ! Schema::hasColumn(self::TABLE, 'filled_at')
            || Schema::hasIndex(self::TABLE, self::INDEX)
            || Schema::hasIndex(self::TABLE, ['filled_at'])
        ) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index('filled_at', self::INDEX);
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
