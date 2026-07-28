<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'counterparties';

    private const INDEX = 'idx_counterparties_operation_type';

    private const DEFAULT_OPERATION_TYPE = 'container_pickup';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'operation_type')) {
            return;
        }

        $this->addIndex();

        DB::table(self::TABLE)
            ->whereNull('operation_type')
            ->update(['operation_type' => self::DEFAULT_OPERATION_TYPE]);

        DB::table(self::TABLE)
            ->where('operation_type', '')
            ->update(['operation_type' => self::DEFAULT_OPERATION_TYPE]);
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

    private function addIndex(): void
    {
        if (
            Schema::hasIndex(self::TABLE, self::INDEX)
            || Schema::hasIndex(self::TABLE, ['operation_type'])
        ) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index('operation_type', self::INDEX);
        });
    }
};
