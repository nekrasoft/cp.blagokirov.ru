<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 50)->default('admin')->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = [];

        if (Schema::hasColumn('users', 'role')) {
            $columns[] = 'role';
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $columns[] = 'is_active';
        }

        if ($columns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
