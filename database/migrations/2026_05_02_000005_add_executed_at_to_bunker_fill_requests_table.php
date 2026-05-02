<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bunker_fill_requests')) {
            return;
        }

        if (! Schema::hasColumn('bunker_fill_requests', 'executed_at')) {
            Schema::table('bunker_fill_requests', function (Blueprint $table): void {
                $table->dateTime('executed_at')->nullable()->after('filled_at');
            });
        }

        if (! $this->indexExists('bunker_fill_requests', 'idx_bunker_fill_requests_executed_at')) {
            Schema::table('bunker_fill_requests', function (Blueprint $table): void {
                $table->index('executed_at', 'idx_bunker_fill_requests_executed_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bunker_fill_requests') || ! Schema::hasColumn('bunker_fill_requests', 'executed_at')) {
            return;
        }

        if ($this->indexExists('bunker_fill_requests', 'idx_bunker_fill_requests_executed_at')) {
            Schema::table('bunker_fill_requests', function (Blueprint $table): void {
                $table->dropIndex('idx_bunker_fill_requests_executed_at');
            });
        }

        Schema::table('bunker_fill_requests', function (Blueprint $table): void {
            $table->dropColumn('executed_at');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        return (int) DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->count() > 0;
    }
};
