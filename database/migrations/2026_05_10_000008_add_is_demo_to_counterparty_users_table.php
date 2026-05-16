<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('counterparty_users') || Schema::hasColumn('counterparty_users', 'is_demo')) {
            return;
        }

        Schema::table('counterparty_users', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false)->after('is_active');
            $table->index('is_demo');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('counterparty_users') || ! Schema::hasColumn('counterparty_users', 'is_demo')) {
            return;
        }

        Schema::table('counterparty_users', function (Blueprint $table): void {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
