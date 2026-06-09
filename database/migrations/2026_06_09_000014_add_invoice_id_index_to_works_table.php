<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'works';

    private const INDEX = 'idx_works_invoice_id';

    public function up(): void
    {
        if (
            ! Schema::hasTable(self::TABLE)
            || ! Schema::hasColumn(self::TABLE, 'invoice_id')
            || Schema::hasIndex(self::TABLE, self::INDEX)
            || Schema::hasIndex(self::TABLE, ['invoice_id'])
        ) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index('invoice_id', self::INDEX);
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
