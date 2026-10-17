<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('works')) {
            return;
        }
        if (! Schema::hasColumn('works', 'billing_unit')) {
            Schema::table('works', fn (Blueprint $table) => $table->string('billing_unit', 20)->nullable());
        }
        if (! Schema::hasColumn('works', 'tariff')) {
            Schema::table('works', fn (Blueprint $table) => $table->decimal('tariff', 12, 2)->nullable());
        }
    }

    public function down(): void
    {
        foreach (['tariff', 'billing_unit'] as $column) {
            if (Schema::hasColumn('works', $column)) {
                Schema::table('works', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
