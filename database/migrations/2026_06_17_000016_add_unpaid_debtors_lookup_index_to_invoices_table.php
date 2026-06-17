<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'invoices';

    private const INDEX = 'idx_invoices_status_counterparty_id';

    public function up(): void
    {
        if (
            ! Schema::hasTable(self::TABLE)
            || ! Schema::hasColumn(self::TABLE, 'status')
            || ! Schema::hasColumn(self::TABLE, 'counterparty_id')
            || Schema::hasIndex(self::TABLE, self::INDEX)
            || Schema::hasIndex(self::TABLE, ['status', 'counterparty_id'])
        ) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index(['status', 'counterparty_id'], self::INDEX);
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
