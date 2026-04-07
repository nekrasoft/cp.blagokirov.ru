<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('counterparty_users')) {
            return;
        }

        Schema::table('counterparty_users', function (Blueprint $table): void {
            if (!Schema::hasColumn('counterparty_users', 'district_scope')) {
                $table->string('district_scope', 255)->nullable()->after('counterparty_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('counterparty_users')) {
            return;
        }

        Schema::table('counterparty_users', function (Blueprint $table): void {
            if (Schema::hasColumn('counterparty_users', 'district_scope')) {
                $table->dropColumn('district_scope');
            }
        });
    }
};

